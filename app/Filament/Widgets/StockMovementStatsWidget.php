<?php

namespace App\Filament\Widgets;

use App\Models\StockIn;
use App\Models\StockOutItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockMovementStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            $this->getStockInStat(),
            $this->getStockOutStat(),
            $this->getTotalTransactionsStat(),
        ];
    }

    private function getStockInStat(): Stat
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        // Current month total
        $currentTotal = StockIn::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->sum('total_quantity');

        // Previous month total
        $previousTotal = StockIn::whereBetween('created_at', [$previousMonth, $previousMonthEnd])
            ->sum('total_quantity');

        // Calculate percentage change
        $percentageChange = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1)
            : ($currentTotal > 0 ? 100 : 0);

        // Get daily data for chart (last 30 days)
        $chartData = StockIn::select([
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_quantity) as total_quantity'),
        ])
        ->whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date')
        ->get();

        // Prepare chart values
        $chartValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartValues[] = $chartData->where('date', $date)->first()?->total_quantity ?? 0;
        }

        $stat = Stat::make('Stock In', number_format($currentTotal))
            ->description($this->getPercentageDescription($percentageChange, 'increase'))
            ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($chartValues)
            ->color($percentageChange >= 0 ? 'success' : 'danger');

        return $stat;
    }

    private function getStockOutStat(): Stat
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        // Current month total
        $currentTotal = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->whereBetween('stock_outs.created_at', [$currentMonth, $endOfMonth])
            ->sum('stock_out_items.quantity');

        // Previous month total
        $previousTotal = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->whereBetween('stock_outs.created_at', [$previousMonth, $previousMonthEnd])
            ->sum('stock_out_items.quantity');

        // Calculate percentage change
        $percentageChange = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1)
            : ($currentTotal > 0 ? 100 : 0);

        // Get daily data for chart (last 30 days)
        $chartData = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select([
                DB::raw('DATE(stock_outs.created_at) as date'),
                DB::raw('SUM(stock_out_items.quantity) as total_quantity'),
            ])
            ->whereBetween('stock_outs.created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
            ->groupBy(DB::raw('DATE(stock_outs.created_at)'))
            ->orderBy('date')
            ->get();

        // Prepare chart values
        $chartValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartValues[] = $chartData->where('date', $date)->first()?->total_quantity ?? 0;
        }

        $stat = Stat::make('Stock Out', number_format($currentTotal))
            ->description($this->getPercentageDescription($percentageChange, 'sales'))
            ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($chartValues)
            ->color($percentageChange >= 0 ? 'warning' : 'success');

        return $stat;
    }

    private function getTotalTransactionsStat(): Stat
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        // Current month total
        $currentTotal = StockIn::whereBetween('created_at', [$currentMonth, $endOfMonth])->count() + StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->whereBetween('stock_outs.created_at', [$currentMonth, $endOfMonth])
            ->count();

        // Previous month total
        $previousTotal = StockIn::whereBetween('created_at', [$previousMonth, $previousMonthEnd])->count() + StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->whereBetween('stock_outs.created_at', [$previousMonth, $previousMonthEnd])
            ->count();

        // Calculate percentage change
        $percentageChange = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1)
            : ($currentTotal > 0 ? 100 : 0);

        // Get daily data for chart (last 30 days)
        $chartData = StockIn::select([
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(id) as total_transactions'),
        ])
        ->whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date')
        ->get();

        $chartDataStockOut = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select([
                DB::raw('DATE(stock_outs.created_at) as date'),
                DB::raw('COUNT(stock_out_items.id) as total_transactions'),
            ])
            ->whereBetween('stock_outs.created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
            ->groupBy(DB::raw('DATE(stock_outs.created_at)'))
            ->orderBy('date')
            ->get();

        // Prepare chart values
        $chartValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartValues[] = ($chartData->where('date', $date)->first()?->total_transactions ?? 0) + ($chartDataStockOut->where('date', $date)->first()?->total_transactions ?? 0);
        }

        $stat = Stat::make('Total Transactions', number_format($currentTotal))
            ->description($this->getPercentageDescription($percentageChange, 'transactions'))
            ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($chartValues)
            ->color($percentageChange >= 0 ? 'success' : 'danger');

        return $stat;
    }

    private function getNetMovementStat(): Stat
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        // Current month total
        $currentTotal = StockIn::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->sum('total_quantity') - StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->whereBetween('stock_outs.created_at', [$currentMonth, $endOfMonth])
            ->sum('stock_out_items.quantity');

        // Previous month total
        $previousTotal = StockIn::whereBetween('created_at', [$previousMonth, $previousMonthEnd])
            ->sum('total_quantity') - StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->whereBetween('stock_outs.created_at', [$previousMonth, $previousMonthEnd])
            ->sum('stock_out_items.quantity');

        // Calculate percentage change
        $percentageChange = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1)
            : ($currentTotal > 0 ? 100 : 0);

        // Get daily data for chart (last 30 days)
        $chartData = StockIn::select([
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_quantity) as total_quantity'),
        ])
        ->whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date')
        ->get();

        $chartDataStockOut = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select([
                DB::raw('DATE(stock_outs.created_at) as date'),
                DB::raw('SUM(stock_out_items.quantity) as total_quantity'),
            ])
            ->whereBetween('stock_outs.created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
            ->groupBy(DB::raw('DATE(stock_outs.created_at)'))
            ->orderBy('date')
            ->get();

        // Prepare chart values
        $chartValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartValues[] = ($chartData->where('date', $date)->first()?->total_quantity ?? 0) - ($chartDataStockOut->where('date', $date)->first()?->total_quantity ?? 0);
        }

        $stat = Stat::make('Net Movement', number_format($currentTotal))
            ->description($this->getPercentageDescription($percentageChange, 'movement'))
            ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($chartValues)
            ->color($percentageChange >= 0 ? 'success' : 'danger');

        return $stat;
    }

    private function getPercentageDescription(float $percentage, string $type): string
    {
        $absPercentage = abs($percentage);

        if ($percentage > 0) {
            return "{$absPercentage}% increase";
        } elseif ($percentage < 0) {
            return "{$absPercentage}% decrease";
        }

        return 'No change from last month';
    }
}
