<?php

namespace App\Providers;

use App\Models\QtSupplier;
use App\Observers\QtFaiObserver;
use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Revolution\Google\Sheets\GoogleSheetClient;

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
        // Ensure the Sheets package's Google client uses the service account credentials.
        // The package's default singleton can be created with an empty/missing
        // credentials_json, causing it to fall back to Application Default Credentials.
        $this->app->singleton(GoogleSheetClient::class, function ($app) {
            $config = $app['config']['google'];
            $client = new GoogleSheetClient($config);

            // Load credentials with the same robust logic used by GoogleDriveServiceProvider
            $credentialsJson = (env('GOOGLE_CREDENTIALS_JSON_B64') ? base64_decode(env('GOOGLE_CREDENTIALS_JSON_B64')) : null)
                ?: (env('GOOGLE_SERVICE_ACCOUNT_JSON_B64') ? base64_decode(env('GOOGLE_SERVICE_ACCOUNT_JSON_B64')) : null)
                ?: env('GOOGLE_CREDENTIALS_JSON')
                ?: env('GOOGLE_SERVICE_ACCOUNT_JSON')
                ?: env('GOOGLE_DRIVE_CREDENTIALS_JSON');

            if ($credentialsJson) {
                $credentialsArray = json_decode($credentialsJson, true);

                if (! is_array($credentialsArray)) {
                    // Raw env may contain real newlines instead of \n escapes
                    $credentialsJson = str_replace("\n", "\\n", $credentialsJson);
                    $credentialsJson = str_replace("\r", '', $credentialsJson);
                    $credentialsArray = json_decode($credentialsJson, true);
                }

                if (is_array($credentialsArray) && isset($credentialsArray['private_key'])) {
                    // OpenSSL requires real newlines in the private key
                    $credentialsArray['private_key'] = str_replace('\\n', "\n", $credentialsArray['private_key']);
                    $client->getClient()->setAuthConfig($credentialsArray);
                }
            }

            return $client;
        });

        \App\Models\QtSupplier::observe(\App\Observers\SupplierObserver::class);
        \App\Models\QtFai::observe(\App\Observers\QtFaiObserver::class);
    }
}
