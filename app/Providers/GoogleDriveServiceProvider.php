<?php

namespace App\Providers;

use App\Services\GoogleDriveAdapter;
use App\Services\SettingService;
use Google\Client;
use Google\Service\Drive;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class GoogleDriveServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

        Storage::extend('google', function($app, $config) {

            $options = [];

            // Carica teamDriveId dal database usando setting_key generico
            if (!empty($config['setting_key']) && empty($config['teamDriveId'] ?? null)) {
                try {
                    $settingService = new SettingService();
                    $teamDriveId = $settingService->get($config['setting_key']);
                    if ($teamDriveId) {
                        $config['teamDriveId'] = $teamDriveId;
                    }
                } catch (\Exception $e) {
                    // Se il database non è disponibile, ignora l'errore
                }
            }

            // Carica folderId dal database usando folder_setting_key generico
            if (!empty($config['folder_setting_key']) && empty($config['folderId'] ?? null)) {
                try {
                    $settingService = new SettingService();
                    $folderId = $settingService->get($config['folder_setting_key']);
                    if ($folderId) {
                        $config['folderId'] = $folderId;
                    }
                } catch (\Exception $e) {
                    // Se il database non è disponibile, ignora l'errore
                }
            }

            if (!empty($config['teamDriveId'] ?? null))
                $options['teamDriveId'] = $config['teamDriveId'];

            $client = new Client();
            $client->setApplicationName('App Protale');
            // $client->setRedirectUri('http://127.0.0.1:8000/api/login/google/callback');
            $client->setScopes([\Google_Service_Drive::DRIVE]);

            // Try to use credentials from environment variable first
            $credentialsJson = (env('GOOGLE_CREDENTIALS_JSON_B64') ? base64_decode(env('GOOGLE_CREDENTIALS_JSON_B64')) : null)
                ?: (env('GOOGLE_SERVICE_ACCOUNT_JSON_B64') ? base64_decode(env('GOOGLE_SERVICE_ACCOUNT_JSON_B64')) : null)
                ?: env('GOOGLE_CREDENTIALS_JSON') ?: env('GOOGLE_SERVICE_ACCOUNT_JSON') ?: env('GOOGLE_DRIVE_CREDENTIALS_JSON');
            if ($credentialsJson) {
                \Log::info('Using credentials from environment variable');
                \Log::info('Credentials length: ' . strlen($credentialsJson));
                
                // Try to decode JSON first (for Coolify format with \n literals)
                $credentialsArray = json_decode($credentialsJson, true);
                
                if (!is_array($credentialsArray)) {
                    \Log::info('JSON decode failed, converting newlines');
                    // If decode fails, convert newlines to \n (for local .env with real newlines)
                    $credentialsJson = str_replace("\n", "\\n", $credentialsJson);
                    $credentialsJson = str_replace("\r", "", $credentialsJson);
                    $credentialsArray = json_decode($credentialsJson, true);
                } else {
                    \Log::info('JSON decode successful');
                }
                
                // Convert \n to actual newlines for private key (OpenSSL requires real newlines)
                if (isset($credentialsArray['private_key'])) {
                    $credentialsArray['private_key'] = str_replace('\\n', "\n", $credentialsArray['private_key']);
                    \Log::info('Private key length: ' . strlen($credentialsArray['private_key']));
                }
                $client->setAuthConfig($credentialsArray);
            } else {
                \Log::info('Using credentials from file');
                $client->setAuthConfig(storage_path('app/google/credentials.json'));
            }

            $client->setAccessType('offline');

            // Fix SSL certificate issue on local Windows development
            $verifySsl = env('GOOGLE_DRIVE_VERIFY_SSL', true);
            if ($verifySsl === false || $verifySsl === 'false') {
                $httpClient = new GuzzleClient(['verify' => false]);
                $client->setHttpClient($httpClient);
            }

            $service = new \Google_Service_Drive($client);
            $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?? null, $options);
            $driver = new \League\Flysystem\Filesystem($adapter);

            return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
