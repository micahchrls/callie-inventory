<?php

namespace App\Filament\Resources\StockInResource\Pages;

use App\Filament\Resources\StockInResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStockIn extends ViewRecord
{
    protected static string $resource = StockInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Stock In Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('product.name')
                            ->label('Product'),
                        Infolists\Components\TextEntry::make('productVariant.sku')
                            ->label('Product Variant'),
                        Infolists\Components\TextEntry::make('reason')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'restock' => 'success',
                                'return_to_callie' => 'info',
                                'returned' => 'warning',
                                'other' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'restock' => 'Restock',
                                'return_to_callie' => 'Return to Callie',
                                'returned' => 'Returned to Supplier',
                                'other' => 'Other',
                                default => ucfirst($state),
                            }),
                        Infolists\Components\TextEntry::make('total_quantity')
                            ->label('Total Quantity')
                            ->badge()
                            ->color('success')
                            ->suffix(' units'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Processed By')
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Stock Status Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('productVariant.quantity_in_stock')
                            ->label('Current Stock Level')
                            ->badge()
                            ->color(function ($record): string {
                                $variant = $record->productVariant;
                                if (! $variant) {
                                    return 'gray';
                                }

                                if ($variant->quantity_in_stock <= 0) {
                                    return 'danger';
                                } elseif ($variant->quantity_in_stock <= $variant->reorder_level) {
                                    return 'warning';
                                } else {
                                    return 'success';
                                }
                            })
                            ->suffix(' units')
                            ->formatStateUsing(function ($record): string {
                                $variant = $record->productVariant;
                                if (! $variant) {
                                    return 'N/A';
                                }

                                $status = '';
                                if ($variant->quantity_in_stock <= 0) {
                                    $status = ' (Out of Stock)';
                                } elseif ($variant->quantity_in_stock <= $variant->reorder_level) {
                                    $status = ' (Low Stock)';
                                } else {
                                    $status = ' (In Stock)';
                                }

                                return number_format($variant->quantity_in_stock).$status;
                            }),
                        Infolists\Components\TextEntry::make('productVariant.reorder_level')
                            ->label('Reorder Level')
                            ->suffix(' units')
                            ->color('info'),
                        Infolists\Components\TextEntry::make('productVariant.status')
                            ->label('Variant Status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'in_stock' => 'success',
                                'low_stock' => 'warning',
                                'out_of_stock' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'in_stock' => 'In Stock',
                                'low_stock' => 'Low Stock',
                                'out_of_stock' => 'Out of Stock',
                                default => 'Unknown',
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Stock In Items Breakdown')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('stockInItems')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Quantity')
                                    ->suffix(' units')
                                    ->color('success')
                                    ->weight('semibold'),
                                Infolists\Components\TextEntry::make('note')
                                    ->label('Notes')
                                    ->placeholder('No notes')
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Added At')
                                    ->dateTime()
                                    ->color('gray'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->stockInItems()->exists()),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Transaction Notes')
                            ->placeholder('No additional notes')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => ! empty($record->notes))
                    ->collapsible(),
            ]);
    }
}
