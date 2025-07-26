<?php

namespace App\Filament\Resources\InventoryResource\Widgets;

use App\Models\Product\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Alerts';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Current Stock')
                    ->alignCenter()
                    ->size('lg')
                    ->weight('bold')
                    ->color('warning')
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

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->limit(20)
                    ->placeholder('Not specified'),
            ])
            ->actions([
                Tables\Actions\Action::make('restock')
                    ->label('Quick Restock')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->action(function (Product $record) {
                        // This would redirect to edit page or open a modal for restocking
                        redirect()->route('filament.admin.resources.inventory.edit', $record);
                    }),
            ])
            ->emptyStateHeading('No Low Stock Items')
            ->emptyStateDescription('All items have sufficient stock levels.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->poll('60s'); // Refresh every minute
    }
}
