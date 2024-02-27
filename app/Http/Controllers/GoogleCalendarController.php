<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    public function connect(){

        $client = GoogleCalendar::getClient();

        $authUrl = $client->createAuthUrl();


        return response()->json($authUrl);
        //return redirect($authUrl);

    }

    public function store(Request $request){


        $client = GoogleCalendar::getClient();

        $authCode = $request->code;


        $credentialsPath = storage_path('app/google/client_secret_generated.json');

        //Exchange authorization code for an access token .

        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Store the credentials to disk.

        if (!file_exists(dirname($credentialsPath))) {

            mkdir(dirname($credentialsPath), 0700, true);

        }

        file_put_contents($credentialsPath, json_encode($accessToken));

        return redirect('/calendar/calendar')->with('message', 'Credentials saved');

    }

    public function addEvent(Request $request)
    {
        // Get the authorized client object and fetch the resources.
        Log::channel('stderr')->info('SIIIIII');



        $param = array(
            'summary' => $request->title,
            'location' => 'Metallurgica Bresciana, Dello, Italy',
            'description' => $request->description,
            'start' => array(
                'date' => (!empty($request->all_day) ? Carbon::parse($request->get('start'))->format('Y-m-d') : null),
                'dateTime' => (!empty($request->all_day) ? null : Carbon::parse($request->get('start'))),
                'timeZone' => 'Europe/Amsterdam',
            ),
            'end' => array(
                'date' => (!empty($request->all_day) ? Carbon::parse($request->get('start'))->addDay()->format('Y-m-d') : null),
                'dateTime' => (!empty($request->all_day) ? null : Carbon::parse($request->get('end'))),
                'timeZone' => 'Europe/Amsterdam',
            ),
            'attendees' => array(
                array('email' => 'portale.metallurgica@stl.tech'),
            ),
        );

        $client = GoogleCalendar::oauth();

        $eventId = GoogleCalendar::newResource($client, $param);

        //QrCode::generate($eventId);

        Log::channel('stderr')->info($eventId);
        return $eventId;


    }

    public function getResources(){

        // Get the authorized client object and fetch the resources.

        $client = GoogleCalendar::oauth();
        Log::channel('stderr')->info(GoogleCalendar::getResources($client));
        return GoogleCalendar::getResources($client);

    }

    public function test(){
        Log::channel('stderr')->info('FUNZIONA');
    }
}
