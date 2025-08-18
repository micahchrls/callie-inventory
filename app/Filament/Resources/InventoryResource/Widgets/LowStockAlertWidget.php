<?php

namespace App\Filament\Resources\InventoryResource\Widgets;

use App\Models\Product\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Alerts';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()
                    ->with(['product.productCategory'])
                    ->whereColumn('quantity_in_stock', '<=', 'reorder_level')
                    ->where('quantity_in_stock', '>', 0)
                    ->where('status', '!=', 'discontinued')
                    ->orderBy('quantity_in_stock', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->searchable()
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
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder At')
                    ->alignCenter()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'in_stock',
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
                    ->url(fn (ProductVariant $record): string => '/admin/product-variants/'.$record->id.'/edit'),
            ])
            ->emptyStateHeading('No Low Stock Items')
            ->emptyStateDescription('All items have sufficient stock levels.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->poll('60s'); // Auto-refresh every minute
    }
}
