<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Notifications\Notification;

class ViewInventory extends ViewRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_restock')
                ->label('🔄 Quick Restock')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('quantity')
                        ->label('Add Quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->step(1),
                    \Filament\Forms\Components\Checkbox::make('update_restock_date')
                        ->label('Update restocked date')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $this->record->adjustStock($data['quantity'], 'add');

                    if ($data['update_restock_date']) {
                        $this->record->update(['last_restocked_at' => now()]);
                    }

                    Notification::make()
                        ->title('✅ Stock Updated')
                        ->body("Added {$data['quantity']} units to {$this->record->name}")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\EditAction::make()
                ->label('Manage Stock'),
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
                                TextEntry::make('name')
                                    ->label('Product Name')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('sku')
                                    ->label('SKU')
                                    ->copyable()
                                    ->copyMessage('SKU copied!')
                                    ->copyMessageDuration(1500),

                                TextEntry::make('productCategory.name')
                                    ->label('Category')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('productSubCategory.name')
                                    ->label('Sub Category')
                                    ->badge()
                                    ->color('success'),
                            ]),

                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
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
                                    ->formatStateUsing(fn ($state) => number_format($state) . ' units'),

                                TextEntry::make('reorder_level')
                                    ->label('Reorder Level')
                                    ->formatStateUsing(fn ($state) => number_format($state) . ' units'),

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
