<?php

namespace App\Filament\Widgets;

use App\Enums\Platform;
use App\Models\StockOutItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductVariantStockOutStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Shopee Stock Out', $this->getTotalStockOut(Platform::SHOPEE))
                ->description('Total quantity sold on Shopee')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),

            Stat::make('TikTok Stock Out', $this->getTotalStockOut(Platform::TIKTOK))
                ->description('Total quantity sold on TikTok')
                ->descriptionIcon('heroicon-m-play')
                ->color('danger'),

            Stat::make('Bazar Stock Out', $this->getTotalStockOut(Platform::BAZAR))
                ->description('Total quantity sold on Bazar')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),

            Stat::make('Others Stock Out', $this->getTotalStockOut(Platform::OTHERS))
                ->description('Total quantity sold on other platforms')
                ->descriptionIcon('heroicon-m-ellipsis-horizontal')
                ->color('success'),
        ];
    }

    protected function getTotalStockOut(Platform $platform): string
    {
        $total = StockOutItem::where('platform', $platform->value)
            ->sum('quantity');

        return number_format($total);
    }
}
