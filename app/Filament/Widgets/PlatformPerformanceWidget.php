<?php

namespace App\Filament\Widgets;

use App\Enums\Platform;
use App\Models\StockOutItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PlatformPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'Platform Performance Comparison (Last 30 Days)';
    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        // Get platform performance data
        $platformData = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select([
                'stock_out_items.platform',
                DB::raw('SUM(stock_out_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT stock_outs.id) as total_orders'),
                DB::raw('COUNT(DISTINCT stock_outs.product_variant_id) as unique_products'),
                DB::raw('AVG(stock_out_items.quantity) as avg_items_per_order')
            ])
            ->where('stock_outs.created_at', '>=', $thirtyDaysAgo)
            ->groupBy('stock_out_items.platform')
            ->orderBy('total_quantity', 'desc')
            ->get();

        $labels = [];
        $quantities = [];
        $orders = [];
        $backgroundColors = [];
        $borderColors = [];

        $platformColors = [
            'shopee' => ['bg' => 'rgba(255, 159, 64, 0.8)', 'border' => 'rgb(255, 159, 64)'],
            'tiktok' => ['bg' => 'rgba(255, 99, 132, 0.8)', 'border' => 'rgb(255, 99, 132)'],
            'bazar' => ['bg' => 'rgba(54, 162, 235, 0.8)', 'border' => 'rgb(54, 162, 235)'],
            'others' => ['bg' => 'rgba(153, 102, 255, 0.8)', 'border' => 'rgb(153, 102, 255)'],
        ];

        foreach ($platformData as $platform) {
            $platformName = ucfirst($platform->platform);
            $labels[] = $platformName;
            $quantities[] = $platform->total_quantity;
            $orders[] = $platform->total_orders;

            $colorKey = strtolower($platform->platform);
            $colors = $platformColors[$colorKey] ?? $platformColors['others'];
            $backgroundColors[] = $colors['bg'];
            $borderColors[] = $colors['border'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Units Sold',
                    'data' => $quantities,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'afterLabel' => 'function(context) {
                            var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                            var percentage = Math.round((context.parsed / total) * 100);
                            return percentage + "% of total sales";
                        }'
                    ]
                ]
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    public function getDescription(): ?string
    {
        $thirtyDaysAgo = now()->subDays(30);

        $stats = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select([
                'stock_out_items.platform',
                DB::raw('SUM(stock_out_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT stock_outs.id) as total_orders')
            ])
            ->where('stock_outs.created_at', '>=', $thirtyDaysAgo)
            ->groupBy('stock_out_items.platform')
            ->orderBy('total_quantity', 'desc')
            ->get();

        if ($stats->isEmpty()) {
            return 'No sales data available for the last 30 days';
        }

        $topPlatform = $stats->first();
        $totalUnits = $stats->sum('total_quantity');
        $totalOrders = $stats->sum('total_orders');
        $avgOrderSize = $totalOrders > 0 ? round($totalUnits / $totalOrders, 1) : 0;

        return "Top platform: " . ucfirst($topPlatform->platform) .
               " ({$topPlatform->total_quantity} units) | " .
               "Total: {$totalUnits} units in {$totalOrders} orders | " .
               "Avg order size: {$avgOrderSize} items";
    }
}
