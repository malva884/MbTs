<?php

namespace App\Providers;

use App\Models\QtSupplier;
use App\Observers\QtFaiObserver;
use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;
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
        \App\Models\QtSupplier::observe(\App\Observers\SupplierObserver::class);
        \App\Models\QtFai::observe(\App\Observers\QtFaiObserver::class);

        // Configure commesse_drive disk dynamically from settings
        Storage::extend('commesse_drive', function ($app, $config) {
            $settingService = new SettingService();
            $folderId = $settingService->get('google_drive_commesse_folder_id');

            if ($folderId) {
                $config['folderId'] = $folderId;
            }

            return Storage::createFilesystem($app, $config);
        });

        // Configure documenti_drive disk dynamically from settings
        Storage::extend('documenti_drive', function ($app, $config) {
            $settingService = new SettingService();
            $folderId = $settingService->get('google_drive_documenti_folder_id');

            if ($folderId) {
                $config['folderId'] = $folderId;
            }

            return Storage::createFilesystem($app, $config);
        });
    }
}
