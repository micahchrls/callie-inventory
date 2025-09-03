<?php

namespace App\Filament\Widgets;

use App\Models\Product\ProductVariant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

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
        $inStockCount = ProductVariant::where('status', 'in_stock')->count();
        $totalValue = ProductVariant::where('status', 'in_stock')
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
        $lowStockCount = ProductVariant::where('status', 'low_stock')->count();
        $totalLowStockItems = ProductVariant::where('status', 'low_stock')
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
        $outOfStockCount = ProductVariant::where('status', 'out_of_stock')->count();

        $percentage = ProductVariant::count() > 0
            ? round(($outOfStockCount / ProductVariant::count()) * 100, 1)
            : 0;

        // Get recently out of stock (last 7 days)
        $recentlyOutOfStock = ProductVariant::where('status', 'out_of_stock')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        return Stat::make('Out of Stock', number_format($outOfStockCount))
            ->description("{$percentage}% of products ({$recentlyOutOfStock} recently depleted)")
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger');
    }
}
