<?php

namespace App\Filament\Widgets;

use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockTransactionsSummaryWidget extends StatsOverviewWidget
{
    public string $date;
    public ?string $platform = null;
    public ?string $transactionType = null;

    protected function getStats(): array
    {
        $summaryStats = $this->calculateSummaryStats();

        return [
            Stat::make('Total Transactions', number_format($summaryStats['total_movements']))
                ->description($summaryStats['average_per_hour'] . ' per hour')
                ->descriptionIcon('heroicon-m-clock')
                ->chart($this->getHourlyTrend())
                ->color('primary')
                ->icon('heroicon-o-arrows-up-down'),

            Stat::make('Stock In', '+' . number_format($summaryStats['total_in']))
                ->description(number_format($summaryStats['in_percentage'], 1) . '% of total')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('success')
                ->icon('heroicon-o-arrow-down-circle')
                ->extraAttributes([
                    'class' => 'ring-1 ring-green-200 dark:ring-green-800',
                ]),

            Stat::make('Stock Out', '-' . number_format($summaryStats['total_out']))
                ->description(number_format($summaryStats['out_percentage'], 1) . '% of total')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('danger')
                ->icon('heroicon-o-arrow-up-circle')
                ->extraAttributes([
                    'class' => 'ring-1 ring-red-200 dark:ring-red-800',
                ]),
        ];
    }

    protected function calculateSummaryStats(): array
    {
        $query = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->whereDate('stock_movements.created_at', $this->date);

        if ($this->platform) {
            $query->where('platforms.name', $this->platform);
        }

        if ($this->transactionType === 'stock_in') {
            $query->where(function ($q) {
                $q->whereIn('stock_movements.movement_type', ['restock', 'return', 'initial_stock'])
                  ->orWhere('stock_movements.quantity_change', '>', 0);
            });
        } elseif ($this->transactionType === 'stock_out') {
            $query->where(function ($q) {
                $q->whereIn('stock_movements.movement_type', ['stock_out', 'sale'])
                  ->orWhere('stock_movements.quantity_change', '<', 0);
            });
        }

        $stats = $query->select([
            DB::raw('COUNT(DISTINCT stock_movements.product_variant_id) as unique_products'),
            DB::raw('SUM(ABS(stock_movements.quantity_change)) as total_quantity'),
            DB::raw('COUNT(*) as total_movements'),
            DB::raw('COALESCE(SUM(ABS(stock_movements.total_cost)), 0) as total_value'),
            DB::raw('SUM(CASE WHEN stock_movements.quantity_change > 0 THEN ABS(stock_movements.quantity_change) ELSE 0 END) as total_in'),
            DB::raw('SUM(CASE WHEN stock_movements.quantity_change < 0 THEN ABS(stock_movements.quantity_change) ELSE 0 END) as total_out'),
            DB::raw('AVG(ABS(stock_movements.total_cost)) as average_value')
        ])->first();

        // Get top reason
        $topReasonQuery = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->whereDate('stock_movements.created_at', $this->date)
            ->when($this->platform, fn($q) => $q->where('platforms.name', $this->platform))
            ->when($this->transactionType === 'stock_in', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereIn('stock_movements.movement_type', ['restock', 'return', 'initial_stock'])
                         ->orWhere('stock_movements.quantity_change', '>', 0);
                });
            })
            ->when($this->transactionType === 'stock_out', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereIn('stock_movements.movement_type', ['stock_out', 'sale'])
                         ->orWhere('stock_movements.quantity_change', '<', 0);
                });
            })
            ->select('stock_movements.reason', DB::raw('COUNT(*) as count'))
            ->whereNotNull('stock_movements.reason')
            ->groupBy('stock_movements.reason')
            ->orderByDesc('count')
            ->first();

        // Get affected categories count
        $categoriesCount = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->whereDate('stock_movements.created_at', $this->date)
            ->when($this->platform, fn($q) => $q->where('platforms.name', $this->platform))
            ->when($this->transactionType === 'stock_in', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereIn('stock_movements.movement_type', ['restock', 'return', 'initial_stock'])
                         ->orWhere('stock_movements.quantity_change', '>', 0);
                });
            })
            ->when($this->transactionType === 'stock_out', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereIn('stock_movements.movement_type', ['stock_out', 'sale'])
                         ->orWhere('stock_movements.quantity_change', '<', 0);
                });
            })
            ->distinct('products.product_category_id')
            ->count('products.product_category_id');

        // Calculate hourly average
        $hoursInDay = Carbon::parse($this->date)->isToday()
            ? Carbon::now()->diffInHours(Carbon::parse($this->date)->startOfDay()) + 1
            : 24;

        $averagePerHour = $hoursInDay > 0
            ? round(($stats->total_movements ?? 0) / $hoursInDay, 1)
            : 0;

        $totalQuantity = ($stats->total_in ?? 0) + ($stats->total_out ?? 0);

        return [
            'unique_products' => $stats->unique_products ?? 0,
            'total_quantity' => $stats->total_quantity ?? 0,
            'total_movements' => $stats->total_movements ?? 0,
            'total_value' => $stats->total_value ?? 0,
            'average_value' => $stats->average_value ?? 0,
            'total_in' => $stats->total_in ?? 0,
            'total_out' => $stats->total_out ?? 0,
            'net_change' => ($stats->total_in ?? 0) - ($stats->total_out ?? 0),
            'top_reason' => $topReasonQuery->reason ?? 'N/A',
            'top_reason_count' => $topReasonQuery->count ?? 0,
            'affected_categories' => $categoriesCount,
            'average_per_hour' => $averagePerHour,
            'in_percentage' => $totalQuantity > 0 ? (($stats->total_in ?? 0) / $totalQuantity) * 100 : 0,
            'out_percentage' => $totalQuantity > 0 ? (($stats->total_out ?? 0) / $totalQuantity) * 100 : 0,
        ];
    }

    protected function getHourlyTrend(): array
    {
        $hourlyData = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->whereDate('stock_movements.created_at', $this->date)
            ->when($this->platform, fn($q) => $q->where('platforms.name', $this->platform))
            ->when($this->transactionType === 'stock_in', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereIn('stock_movements.movement_type', ['restock', 'return', 'initial_stock'])
                         ->orWhere('stock_movements.quantity_change', '>', 0);
                });
            })
            ->when($this->transactionType === 'stock_out', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereIn('stock_movements.movement_type', ['stock_out', 'sale'])
                         ->orWhere('stock_movements.quantity_change', '<', 0);
                });
            })
            ->select(
                DB::raw('HOUR(stock_movements.created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Fill in missing hours with 0
        $trend = [];
        for ($i = 0; $i < 24; $i++) {
            $trend[] = $hourlyData[$i] ?? 0;
        }

        // Return last 7 hours for a mini chart
        $currentHour = Carbon::now()->hour;
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $hour = ($currentHour - $i + 24) % 24;
            $chartData[] = $trend[$hour];
        }

        return $chartData;
    }

    protected function getNetChangeChart(): array
    {
        // Get daily net change for the past 7 days
        $endDate = Carbon::parse($this->date);
        $startDate = $endDate->copy()->subDays(6);

        $dailyData = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->whereBetween('stock_movements.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->when($this->platform, fn($q) => $q->where('platforms.name', $this->platform))
            ->select(
                DB::raw('DATE(stock_movements.created_at) as date'),
                DB::raw('SUM(stock_movements.quantity_change) as net_change')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('net_change', 'date')
            ->toArray();

        $chartData = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $chartData[] = $dailyData[$date] ?? 0;
        }

        return $chartData;
    }

    public static function canView(): bool
    {
        return false; // This widget is only used within the Stock Transactions page
    }
}
