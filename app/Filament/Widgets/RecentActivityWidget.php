<?php

namespace App\Filament\Widgets;

use App\Models\Product\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Inventory Activity';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    // Role-based access control - accessible to users with stock movements view permission
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->can('stock.movements.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()
                    ->with(['product.productCategory'])
                    ->whereNotNull('last_restocked_at')
                    ->orderBy('last_restocked_at', 'desc')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('variation_name')
                    ->label('Variant')
                    ->limit(20)
                    ->placeholder('No variation'),

                Tables\Columns\TextColumn::make('product.productCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Current Stock')
                    ->alignCenter()
                    ->weight('bold')
                    ->color(fn ($record) => match($record->status) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'in_stock',
                        'warning' => 'low_stock',
                        'danger' => 'out_of_stock',
                        'secondary' => 'discontinued',
                    ])
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Last Activity')
                    ->dateTime('M j, Y H:i')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->url(fn (ProductVariant $record): string => '/admin/product-variants/' . $record->id),
            ])
            ->emptyStateHeading('No Recent Activity')
            ->emptyStateDescription('No products have been restocked recently.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
