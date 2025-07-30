<?php

namespace App\Filament\Widgets;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductVariant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExecutiveOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    // Role-based access control - accessible to all authenticated users with inventory view permission
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->can('products.view');
    }

    protected function getStats(): array
    {
        // Get real inventory counts from ProductVariant since inventory fields moved there
        $totalProducts = Product::count();
        $totalVariants = ProductVariant::count();
        $inStockCount = ProductVariant::where('status', 'in_stock')->count();
        $lowStockCount = ProductVariant::where('status', 'low_stock')->count();
        $outOfStockCount = ProductVariant::where('status', 'out_of_stock')->count();
        $discontinuedCount = ProductVariant::where('status', 'discontinued')->count();
        $totalCategories = ProductCategory::count();

        return [
            Stat::make('Total Products', number_format($totalProducts))
                ->description($totalVariants . ' variants total')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('In Stock', number_format($inStockCount))
                ->description('Variants available for sale')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Low Stock', number_format($lowStockCount))
                ->description('Variants needing reorder')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->url('/admin/product-variants'),

            Stat::make('Out of Stock', number_format($outOfStockCount))
                ->description('Variants not available')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url('/admin/product-variants'),

            Stat::make('Discontinued', number_format($discontinuedCount))
                ->description('Variants no longer sold')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),

            Stat::make('Categories', number_format($totalCategories))
                ->description('Product categories')
                ->descriptionIcon('heroicon-m-folder')
                ->color('info')
                ->url('/admin/product-categories'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
