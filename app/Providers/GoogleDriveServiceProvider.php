<?php

namespace App\Providers;

use Google\Client;
use Google\Service\Drive;
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
            $client->setAuthConfig(storage_path('app/google/credentials.json'));
            $client->setAccessType('offline');

            $service = new \Google_Service_Drive($client);
            $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, null ?? '/', $options);
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
