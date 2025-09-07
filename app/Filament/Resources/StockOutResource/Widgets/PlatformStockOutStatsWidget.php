<?php

namespace App\Filament\Resources\StockOutResource\Widgets;

use App\Models\StockOutItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PlatformStockOutStatsWidget extends BaseWidget
{
    // ✅ Must be public for Livewire to keep it
    public ?string $date = null;

    public function mount(?string $date = null): void
    {
        // Accept date parameter from widget instantiation
        $this->date = $date
                   ?? request()->query('date')
                   ?? now()->format('Y-m-d');
    }

    protected function getStats(): array
    {
        // ✅ Use the mounted value instead of calling request() again
        $targetDate = Carbon::parse($this->date);

        try {
            $platformTotals = StockOutItem::query()
                ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
                ->select('stock_out_items.platform', DB::raw('SUM(stock_out_items.quantity) as total_quantity'))
                ->whereDate('stock_outs.created_at', $targetDate->format('Y-m-d'))
                ->groupBy('stock_out_items.platform')
                ->pluck('total_quantity', 'platform');
        } catch (\Exception $e) {
            // Return empty collection if query fails
            $platformTotals = collect();
        }

        $getTotal = fn (string $platform) => $platformTotals[strtolower($platform)] ?? 0;

        return [
            Stat::make('Shopee', $getTotal('shopee'))
                ->description('Total Stock Outs')
                ->color('warning')
                ->icon('heroicon-o-shopping-cart')
                ->url(route('filament.admin.pages.shopee-stock-out-reports-dashboard')),

            Stat::make('Tiktok', $getTotal('tiktok'))
                ->description('Total Stock Outs')
                ->color('danger')
                ->icon('heroicon-o-play-circle')
                ->url(route('filament.admin.pages.tiktok-stock-out-reports-dashboard')),

            Stat::make('Bazar', $getTotal('bazar'))
                ->description('Total Stock Outs')
                ->color('info')
                ->icon('heroicon-o-building-storefront')
                ->url(route('filament.admin.pages.bazar-stock-out-reports-dashboard')),

            Stat::make(
                'Others',
                $platformTotals->except(['tiktok', 'shopee', 'bazar'])->sum()
            )
                ->description('Total Stock Outs')
                ->color('secondary')
                ->icon('heroicon-o-ellipsis-horizontal-circle')
                ->url(route('filament.admin.pages.other-stock-out-reports-dashboard')),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
