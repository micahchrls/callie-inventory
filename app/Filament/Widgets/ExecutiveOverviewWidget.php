<?php

namespace App\Filament\Widgets;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        // Get real inventory counts
        $totalProducts = Product::count();
        $inStockCount = Product::where('status', 'in_stock')->count();
        $lowStockCount = Product::where('status', 'low_stock')->count();
        $outOfStockCount = Product::where('status', 'out_of_stock')->count();
        $discontinuedCount = Product::where('status', 'discontinued')->count();
        $totalCategories = ProductCategory::count();

        return [
            Stat::make('Total Products', number_format($totalProducts))
                ->description('All inventory items')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('In Stock', number_format($inStockCount))
                ->description('Items available for sale')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Low Stock', number_format($lowStockCount))
                ->description('Items needing reorder')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->url('/admin/inventory'),

            Stat::make('Out of Stock', number_format($outOfStockCount))
                ->description('Items not available')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url('/admin/inventory'),

            Stat::make('Discontinued', number_format($discontinuedCount))
                ->description('Items no longer sold')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),

            Stat::make('Categories', number_format($totalCategories))
                ->description('Product categories')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
