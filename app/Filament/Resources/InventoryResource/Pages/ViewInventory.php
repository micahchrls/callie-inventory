<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
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
                Section::make('Product Information')
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
                                    ->copyMessageDuration(1500),

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
                    ->columns(2),

                Section::make('Stock Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('quantity_in_stock')
                                    ->label('Current Stock')
                                    ->size('xl')
                                    ->weight('bold')
                                    ->color(fn ($record) => $record->getStockStatusColor())
                                    ->formatStateUsing(fn ($state) => number_format($state).' units'),

                                TextEntry::make('reorder_level')
                                    ->label('Reorder Level')
                                    ->formatStateUsing(fn ($state) => number_format($state).' units'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->colors([
                                        'success' => 'in_stock',
                                        'warning' => 'low_stock',
                                        'danger' => 'out_of_stock',
                                        'secondary' => 'discontinued',
                                    ])
                                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('location')
                                    ->label('Storage Location')
                                    ->placeholder('Not set')
                                    ->icon('heroicon-o-map-pin'),

                                IconEntry::make('is_active')
                                    ->label('Active Status')
                                    ->boolean(),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Restock History')
                    ->schema([
                        Grid::make(2)
                            ->schema([
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
                    ->columns(1),

                Section::make('Variant Details')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('variation_name')
                                    ->label('Variant Name')
                                    ->placeholder('Standard variant'),

                                TextEntry::make('platform.name')
                                    ->label('Platform')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('No platform assigned'),

                                TextEntry::make('size')
                                    ->label('Size')
                                    ->placeholder('N/A'),

                                TextEntry::make('color')
                                    ->label('Color')
                                    ->placeholder('N/A'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('material')
                                    ->label('Material')
                                    ->placeholder('N/A'),

                                TextEntry::make('variant_initial')
                                    ->label('Variant Initial')
                                    ->placeholder('N/A'),

                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('M d, Y')
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Inventory Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }
}
