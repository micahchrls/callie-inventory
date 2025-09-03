<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInventory extends ViewRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Stock'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(2)
                    ->schema([
                        // Left Column: Combined Product Information & Variant Details
                        Section::make('Product Information & Variants')
                            ->description('Detailed information about the product and its variations')
                            ->schema([
                                // Product Information
                                Section::make('Product Details')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('product.name')
                                                    ->label('Product Name')
                                                    ->size('lg')
                                                    ->weight('bold'),

                                                TextEntry::make('sku')
                                                    ->label('SKU')
                                                    ->copyable()
                                                    ->copyMessage('SKU copied!')
                                                    ->copyMessageDuration(1500)
                                                    ->fontFamily('mono'),

                                                TextEntry::make('product.productCategory.name')
                                                    ->label('Category')
                                                    ->badge()
                                                    ->color('primary')
                                                    ->placeholder('No category assigned'),

                                                TextEntry::make('product.productSubCategory.name')
                                                    ->label('Sub Category')
                                                    ->badge()
                                                    ->color('success')
                                                    ->placeholder('No subcategory assigned'),
                                            ]),

                                        TextEntry::make('product.description')
                                            ->label('Description')
                                            ->columnSpanFull()
                                            ->placeholder('No description available'),
                                    ])
                                    ->compact(),

                                // Variant Information
                                Section::make('Variant Details')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('variation_name')
                                                    ->label('Variant Name')
                                                    ->placeholder('Standard variant')
                                                    ->weight('semibold'),

                                                TextEntry::make('size')
                                                    ->label('Size')
                                                    ->badge()
                                                    ->color('info')
                                                    ->placeholder('N/A')
                                                    ->visible(fn ($record) => $record->size),

                                                TextEntry::make('color')
                                                    ->label('Color')
                                                    ->badge()
                                                    ->color('success')
                                                    ->placeholder('N/A')
                                                    ->visible(fn ($record) => $record->color),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('material')
                                                    ->label('Material')
                                                    ->badge()
                                                    ->color('warning')
                                                    ->placeholder('N/A')
                                                    ->visible(fn ($record) => $record->material),

                                                TextEntry::make('variant_initial')
                                                    ->label('Variant Initial')
                                                    ->badge()
                                                    ->color('secondary')
                                                    ->placeholder('N/A')
                                                    ->visible(fn ($record) => $record->variant_initial),

                                                TextEntry::make('created_at')
                                                    ->label('Created')
                                                    ->dateTime('M d, Y')
                                                    ->icon('heroicon-o-calendar'),
                                            ]),

                                        TextEntry::make('platform.name')
                                            ->label('Platform')
                                            ->badge()
                                            ->color('info')
                                            ->placeholder('No platform assigned')
                                            ->visible(fn ($record) => $record->platform),

                                        TextEntry::make('location')
                                            ->label('Storage Location')
                                            ->placeholder('Not set')
                                            ->icon('heroicon-o-map-pin')
                                            ->visible(fn ($record) => $record->location),

                                        TextEntry::make('notes')
                                            ->label('Inventory Notes')
                                            ->placeholder('No notes')
                                            ->columnSpanFull()
                                            ->visible(fn ($record) => $record->notes),
                                    ])
                                    ->compact(),
                            ])
                            ->columnSpan(1),

                        // Right Column: Stock Information
                        Section::make('Stock Information')
                            ->description('Current stock levels and inventory management')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('quantity_in_stock')
                                            ->label('Current Stock')
                                            ->size('xl')
                                            ->weight('bold')
                                            ->color(fn ($record) => match (true) {
                                                $record->quantity_in_stock <= 0 => 'danger',
                                                $record->quantity_in_stock <= $record->reorder_level => 'warning',
                                                default => 'success',
                                            })
                                            ->formatStateUsing(fn ($state) => number_format($state).' units')
                                            ->icon('heroicon-m-cube'),

                                        TextEntry::make('reorder_level')
                                            ->label('Reorder Level')
                                            ->formatStateUsing(fn ($state) => number_format($state).' units')
                                            ->icon('heroicon-m-exclamation-triangle')
                                            ->helperText('Alert when stock reaches this level'),

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->getStateUsing(function ($record): string {
                                                // Calculate status based on current stock and reorder level
                                                if ($record->quantity_in_stock <= 0) {
                                                    return 'out_of_stock';
                                                } elseif ($record->quantity_in_stock <= $record->reorder_level) {
                                                    return 'low_stock';
                                                } else {
                                                    return 'in_stock';
                                                }
                                            })
                                            ->color(fn (string $state): string => match ($state) {
                                                'in_stock' => 'success',
                                                'low_stock' => 'warning',
                                                'out_of_stock' => 'danger',
                                                'discontinued' => 'gray',
                                                default => 'secondary',
                                            })
                                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                                            ->icon('heroicon-m-signal'),

                                        TextEntry::make('last_restocked_at')
                                            ->label('Last Restocked')
                                            ->dateTime('M d, Y H:i')
                                            ->placeholder('Never restocked')
                                            ->icon('heroicon-o-clock'),

                                        TextEntry::make('updated_at')
                                            ->label('Last Updated')
                                            ->dateTime('M d, Y H:i')
                                            ->icon('heroicon-o-pencil'),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
