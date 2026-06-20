<?php

namespace App\Providers;

use App\Models\QtSupplier;
use App\Observers\QtFaiObserver;
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
    }
}
