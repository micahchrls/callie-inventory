<?php

namespace App\Filament\Widgets;

use App\Models\StockOutItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TotalStockOutWidget extends ChartWidget
{
    protected static ?string $heading = 'Total Stock Out';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Get daily stock out data for current month from StockOutItem
        $stockOutData = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select([
                DB::raw('DATE(stock_outs.created_at) as date'),
                DB::raw('SUM(stock_out_items.quantity) as total_quantity'),
            ])
            ->whereBetween('stock_outs.created_at', [$currentMonth, $endOfMonth])
            ->groupBy(DB::raw('DATE(stock_outs.created_at)'))
            ->orderBy('date')
            ->get();

        // Create labels for all days in current month
        $labels = [];
        $data = [];
        $current = $currentMonth->copy();
        $dailyTotals = $stockOutData->keyBy('date');

        while ($current <= $endOfMonth && $current <= now()) {
            $dateKey = $current->format('Y-m-d');
            $labels[] = $current->format('M j');
            $data[] = $dailyTotals->get($dateKey)?->total_quantity ?? 0;
            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Stock Out Quantity',
                    'data' => $data,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)', // Red background
                    'borderColor' => 'rgb(239, 68, 68)', // Red border
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    public function getDescription(): ?string
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalStockOut = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->whereBetween('stock_outs.created_at', [$currentMonth, $endOfMonth])
            ->sum('stock_out_items.quantity');

        return "Total this month: {$totalStockOut} items";
    }
}
