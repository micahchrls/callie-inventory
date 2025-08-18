<?php

namespace App\Filament\Resources\InventoryResource\Widgets;

use App\Models\Product\ProductCategory;
use Filament\Widgets\ChartWidget;

class CategoryBreakdownWidget extends ChartWidget
{
    protected static ?string $heading = 'Inventory by Category';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $categoryData = ProductCategory::withCount('products')
            ->having('products_count', '>', 0)
            ->get();

        $labels = [];
        $data = [];
        $colors = [
            '#3B82F6', // Blue
            '#EF4444', // Red
            '#10B981', // Green
            '#F59E0B', // Amber
            '#8B5CF6', // Violet
            '#EC4899', // Pink
            '#06B6D4', // Cyan
            '#84CC16', // Lime
        ];

        foreach ($categoryData as $index => $category) {
            $labels[] = $category->name;
            $data[] = $category->products_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Items per Category',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor' => array_slice($colors, 0, count($data)),
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
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
