<?php

namespace App\Filament\Widgets;

use App\Enums\Platform;
use App\Models\StockOutItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductVariantStockOutStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Shopee Stock Out', $this->getTotalStockOut(Platform::SHOPEE))
                ->description('Total quantity sold on Shopee')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning')
                ->url(route('filament.admin.pages.shopee-stock-out-reports-dashboard')),

            Stat::make('TikTok Stock Out', $this->getTotalStockOut(Platform::TIKTOK))
                ->description('Total quantity sold on TikTok')
                ->descriptionIcon('heroicon-m-play')
                ->color('danger')
                ->url(route('filament.admin.pages.tiktok-stock-out-reports-dashboard')),

            Stat::make('bazaar Stock Out', $this->getTotalStockOut(Platform::BAZAAR))
                ->description('Total quantity sold on bazaar')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info')
                ->url(route('filament.admin.pages.bazaar-stock-out-reports-dashboard')),

            Stat::make('Others Stock Out', $this->getTotalStockOut(Platform::OTHERS))
                ->description('Total quantity sold on other platforms')
                ->descriptionIcon('heroicon-m-ellipsis-horizontal')
                ->color('success')
                ->url(route('filament.admin.pages.other-stock-out-reports-dashboard')),
        ];
    }

    protected function getTotalStockOut(Platform $platform): string
    {
        $total = StockOutItem::where('platform', $platform->value)
            ->sum('quantity');

        return number_format($total);
    }
}
