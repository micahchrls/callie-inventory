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
    public $currentDate; // For day and week views
    public $viewMode = 'month'; // 'day', 'week', 'month'
    public $calendarData = [];

    public function mount(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->currentDate = now()->startOfDay();
        $this->loadCalendarData();
    }

    public function loadCalendarData(): void
    {
        switch ($this->viewMode) {
            case 'day':
                $this->loadDayData();
                break;
            case 'week':
                $this->loadWeekData();
                break;
            case 'month':
            default:
                $this->loadMonthData();
                break;
        }
    }

    private function loadMonthData(): void
    {
        $startDate = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $this->loadMovementsForRange($startDate, $endDate);
    }

    private function loadWeekData(): void
    {
        $startDate = $this->currentDate->copy()->startOfWeek(Carbon::SUNDAY);
        $endDate = $startDate->copy()->endOfWeek(Carbon::SATURDAY);

        $this->loadMovementsForRange($startDate, $endDate);
    }

    private function loadDayData(): void
    {
        $startDate = $this->currentDate->copy()->startOfDay();
        $endDate = $startDate->copy()->endOfDay();

        $this->loadMovementsForRange($startDate, $endDate);
    }

    private function loadMovementsForRange(Carbon $startDate, Carbon $endDate): void
    {
        // Get stock movements for the date range
        $movements = StockMovement::with(['productVariant.product'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($movement) {
                return $movement->created_at->format('Y-m-d');
            });

        // Build calendar data
        $this->calendarData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $dayMovements = $movements->get($dateKey, collect());

            // Calculate movement statistics
            $restockCount = $dayMovements->where('movement_type', 'restock')->count();
            $adjustmentCount = $dayMovements->where('movement_type', 'adjustment')->count();
            $damageCount = $dayMovements->where('movement_type', 'damage')->count();
            $totalMovements = $dayMovements->count();

            // Determine day intensity for visual effects
            $intensity = $this->calculateIntensity($totalMovements);
            $primaryType = $this->getPrimaryMovementType($dayMovements);

            $this->calendarData[$dateKey] = [
                'date' => $current->copy(),
                'movements' => $dayMovements,
                'total_movements' => $totalMovements,
                'restock_count' => $restockCount,
                'adjustment_count' => $adjustmentCount,
                'damage_count' => $damageCount,
                'intensity' => $intensity,
                'primary_type' => $primaryType,
                'positive_movements' => $restockCount, // Positive stock changes
                'negative_movements' => $damageCount, // Negative stock changes
                'neutral_movements' => $adjustmentCount, // Neutral adjustments
            ];

            $current->addDay();
        }
    }

    private function calculateIntensity(int $totalMovements): string
    {
        if ($totalMovements === 0) return 'none';
        if ($totalMovements <= 2) return 'low';
        if ($totalMovements <= 5) return 'medium';
        if ($totalMovements <= 10) return 'high';
        return 'very-high';
    }

    private function getPrimaryMovementType($movements): ?string
    {
        if ($movements->isEmpty()) return null;

        $typeCounts = $movements->groupBy('movement_type')->map->count();
        return $typeCounts->keys()->first(); // Get the most frequent type
    }

    // View Mode Controls
    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['day', 'week', 'month'])) {
            $this->viewMode = $mode;
            $this->loadCalendarData();
        }
    }

    // Navigation Methods
    public function previousPeriod(): void
    {
        switch ($this->viewMode) {
            case 'day':
                $this->currentDate = $this->currentDate->copy()->subDay();
                break;
            case 'week':
                $this->currentDate = $this->currentDate->copy()->subWeek();
                break;
            case 'month':
                $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
                $this->currentMonth = $date->month;
                $this->currentYear = $date->year;
                break;
        }
        $this->loadCalendarData();
    }

    public function nextPeriod(): void
    {
        switch ($this->viewMode) {
            case 'day':
                $this->currentDate = $this->currentDate->copy()->addDay();
                break;
            case 'week':
                $this->currentDate = $this->currentDate->copy()->addWeek();
                break;
            case 'month':
                $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
                $this->currentMonth = $date->month;
                $this->currentYear = $date->year;
                break;
        }
        $this->loadCalendarData();
    }

    public function goToToday(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->currentDate = now()->startOfDay();
        $this->loadCalendarData();
    }

    // Display Helper Methods
    public function getCurrentPeriodTitle(): string
    {
        switch ($this->viewMode) {
            case 'day':
                return $this->currentDate->format('l, F j, Y');
            case 'week':
                $startOfWeek = $this->currentDate->copy()->startOfWeek(Carbon::SUNDAY);
                $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SATURDAY);

                if ($startOfWeek->month === $endOfWeek->month) {
                    return $startOfWeek->format('F j') . ' - ' . $endOfWeek->format('j, Y');
                } else {
                    return $startOfWeek->format('M j') . ' - ' . $endOfWeek->format('M j, Y');
                }
            case 'month':
            default:
                return Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F Y');
        }
    }

    public function getCalendarWeeks(): array
    {
        if ($this->viewMode === 'day') {
            return $this->getDayView();
        } elseif ($this->viewMode === 'week') {
            return $this->getWeekView();
        } else {
            return $this->getMonthView();
        }
    }

    private function getDayView(): array
    {
        $dateKey = $this->currentDate->format('Y-m-d');
        $dayData = $this->calendarData[$dateKey] ?? [
            'date' => $this->currentDate->copy(),
            'movements' => collect(),
            'total_movements' => 0,
            'restock_count' => 0,
            'adjustment_count' => 0,
            'damage_count' => 0,
            'intensity' => 'none',
            'primary_type' => null,
            'positive_movements' => 0,
            'negative_movements' => 0,
            'neutral_movements' => 0,
        ];

        return [
            'mode' => 'day',
            'data' => $dayData
        ];
    }

    private function getWeekView(): array
    {
        $startOfWeek = $this->currentDate->copy()->startOfWeek(Carbon::SUNDAY);
        $week = [];

        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);
            $dateKey = $currentDay->format('Y-m-d');

            $week[] = [
                'date' => $currentDay,
                'is_today' => $currentDay->isToday(),
                'data' => $this->calendarData[$dateKey] ?? [
                    'date' => $currentDay,
                    'movements' => collect(),
                    'total_movements' => 0,
                    'restock_count' => 0,
                    'adjustment_count' => 0,
                    'damage_count' => 0,
                    'intensity' => 'none',
                    'primary_type' => null,
                    'positive_movements' => 0,
                    'negative_movements' => 0,
                    'neutral_movements' => 0,
                ]
            ];
        }

        return [
            'mode' => 'week',
            'data' => $week
        ];
    }

    private function getMonthView(): array
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
                        'movements' => collect(),
                        'total_movements' => 0,
                        'restock_count' => 0,
                        'adjustment_count' => 0,
                        'damage_count' => 0,
                        'intensity' => 'none',
                        'primary_type' => null,
                        'positive_movements' => 0,
                        'negative_movements' => 0,
                        'neutral_movements' => 0,
                    ]
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return [
            'mode' => 'month',
            'data' => $weeks
        ];
    }

    public function getMovementTypeConfig(): array
    {
        return [
            'restock' => [
                'label' => 'Restocks',
                'icon' => '📦',
                'color' => 'emerald',
                'description' => 'Stock replenishments'
            ],
            'adjustment' => [
                'label' => 'Adjustments',
                'icon' => '⚖️',
                'color' => 'amber',
                'description' => 'Inventory corrections'
            ],
            'damage' => [
                'label' => 'Damages',
                'icon' => '⚠️',
                'color' => 'red',
                'description' => 'Damaged inventory'
            ]
        ];
    }

    public function getViewModes(): array
    {
        return [
            'day' => [
                'label' => 'Day',
                'icon' => '📅'
            ],
            'week' => [
                'label' => 'Week',
                'icon' => '📆'
            ],
            'month' => [
                'label' => 'Month',
                'icon' => '🗓️'
            ]
        ];
    }
}
