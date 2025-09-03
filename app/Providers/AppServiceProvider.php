<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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

        // Listen for login events and update last_login_at timestamp
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                // Regenerate session ID to prevent session fixation attacks
                request()->session()->regenerate();

                $event->user->updateLastLogin();

                // Log successful login for security monitoring
                Log::info('User logged in successfully', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });

        // Listen for logout events for security logging
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user instanceof User) {
                // Log logout for security monitoring
                Log::info('User logged out', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                // Invalidate the session completely
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }
        });
    }
}
