<?php

namespace App\Filament\Widgets;

use App\Models\Product\ProductVariant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            $this->getTotalProductsStat(),
            $this->getInStockStat(),
            $this->getLowStockStat(),
            $this->getOutOfStockStat(),
        ];
    }

    private function getTotalProductsStat(): Stat
    {
        $totalProducts = ProductVariant::count();
        // Since is_discontinued column doesn't exist, treat all as active
        $activeProducts = $totalProducts;
        $discontinuedProducts = 0;

        return Stat::make('Total Products', number_format($totalProducts))
            ->description("{$activeProducts} active products")
            ->descriptionIcon('heroicon-m-cube')
            ->color('primary');
    }

    private function getInStockStat(): Stat
    {
        // Use quantity-based logic instead of status field
        $inStockCount = ProductVariant::whereRaw('quantity_in_stock > reorder_level')->count();
        $totalValue = ProductVariant::whereRaw('quantity_in_stock > reorder_level')
            ->sum('quantity_in_stock');

        $percentage = ProductVariant::count() > 0
            ? round(($inStockCount / ProductVariant::count()) * 100, 1)
            : 0;

        return Stat::make('In Stock', number_format($inStockCount))
            ->description("{$percentage}% of products ({$totalValue} total units)")
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');
    }

    private function getLowStockStat(): Stat
    {
        // Use quantity-based logic: low stock means quantity <= reorder_level AND quantity > 0
        $lowStockCount = ProductVariant::whereRaw('quantity_in_stock <= reorder_level AND quantity_in_stock > 0')->count();
        $totalLowStockItems = ProductVariant::whereRaw('quantity_in_stock <= reorder_level AND quantity_in_stock > 0')
            ->sum('quantity_in_stock');

        $percentage = ProductVariant::count() > 0
            ? round(($lowStockCount / ProductVariant::count()) * 100, 1)
            : 0;

        return Stat::make('Low Stock', number_format($lowStockCount))
            ->description("{$percentage}% of products ({$totalLowStockItems} units remaining)")
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color('warning');
    }

    private function getOutOfStockStat(): Stat
    {
        // Use quantity-based logic instead of status field
        $outOfStockCount = ProductVariant::where('quantity_in_stock', '<=', 0)->count();

        $percentage = ProductVariant::count() > 0
            ? round(($outOfStockCount / ProductVariant::count()) * 100, 1)
            : 0;

        // Get recently out of stock (last 7 days) - variants that became 0 stock recently
        $recentlyOutOfStock = ProductVariant::where('quantity_in_stock', '<=', 0)
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        return Stat::make('Out of Stock', number_format($outOfStockCount))
            ->description("{$percentage}% of products ({$recentlyOutOfStock} recently depleted)")
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger');
    }
}
