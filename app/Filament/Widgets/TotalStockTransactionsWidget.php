<?php

namespace App\Filament\Widgets;

use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\StockOutItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TotalStockTransactionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            $this->getTotalTransactionsStat(),
            $this->getStockInTransactionsStat(),
            $this->getStockOutTransactionsStat(),
        ];
    }

    private function getTotalTransactionsStat(): Stat
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Count total transactions this month
        $stockInCount = StockIn::whereBetween('created_at', [$currentMonth, $endOfMonth])->count();
        $stockOutCount = StockOut::whereBetween('created_at', [$currentMonth, $endOfMonth])->count();
        $totalTransactions = $stockInCount + $stockOutCount;

        // Previous month for comparison
        $previousMonth = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        $prevStockInCount = StockIn::whereBetween('created_at', [$previousMonth, $previousMonthEnd])->count();
        $prevStockOutCount = StockOut::whereBetween('created_at', [$previousMonth, $previousMonthEnd])->count();
        $prevTotalTransactions = $prevStockInCount + $prevStockOutCount;

        // Calculate percentage change
        $percentageChange = $prevTotalTransactions > 0
            ? round((($totalTransactions - $prevTotalTransactions) / $prevTotalTransactions) * 100, 1)
            : ($totalTransactions > 0 ? 100 : 0);

        return Stat::make('Total Transactions', number_format($totalTransactions))
            ->description("{$stockInCount} stock-ins, {$stockOutCount} stock-outs")
            ->descriptionIcon($percentageChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($percentageChange >= 0 ? 'success' : 'warning')
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'title' => $this->getPercentageDescription($percentageChange) . ' from last month'
            ]);
    }

    private function getStockInTransactionsStat(): Stat
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalQuantity = StockIn::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->sum('total_quantity');

        $transactionCount = StockIn::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->count();

        $avgPerTransaction = $transactionCount > 0 ? round($totalQuantity / $transactionCount, 1) : 0;

        // Get daily data for chart (last 7 days)
        $chartData = StockIn::select([
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_quantity) as daily_total'),
        ])
        ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date')
        ->get();

        // Prepare chart values
        $chartValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartValues[] = $chartData->where('date', $date)->first()?->daily_total ?? 0;
        }

        return Stat::make('Stock In Volume', number_format($totalQuantity))
            ->description("{$transactionCount} transactions (avg: {$avgPerTransaction}/transaction)")
            ->descriptionIcon('heroicon-m-arrow-up-circle')
            ->chart($chartValues)
            ->color('success');
    }

    private function getStockOutTransactionsStat(): Stat
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalQuantity = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->whereBetween('stock_outs.created_at', [$currentMonth, $endOfMonth])
            ->sum('stock_out_items.quantity');

        $transactionCount = StockOut::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->count();

        $avgPerTransaction = $transactionCount > 0 ? round($totalQuantity / $transactionCount, 1) : 0;

        // Get daily data for chart (last 7 days)
        $chartData = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select([
                DB::raw('DATE(stock_outs.created_at) as date'),
                DB::raw('SUM(stock_out_items.quantity) as daily_total'),
            ])
            ->whereBetween('stock_outs.created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->groupBy(DB::raw('DATE(stock_outs.created_at)'))
            ->orderBy('date')
            ->get();

        // Prepare chart values
        $chartValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartValues[] = $chartData->where('date', $date)->first()?->daily_total ?? 0;
        }

        return Stat::make('Stock Out Volume', number_format($totalQuantity))
            ->description("{$transactionCount} transactions (avg: {$avgPerTransaction}/transaction)")
            ->descriptionIcon('heroicon-m-arrow-down-circle')
            ->chart($chartValues)
            ->color('warning');
    }

    private function getPercentageDescription(float $percentage): string
    {
        $absPercentage = abs($percentage);

        if ($percentage > 0) {
            return "{$absPercentage}% increase";
        } elseif ($percentage < 0) {
            return "{$absPercentage}% decrease";
        }

        return 'No change';
    }
}
