<?php

namespace App\Providers;

use App\Services\GoogleDriveAdapter;
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

            if (!empty($config['teamDriveId'] ?? null))
                $options['teamDriveId'] = $config['teamDriveId'];

            $client = new Client();
            $client->setApplicationName('App Protale');
            // $client->setRedirectUri('http://127.0.0.1:8000/api/login/google/callback');
            $client->setScopes([\Google_Service_Drive::DRIVE]);
            
            // Try to use credentials from environment variable first
            $credentialsJson = env('GOOGLE_DRIVE_CREDENTIALS_JSON');
            if ($credentialsJson) {
                $client->setAuthConfig(json_decode($credentialsJson, true));
            } else {
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
            $adapter = new GoogleDriveAdapter($service, null ?? '/', $options);
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
