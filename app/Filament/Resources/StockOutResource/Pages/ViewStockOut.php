<?php

namespace App\Filament\Resources\StockOutResource\Pages;

use App\Enums\Platform;
use App\Filament\Resources\StockOutResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStockOut extends ViewRecord
{
    protected static string $resource = StockOutResource::class;

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
                Infolists\Components\Section::make('Stock Out Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('product.name')
                            ->label('Product'),
                        Infolists\Components\TextEntry::make('productVariant.sku')
                            ->label('Product Variant'),
                        Infolists\Components\TextEntry::make('reason')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'sale' => 'success',
                                'damaged' => 'danger',
                                'expired' => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('total_quantity')
                            ->label('Total Quantity')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Platform Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('tiktok_total')
                            ->label('TikTok')
                            ->getStateUsing(function ($record): string {
                                $total = $record->stockOutItems->where('platform', 'tiktok')->sum('quantity');

                                return $total > 0 ? number_format($total).' units' : 'No sales';
                            })
                            ->badge()
                            ->color(fn ($record): string => $record->stockOutItems->where('platform', 'tiktok')->sum('quantity') > 0 ? 'danger' : 'gray'
                            ),
                        Infolists\Components\TextEntry::make('shopee_total')
                            ->label('Shopee')
                            ->getStateUsing(function ($record): string {
                                $total = $record->stockOutItems->where('platform', 'shopee')->sum('quantity');

                                return $total > 0 ? number_format($total).' units' : 'No sales';
                            })
                            ->badge()
                            ->color(fn ($record): string => $record->stockOutItems->where('platform', 'shopee')->sum('quantity') > 0 ? 'warning' : 'gray'
                            ),
                        Infolists\Components\TextEntry::make('bazar_total')
                            ->label('Bazar')
                            ->getStateUsing(function ($record): string {
                                $total = $record->stockOutItems->where('platform', 'bazar')->sum('quantity');

                                return $total > 0 ? number_format($total).' units' : 'No sales';
                            })
                            ->badge()
                            ->color(fn ($record): string => $record->stockOutItems->where('platform', 'bazar')->sum('quantity') > 0 ? 'info' : 'gray'
                            ),
                        Infolists\Components\TextEntry::make('others_total')
                            ->label('Others')
                            ->getStateUsing(function ($record): string {
                                $total = $record->stockOutItems->where('platform', 'others')->sum('quantity');

                                return $total > 0 ? number_format($total).' units' : 'No sales';
                            })
                            ->badge()
                            ->color(fn ($record): string => $record->stockOutItems->where('platform', 'others')->sum('quantity') > 0 ? 'secondary' : 'gray'
                            ),
                    ])
                    ->columns(4)
                    ->visible(fn ($record) => $record->stockOutItems()->exists()),

                Infolists\Components\Section::make('Detailed Platform Breakdown')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('stockOutItems')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('platform')
                                    ->formatStateUsing(fn (string $state): string => Platform::tryFrom($state)?->label() ?? ucfirst($state)
                                    )
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'tiktok' => 'pink',
                                        'shopee' => 'orange',
                                        'bazar' => 'blue',
                                        'others' => 'gray',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->suffix(' units')
                                    ->color('success'),
                                Infolists\Components\TextEntry::make('note')
                                    ->placeholder('No notes')
                                    ->color('gray'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->stockOutItems()->exists()),
            ]);
    }
}
