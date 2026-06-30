<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Masbug\Flysystem\GoogleDriveAdapter as BaseGoogleDriveAdapter;

class GoogleDriveAdapter extends BaseGoogleDriveAdapter
{
    public function refreshToken()
    {
        $client = $this->service->getClient();
        if ($client->isAccessTokenExpired()) {
            $client->getCache()->clear();

            // Create a custom HTTP client that disables SSL verification for local development
            $verifySsl = env('GOOGLE_DRIVE_VERIFY_SSL', true);
            $authHttp = null;
            if ($verifySsl === false || $verifySsl === 'false') {
                $authHttp = new GuzzleClient(['verify' => false]);
            }

            if ($client->isUsingApplicationDefaultCredentials()) {
                $client->fetchAccessTokenWithAssertion($authHttp);
            } else {
                $refreshToken = $client->getRefreshToken();
                if ($refreshToken) {
                    $client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $this->service = new \Google\Service\Drive($client);
                }
            }
        }
    }
}
