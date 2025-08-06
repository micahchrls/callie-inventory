<?php

namespace App\Filament\Widgets;

use App\Models\StockMovement;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class StockMovementCalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.stock-movement-calendar';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    // Role-based access control - accessible to users with stock movements view permission
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->can('stock.movements.view');
    }

    public $currentMonth;
    public $currentYear;
    public $calendarData = [];

    public function mount(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->loadCalendarData();
    }

    public function loadCalendarData(): void
    {
        $startDate = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get stock movements for the date range
        $movements = StockMovement::with(['productVariant.platform'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('quantity_change', '<', 0) // Only stock outs
            ->get()
            ->groupBy(function ($movement) {
                return $movement->created_at->format('Y-m-d');
            });

        \Log::info("Movements: ", $movements->toArray());

        // Build calendar data
        $this->calendarData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $dayMovements = $movements->get($dateKey, collect());

            // Group stock outs by platform
            $platformStockOuts = $dayMovements->groupBy(function ($movement) {
                return $movement->productVariant?->platform?->name ?? 'Unknown';
            });

            // Calculate stock out quantities per platform
            $platformData = [];
            foreach ($platformStockOuts as $platformName => $movements) {
                $platformData[$platformName] = $movements->sum(function ($movement) {
                    return abs($movement->quantity_change);
                });
            }

            \Log::info("Platform Data: ", $platformData);

            $this->calendarData[$dateKey] = [
                'date' => $current->copy(),
                'platform_stock_outs' => $platformData,
                'total_stock_out' => array_sum($platformData),
            ];

            $current->addDay();
        }
    }

    // Navigation Methods
    public function previousPeriod(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->loadCalendarData();
    }

    public function nextPeriod(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->loadCalendarData();
    }

    public function goToToday(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->loadCalendarData();
    }

    // Display Helper Methods
    public function getCurrentPeriodTitle(): string
    {
        return Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F Y');
    }

    public function getCalendarWeeks(): array
    {
        $firstDay = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $startOfCalendar = $firstDay->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfMonth = $firstDay->copy()->endOfMonth();
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        $weeks = [];
        $current = $startOfCalendar->copy();

        while ($current <= $endOfCalendar) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateKey = $current->format('Y-m-d');
                $week[] = [
                    'date' => $current->copy(),
                    'is_current_month' => $current->month === $this->currentMonth,
                    'is_today' => $current->isToday(),
                    'data' => $this->calendarData[$dateKey] ?? [
                        'date' => $current->copy(),
                        'platform_stock_outs' => [],
                        'total_stock_out' => 0,
                    ]
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    public function getViewModes(): array
    {
        return [
            'month' => [
                'label' => 'Month',
                'icon' => '🗓️'
            ]
        ];
    }
}
