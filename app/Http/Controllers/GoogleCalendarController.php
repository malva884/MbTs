<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    public function connect(){
        $client = GoogleCalendar::getClient();

        $authUrl = $client->createAuthUrl();
        Log::channel('stderr')->info($authUrl);

        return response()->json($authUrl);
        //return redirect($authUrl);

    }

    public function store(){
        Log::channel('stderr')->info(request());
        $client = GoogleCalendar::getClient();

        $authCode = request('code');


        $credentialsPath = storage_path('client_secret.json');

        //Exchange authorization code for an access token .

        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Store the credentials to disk.

        if (!file_exists(dirname($credentialsPath))) {

            mkdir(dirname($credentialsPath), 0700, true);

        }

        file_put_contents($credentialsPath, json_encode($accessToken));

        return redirect('/google-calendar')->with('message', 'Credentials saved');

    }

    public function getResources(){

        // Get the authorized client object and fetch the resources.

        $client = GoogleCalendar::oauth();

        return GoogleCalendar::getResources($client);

    }

    public function test(){
        Log::channel('stderr')->info('FUNZIONA');
    }
}
