<?php

namespace App\Filament\Widgets;

use App\Models\Product\Product;
use Filament\Widgets\ChartWidget;

class StockPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Inventory Status Breakdown';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Get actual inventory status counts
        $inStock = Product::where('status', 'in_stock')->count();
        $lowStock = Product::where('status', 'low_stock')->count();
        $outOfStock = Product::where('status', 'out_of_stock')->count();
        $discontinued = Product::where('status', 'discontinued')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Inventory Status',
                    'data' => [$inStock, $lowStock, $outOfStock, $discontinued],
                    'backgroundColor' => [
                        '#10B981', // Green for In Stock
                        '#F59E0B', // Yellow for Low Stock
                        '#EF4444', // Red for Out of Stock
                        '#6B7280', // Gray for Discontinued
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['In Stock', 'Low Stock', 'Out of Stock', 'Discontinued'],
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
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
