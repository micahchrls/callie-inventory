<?php

namespace App\Filament\Resources\ProductVariantResource\RelationManagers;

use App\Enums\Platform;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShopeeStockOutsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockOutItems';

    protected static ?string $title = 'Shopee Stock Outs';

    protected static ?string $modelLabel = 'Shopee Stock Out';

    protected static ?string $pluralModelLabel = 'Shopee Stock Outs';

    protected static ?string $icon = 'heroicon-m-shopping-bag';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('platform')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('platform', Platform::SHOPEE->value))
            ->columns([
                TextColumn::make('stockOut.created_at')
                    ->label('Date & Time')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('stockOut.productVariant.product.name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                TextColumn::make('stockOut.productVariant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied!')
                    ->copyMessageDuration(1500)
                    ->weight('semibold'),

                TextColumn::make('variation_name')
                    ->label('Variation')
                    ->getStateUsing(function ($record) {
                        $variant = $record->stockOut->productVariant;
                        if ($variant->variation_name) {
                            return $variant->variation_name;
                        }

                        $attributes = array_filter([
                            $variant->size,
                            $variant->color,
                            $variant->material,
                            $variant->weight,
                        ]);

                        return ! empty($attributes) ? implode(' | ', $attributes) : 'Standard';
                    })
                    ->searchable(false)
                    ->sortable(false)
                    ->limit(25)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 25 ? $state : null;
                    })
                    ->color('gray'),

                TextColumn::make('quantity')
                    ->label('Quantity Out')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => '-'.number_format($state)),

                TextColumn::make('stockOut.reason')
                    ->label('Reason')
                    ->badge()
                    ->color('orange')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'sale' => 'Sale/Order',
                            'damaged' => 'Damaged Goods',
                            'expired' => 'Expired Product',
                            'lost' => 'Lost/Missing',
                            'promotion' => 'Promotional Giveaway',
                            'sample' => 'Sample/Demo',
                            'return_to_supplier' => 'Return to Supplier',
                            'quality_issue' => 'Quality Issue',
                            'theft' => 'Theft/Shrinkage',
                            'adjustment' => 'Inventory Adjustment',
                            'other' => 'Other',
                            default => ucwords(str_replace('_', ' ', $state ?? 'Unknown')),
                        };
                    }),

                TextColumn::make('stockOut.user.name')
                    ->label('Processed By')
                    ->placeholder('System')
                    ->searchable(),

                TextColumn::make('note')
                    ->label('Notes')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 40 ? $state : null;
                    })
                    ->placeholder('-')
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('reason')
                    ->label('Reason')
                    ->options([
                        'sale' => 'Sale/Order',
                        'damaged' => 'Damaged Goods',
                        'expired' => 'Expired Product',
                        'lost' => 'Lost/Missing',
                        'promotion' => 'Promotional Giveaway',
                        'sample' => 'Sample/Demo',
                        'return_to_supplier' => 'Return to Supplier',
                        'quality_issue' => 'Quality Issue',
                        'theft' => 'Theft/Shrinkage',
                        'adjustment' => 'Inventory Adjustment',
                        'other' => 'Other',
                    ])
                    ->relationship('stockOut', 'reason'),
            ])
            ->defaultSort('stockOut.created_at', 'desc')
            ->emptyStateHeading('No Shopee stock outs found')
            ->emptyStateDescription('This product variant has no Shopee stock out history yet.')
            ->emptyStateIcon('heroicon-m-shopping-bag');
    }
}
