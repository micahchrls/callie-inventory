<?php

namespace App\Filament\Widgets;

use App\Models\StockOut;
use App\Models\StockIn;
use App\Models\StockOutItem;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class StockMovementCalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.stock-movement-calendar';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

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
            'TikTok' => ['bg' => 'pink', 'icon' => 'heroicon-o-musical-note'],
            'Shopee' => ['bg' => 'orange', 'icon' => 'heroicon-o-shopping-cart'],
            'Bazar' => ['bg' => 'blue', 'icon' => 'heroicon-o-building-storefront'],
            'Others' => ['bg' => 'gray', 'icon' => 'heroicon-o-building-office'],
            'Restock' => ['bg' => 'green', 'icon' => 'heroicon-o-arrow-up-circle'],
        ];
    }

    public function loadCalendarData(): void
    {
        $startDate = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Fetch platform-specific stock out data from StockOutItem
        $stockOutData = StockOutItem::query()
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->select([
                DB::raw('DATE(stock_outs.created_at) as date'),
                'stock_out_items.platform',
                DB::raw('SUM(stock_out_items.quantity) as total_quantity'),
                DB::raw('COUNT(stock_out_items.id) as item_count'),
                DB::raw('COUNT(DISTINCT stock_outs.product_variant_id) as unique_products'),
            ])
            ->whereBetween('stock_outs.created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy(DB::raw('DATE(stock_outs.created_at)'), 'stock_out_items.platform')
            ->get()
            ->groupBy('date');

        // Fetch stock in data categorized by reason
        $stockInData = StockIn::query()
            ->select([
                DB::raw('DATE(created_at) as date'),
                'reason',
                DB::raw('SUM(total_quantity) as total_quantity'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('COUNT(DISTINCT product_variant_id) as unique_products'),
            ])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy(DB::raw('DATE(created_at)'), 'reason')
            ->get()
            ->groupBy('date');

        // Build calendar data
        $this->calendarData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $dayStockOuts = $stockOutData->get($dateKey, collect());
            $dayStockIns = $stockInData->get($dateKey, collect());

            // Initialize platform data for this day
            $platformData = [
                'tiktok_stock_out' => 0,
                'shopee_stock_out' => 0,
                'bazar_stock_out' => 0,
                'others_stock_out' => 0,
                'restock_stock_in' => 0,
                'others_stock_in' => 0,
            ];

            $totalStockOut = 0;
            $totalStockIn = 0;
            $uniqueProducts = 0;

            // Process stock out data by platform
            foreach ($dayStockOuts as $stockOut) {
                $platform = strtolower($stockOut->platform);
                $quantity = $stockOut->total_quantity;

                switch ($platform) {
                    case 'tiktok':
                        $platformData['tiktok_stock_out'] += $quantity;
                        break;
                    case 'shopee':
                        $platformData['shopee_stock_out'] += $quantity;
                        break;
                    case 'bazar':
                        $platformData['bazar_stock_out'] += $quantity;
                        break;
                    case 'others':
                    default:
                        $platformData['others_stock_out'] += $quantity;
                        break;
                }

                $totalStockOut += $quantity;
                $uniqueProducts += $stockOut->unique_products;
            }

            // Process stock in data by reason
            foreach ($dayStockIns as $stockIn) {
                $reason = strtolower($stockIn->reason ?? '');
                $quantity = $stockIn->total_quantity;

                // Categorize stock in by reason
                if (str_contains($reason, 'restock') || str_contains($reason, 'purchase') || str_contains($reason, 'supplier')) {
                    $platformData['restock_stock_in'] += $quantity;
                } elseif (str_contains($reason, 'return') && str_contains($reason, 'callie')) {
                    $platformData['others_stock_in'] += $quantity;
                } else {
                    // Default to restock for unspecified reasons
                    $platformData['restock_stock_in'] += $quantity;
                }

                $totalStockIn += $quantity;
                $uniqueProducts += $stockIn->unique_products;
            }

            $this->calendarData[$dateKey] = [
                'date' => $current->copy(),
                'platform_data' => $platformData,
                'total_stock_out' => $totalStockOut,
                'total_stock_in' => $totalStockIn,
                'total_unique_products' => $uniqueProducts,
                'has_data' => ($totalStockOut > 0 || $totalStockIn > 0),
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
                    'total_stock_out' => 0,
                    'total_stock_in' => 0,
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
                    'intensity' => $this->calculateIntensity(($dayData['total_stock_out'] ?? 0) + ($dayData['total_stock_in'] ?? 0)),
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    protected function calculateIntensity(int $quantity): string
    {
        return match (true) {
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
        $totalStockOut = 0;
        $totalStockIn = 0;
        $totalUniqueProducts = 0;
        $platformTotals = [
            'tiktok_stock_out' => 0,
            'shopee_stock_out' => 0,
            'bazar_stock_out' => 0,
            'others_stock_out' => 0,
            'restock_stock_in' => 0,
            'others_stock_in' => 0,
        ];
        $daysWithActivity = 0;

        foreach ($this->calendarData as $dayData) {
            if ($dayData['has_data']) {
                $daysWithActivity++;
                $totalStockOut += $dayData['total_stock_out'];
                $totalStockIn += $dayData['total_stock_in'];
                $totalUniqueProducts += $dayData['total_unique_products'];

                foreach ($platformTotals as $key => $value) {
                    $platformTotals[$key] += $dayData['platform_data'][$key] ?? 0;
                }
            }
        }

        return [
            'total_stock_out' => $totalStockOut,
            'total_stock_in' => $totalStockIn,
            'total_unique_products' => $totalUniqueProducts,
            'days_with_activity' => $daysWithActivity,
            'platform_totals' => $platformTotals,
            'average_stock_out_per_day' => $daysWithActivity > 0 ? round($totalStockOut / $daysWithActivity, 1) : 0,
            'average_stock_in_per_day' => $daysWithActivity > 0 ? round($totalStockIn / $daysWithActivity, 1) : 0,
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
