<?php

namespace App\Filament\Resources\InventoryResource\Widgets;

use App\Models\Product\ProductVariant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InventoryOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Get inventory statistics from ProductVariant since inventory fields moved there
        $totalItems = ProductVariant::count();
        $lowStockItems = ProductVariant::whereColumn('quantity_in_stock', '<=', 'reorder_level')
            ->where('quantity_in_stock', '>', 0)
            ->count();
        $outOfStockItems = ProductVariant::where('quantity_in_stock', '<=', 0)->count();
        $inStockItems = ProductVariant::where('quantity_in_stock', '>', DB::raw('reorder_level'))->count();
        $discontinuedItems = ProductVariant::where('status', 'discontinued')->count();

        // Calculate total stock value (if cost price is available)
        $totalStockQuantity = ProductVariant::sum('quantity_in_stock');

        return [
            Stat::make('Total Variants', number_format($totalItems))
                ->description('All inventory variants')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('In Stock', number_format($inStockItems))
                ->description('Variants with sufficient stock')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Low Stock', number_format($lowStockItems))
                ->description('Variants needing reorder')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Out of Stock', number_format($outOfStockItems))
                ->description('Variants with zero stock')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total Stock Units', number_format($totalStockQuantity))
                ->description('Total quantity across all variants')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Discontinued', number_format($discontinuedItems))
                ->description('Discontinued variants')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
