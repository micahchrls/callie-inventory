<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExecutiveOverviewWidget;
use App\Filament\Widgets\StockPerformanceChart;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\ReorderRecommendationsWidget;
use App\Filament\Widgets\StockMovementCalendarWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Request;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Inventory Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            StockMovementCalendarWidget::class,
            ExecutiveOverviewWidget::class,
            StockPerformanceChart::class,
            RecentActivityWidget::class,
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
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    public static function isActiveWhen(): bool
    {
        // Make Dashboard active when on Dashboard or Stock Transactions page
        return Request::routeIs('filament.admin.pages.dashboard') || 
               Request::routeIs('filament.admin.pages.stock-transactions');
    }
}
