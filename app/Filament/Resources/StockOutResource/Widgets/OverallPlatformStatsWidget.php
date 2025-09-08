<?php

namespace App\Filament\Resources\StockOutResource\Widgets;

use App\Models\StockOutItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OverallPlatformStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Get overall platform-specific totals (all time)
        $platformTotals = StockOutItem::select('platform', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('platform')
            ->pluck('total_quantity', 'platform')
            ->toArray();

        // Define platform configurations with colors and icons
        $platforms = [
            'shopee' => [
                'label' => 'Shopee',
                'color' => 'warning',
                'icon' => 'heroicon-m-shopping-bag',
            ],
            'tiktok' => [
                'label' => 'TikTok',
                'color' => 'danger',
                'icon' => 'heroicon-m-video-camera',
            ],
            'bazaar' => [
                'label' => 'bazaar',
                'color' => 'info',
                'icon' => 'heroicon-m-building-storefront',
            ],
            'others' => [
                'label' => 'Others',
                'color' => 'gray',
                'icon' => 'heroicon-m-ellipsis-horizontal',
            ],
        ];

        $stats = [];

        foreach ($platforms as $platformKey => $config) {
            $quantity = $platformTotals[$platformKey] ?? 0;

            $stats[] = Stat::make($config['label'], number_format($quantity))
                ->description('Total stock out (all time)')
                ->descriptionIcon($config['icon'])
                ->color($config['color'])
                ->chart($this->getChartData($platformKey));
        }

        return $stats;
    }

    protected function getChartData(string $platform): array
    {
        // Get last 7 days of data for the chart
        $chartData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $quantity = StockOutItem::where('platform', $platform)
                ->whereHas('stockOut', function ($query) use ($date) {
                    $query->whereDate('created_at', $date->format('Y-m-d'));
                })
                ->sum('quantity');

            $chartData[] = $quantity;
        }

        return $chartData;
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
