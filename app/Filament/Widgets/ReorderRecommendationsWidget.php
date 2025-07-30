<?php

namespace App\Filament\Widgets;

use App\Models\Product\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ReorderRecommendationsWidget extends BaseWidget
{
    protected static ?string $heading = 'Items Needing Reorder';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    // Role-based access control - accessible to users with stock view permission
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->can('stock.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()
                    ->with(['product.productCategory'])
                    ->where(function (Builder $query) {
                        $query->where('status', 'low_stock')
                              ->orWhere('status', 'out_of_stock');
                    })
                    ->where('status', '!=', 'discontinued')
                    ->where('is_active', true)
                    ->orderByRaw('
                        CASE
                            WHEN status = "out_of_stock" THEN 1
                            WHEN status = "low_stock" THEN 2
                            ELSE 3
                        END,
                        quantity_in_stock ASC
                    ')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
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
                    ->color(fn ($record) => $record->quantity_in_stock <= 0 ? 'danger' : 'warning')
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder Level')
                    ->alignCenter()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'low_stock',
                        'danger' => 'out_of_stock',
                    ])
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
            ])
            ->actions([
                Tables\Actions\Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->action(function (ProductVariant $record) {
                        return redirect()->to('/admin/product-variants/' . $record->id . '/edit');
                    }),
            ])
            ->emptyStateHeading('No Items Need Reordering')
            ->emptyStateDescription('All items have sufficient stock levels.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->poll('300s'); // Refresh every 5 minutes
    }
}
