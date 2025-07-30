<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Filament\Facades\Filament;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Configure Filament to serve from root URL instead of /admin
        Filament::serving(function () {
            if (Filament::getCurrentPanel()) {
                Filament::getCurrentPanel()->path('');
            }
        });
    }
}
