<?php

namespace App\Filament\Resources\InventoryResource\Widgets;

use App\Models\Product\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class InventoryOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Get inventory statistics
        $totalItems = Product::count();
        $lowStockItems = Product::whereColumn('quantity_in_stock', '<=', 'reorder_level')
            ->where('quantity_in_stock', '>', 0)
            ->count();
        $outOfStockItems = Product::where('quantity_in_stock', '<=', 0)->count();
        $inStockItems = Product::where('quantity_in_stock', '>', DB::raw('reorder_level'))->count();
        $discontinuedItems = Product::where('status', 'discontinued')->count();
        
        // Calculate total stock value (if cost price is available)
        $totalStockQuantity = Product::sum('quantity_in_stock');
        
        return [
            Stat::make('Total Items', number_format($totalItems))
                ->description('All inventory items')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
                
            Stat::make('In Stock', number_format($inStockItems))
                ->description('Items with sufficient stock')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Low Stock', number_format($lowStockItems))
                ->description('Items needing reorder')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
                
            Stat::make('Out of Stock', number_format($outOfStockItems))
                ->description('Items with zero stock')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
                
            Stat::make('Total Stock Units', number_format($totalStockQuantity))
                ->description('Total quantity across all items')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
                
            Stat::make('Discontinued', number_format($discontinuedItems))
                ->description('Items marked as discontinued')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
}
