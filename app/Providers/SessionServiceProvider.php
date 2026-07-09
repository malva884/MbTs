<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SettingService;
use Illuminate\Support\Facades\Config;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            $settingService = new SettingService();
            $sessionLifetime = $settingService->get('session_lifetime', 120);
            Config::set('session.lifetime', (int) $sessionLifetime);
        } catch (\Exception $e) {
            // Fallback to default if database is not available
            Config::set('session.lifetime', 120);
        }
    }
}
