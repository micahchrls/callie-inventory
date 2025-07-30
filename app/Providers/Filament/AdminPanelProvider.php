<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ExecutiveOverviewWidget;
use App\Filament\Widgets\StockPerformanceChart;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\ReorderRecommendationsWidget;
use App\Filament\Widgets\StockMovementCalendarWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandLogo(asset('app-logo.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('app-logo.png'))
            ->colors([
                'primary' => [
                    50 => '#fdf9f7',
                    100 => '#f3dbd3',
                    200 => '#e7b7a7',
                    300 => '#d59a8f',
                    400 => '#cc867a',
                    500 => '#ac353c',
                    600 => '#9a2f34',
                    700 => '#87282d',
                    800 => '#752125',
                    900 => '#621b1e',
                    950 => '#4f1518',
                ],
                'gray' => [
                    50 => '#fafafa',
                    100 => '#f4f4f5',
                    200 => '#e4e4e7',
                    300 => '#d4d4d8',
                    400 => '#a1a1aa',
                    500 => '#71717a',
                    600 => '#52525b',
                    700 => '#3f3f46',
                    800 => '#27272a',
                    900 => '#18181b',
                    950 => '#09090b',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class, // Use our custom dashboard
            ])
            ->navigationGroups([
                'Inventory Management',
                'Product Management',
                'Reports',
                'User Management',
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Dashboard widgets
                ExecutiveOverviewWidget::class,
                StockPerformanceChart::class,
                RecentActivityWidget::class,
                ReorderRecommendationsWidget::class,
                StockMovementCalendarWidget::class,
                // Default widgets
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
