<?php

namespace App\Filament\Widgets;

use App\Models\StockIn;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TotalStockInWidget extends ChartWidget
{
    protected static ?string $heading = 'Total Stock In';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Get daily stock in data for current month
        $stockInData = StockIn::select([
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_quantity) as total_quantity'),
        ])
            ->whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Create labels for all days in current month
        $labels = [];
        $data = [];
        $current = $currentMonth->copy();
        $dailyTotals = $stockInData->keyBy('date');

        while ($current <= $endOfMonth && $current <= now()) {
            $dateKey = $current->format('Y-m-d');
            $labels[] = $current->format('M j');
            $data[] = $dailyTotals->get($dateKey)?->total_quantity ?? 0;
            $current->addDay();
        }

        $totalStockIn = array_sum($data);

        return [
            'datasets' => [
                [
                    'label' => 'Stock In Quantity',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)', // Green background
                    'borderColor' => 'rgb(34, 197, 94)', // Green border
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

        $totalStockIn = StockIn::whereBetween('created_at', [$currentMonth, $endOfMonth])
            ->sum('total_quantity');

        return "Total this month: {$totalStockIn} items";
    }
}
