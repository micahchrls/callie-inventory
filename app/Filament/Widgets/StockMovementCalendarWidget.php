<?php

namespace App\Filament\Widgets;

use App\Models\StockMovement;
use App\Models\Platform;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class StockMovementCalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.stock-movement-calendar';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Stock Movement Calendar';

    protected static ?string $pollingInterval = '30s';

    // Properties
    public int $currentMonth;
    public int $currentYear;
    public array $calendarData = [];
    public array $platformColors = [];

    public function mount(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;

        // Initialize platform colors
        $this->initializePlatformColors();

        // Load initial calendar data
        $this->loadCalendarData();
    }

    protected function initializePlatformColors(): void
    {
        $this->platformColors = [
            'Shopee' => ['bg' => 'orange', 'icon' => 'heroicon-o-shopping-cart'],
            'TikTok' => ['bg' => 'pink', 'icon' => 'heroicon-o-musical-note'],
            'Lazada' => ['bg' => 'blue', 'icon' => 'heroicon-o-building-storefront'],
        ];
    }

    public function loadCalendarData(): void
    {
        $startDate = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Fetch all stock_out movements for the month with platform information from ProductVariant
        $movements = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->join('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->select([
                DB::raw('DATE(stock_movements.created_at) as date'),
                'platforms.name as platform',
                DB::raw('COUNT(*) as stock_out_count'),
                DB::raw('SUM(ABS(stock_movements.quantity_change)) as total_quantity'),
                DB::raw('COUNT(DISTINCT stock_movements.product_variant_id) as unique_products')
            ])
            ->where('stock_movements.movement_type', 'stock_out')
            ->whereBetween('stock_movements.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy(DB::raw('DATE(stock_movements.created_at)'), 'platforms.name')
            ->orderBy('date')
            ->get();

        // Also get movements for variants without platform (if any)
        $movementsWithoutPlatform = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->select([
                DB::raw('DATE(stock_movements.created_at) as date'),
                DB::raw('COUNT(*) as stock_out_count'),
                DB::raw('SUM(ABS(stock_movements.quantity_change)) as total_quantity'),
                DB::raw('COUNT(DISTINCT stock_movements.product_variant_id) as unique_products')
            ])
            ->where('stock_movements.movement_type', 'stock_out')
            ->whereBetween('stock_movements.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereNull('product_variants.platform_id')
            ->groupBy(DB::raw('DATE(stock_movements.created_at)'))
            ->get()
            ->keyBy('date');
        // Group movements by date
        $movementsByDate = $movements->groupBy('date');

        // Build calendar data
        $this->calendarData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $dayMovements = $movementsByDate->get($dateKey, collect());
            $dayMovementWithoutPlatform = $movementsWithoutPlatform->get($dateKey);

            // Initialize platform data for this day
            $platformData = [];
            $totalQuantity = 0;
            $totalStockOuts = 0;
            $totalUniqueProducts = 0;

            // Process movements with platforms
            foreach ($dayMovements as $movement) {
                $platform = $movement->platform;

                $platformData[$platform] = [
                    'stock_out_count' => $movement->stock_out_count,
                    'quantity' => $movement->total_quantity,
                    'unique_products' => $movement->unique_products
                ];

                $totalQuantity += $movement->total_quantity;
                $totalStockOuts += $movement->stock_out_count;
                $totalUniqueProducts += $movement->unique_products;
            }

            // Add movements without platform if any
            if ($dayMovementWithoutPlatform) {
                $platformData['Unknown'] = [
                    'stock_out_count' => $dayMovementWithoutPlatform->stock_out_count,
                    'quantity' => $dayMovementWithoutPlatform->total_quantity,
                    'unique_products' => $dayMovementWithoutPlatform->unique_products
                ];

                $totalQuantity += $dayMovementWithoutPlatform->total_quantity;
                $totalStockOuts += $dayMovementWithoutPlatform->stock_out_count;
                $totalUniqueProducts += $dayMovementWithoutPlatform->unique_products;
            }

            $this->calendarData[$dateKey] = [
                'date' => $current->copy(),
                'platform_data' => $platformData,
                'total_quantity' => $totalQuantity,
                'total_stock_outs' => $totalStockOuts,
                'total_unique_products' => $totalUniqueProducts,
                'has_data' => $totalStockOuts > 0,
            ];


            $current->addDay();
        }
    }

    // Navigation Methods
    public function previousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->loadCalendarData();
    }

    public function nextMonth(): void
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
    #[Computed]
    public function currentPeriodTitle(): string
    {
        return Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F Y');
    }

    #[Computed]
    public function calendarWeeks(): array
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
                $dayData = $this->calendarData[$dateKey] ?? [
                    'date' => $current->copy(),
                    'platform_data' => [],
                    'total_quantity' => 0,
                    'total_stock_outs' => 0,
                    'total_unique_products' => 0,
                    'has_data' => false,
                ];

                $week[] = [
                    'date' => $current->copy(),
                    'dateKey' => $dateKey,
                    'is_current_month' => $current->month === $this->currentMonth,
                    'is_today' => $current->isToday(),
                    'is_weekend' => $current->isWeekend(),
                    'data' => $dayData,
                    'intensity' => $this->calculateIntensity($dayData['total_quantity'] ?? 0),
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    protected function calculateIntensity(int $quantity): string
    {
        return match(true) {
            $quantity === 0 => 'none',
            $quantity <= 50 => 'low',
            $quantity <= 150 => 'medium',
            $quantity <= 300 => 'high',
            default => 'very_high'
        };
    }

    #[Computed]
    public function monthSummary(): array
    {
        $totalQuantity = 0;
        $totalStockOuts = 0;
        $totalUniqueProducts = 0;
        $platformTotals = [];
        $daysWithActivity = 0;

        foreach ($this->calendarData as $dayData) {
            if ($dayData['has_data']) {
                $daysWithActivity++;
                $totalQuantity += $dayData['total_quantity'];
                $totalStockOuts += $dayData['total_stock_outs'];
                $totalUniqueProducts += $dayData['total_unique_products'];

                foreach ($dayData['platform_data'] as $platform => $data) {
                    if (!isset($platformTotals[$platform])) {
                        $platformTotals[$platform] = [
                            'stock_outs' => 0,
                            'quantity' => 0,
                            'products' => 0
                        ];
                    }
                    $platformTotals[$platform]['stock_outs'] += $data['stock_out_count'];
                    $platformTotals[$platform]['quantity'] += $data['quantity'];
                    $platformTotals[$platform]['products'] += $data['unique_products'];
                }
            }
        }

        return [
            'total_quantity' => $totalQuantity,
            'total_stock_outs' => $totalStockOuts,
            'total_unique_products' => $totalUniqueProducts,
            'days_with_activity' => $daysWithActivity,
            'platform_totals' => $platformTotals,
            'average_per_day' => $daysWithActivity > 0 ? round($totalStockOuts / $daysWithActivity, 1) : 0,
        ];
    }

    public function getStockoutDetailsUrl(string $date, ?string $platform = null): string
    {
        $params = ['date' => $date];

        if ($platform && $platform !== 'all') {
            $params['platform'] = $platform;
        }

        return route('filament.admin.pages.stock-transactions', $params);
    }
}
