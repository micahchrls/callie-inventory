<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExecutiveOverviewWidget;
use App\Filament\Widgets\StockPerformanceChart;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\ReorderRecommendationsWidget;
use App\Filament\Widgets\StockMovementCalendarWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Inventory Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            ExecutiveOverviewWidget::class,
            StockPerformanceChart::class,
            RecentActivityWidget::class,
            StockMovementCalendarWidget::class,
            ReorderRecommendationsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getTitle(): string
    {
        return 'Callie Jewelry - Inventory Dashboard';
    }

    public function getHeading(): string
    {
        return 'Inventory Management Dashboard';
    }

    public function getSubheading(): ?string
    {
        return 'Real-time insights and analytics for your jewelry inventory';
    }
}
