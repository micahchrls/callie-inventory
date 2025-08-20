<?php

namespace App\Filament\Resources\ProductVariantResource\RelationManagers;

use App\Enums\Platform;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BazarStockOutsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockOutItems';

    protected static ?string $title = 'Bazar Stock Outs';

    protected static ?string $modelLabel = 'Bazar Stock Out';

    protected static ?string $pluralModelLabel = 'Bazar Stock Outs';

    protected static ?string $icon = 'heroicon-m-building-storefront';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('platform')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('platform', Platform::BAZAR->value))
            ->columns([
                TextColumn::make('stockOut.created_at')
                    ->label('Date & Time')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('Quantity Out')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => '-' . number_format($state)),

                TextColumn::make('stockOut.reason')
                    ->label('Reason')
                    ->badge()
                    ->color('blue')
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
            ->emptyStateHeading('No Bazar stock outs found')
            ->emptyStateDescription('This product variant has no Bazar stock out history yet.')
            ->emptyStateIcon('heroicon-m-building-storefront');
    }
}
