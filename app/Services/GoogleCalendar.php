<?php

namespace App\Services;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Directory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleCalendar
{
    public static function getClient()
    {
        $jwt = new JWT();
        $jwt::$leeway = 5; // adjust this value

        $client = new \Google\Client(['jwt' => $jwt]);
        $client->setAuthConfig(storage_path('app/google/client_secret.json'));
        $client->addScope([
            \Google\Service\Calendar::CALENDAR,
            \Google\Service\Calendar::CALENDAR_EVENTS,
            \Google_Service_Calendar::CALENDAR_EVENTS_READONLY,
            \Google_Service_Calendar::CALENDAR_READONLY,
            \Google_Service_Oauth2::OPENID,
            \Google_Service_Oauth2::USERINFO_EMAIL,
            \Google_Service_Oauth2::USERINFO_PROFILE,
        ]);
        $redirect_uri = 'http://127.0.0.1:8000/api/reception/google-calendar/auth-callback';
        $client->setRedirectUri($redirect_uri);
        // offline access will give you both an access and refresh token so that
        // your app can refresh the access token without user interaction.
        $client->setAccessType('offline');
        // Using "consent" will prompt the user for consent
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);   // incremental auth

        return $client;
    }


    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */

    public static function oauth()
    {
        $client = self::getClient();

        // Load previously authorized credentials from a file.
        $user = Auth::user();

        $credentialsPath = storage_path('app/google/' . $user->email . '_client_secret_generated.json');

        if (!file_exists($credentialsPath))
            return false;


        $accessToken = json_decode(file_get_contents($credentialsPath), true);

        $client->setAccessToken($accessToken);


        // Refresh the token if it’s expired.

        if ($client->isAccessTokenExpired()) {

            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));

        }

        return $client;

    }


    function getResource($client)
    {

        $service = new Google_Service_Calendar($client);


        // On the user's calenda print the next 10 events .

        $calendarId = 'primary';

        $optParams = array(

            'maxResults' => 10,

            'orderBy' => 'startTime',

            'singleEvents' => true,

            'timeMin' => date('c'),

        );


        $results = $service->events->listEvents($calendarId, $optParams);

        $events = $results->getItems();


        if (empty($events)) {

            print "No upcoming events found.\n";

        } else {

            print "Upcoming events:\n";

            foreach ($events as $event) {

                $start = $event->start->dateTime;

                if (empty($start)) {

                    $start = $event->start->date;

                }

                printf("%s (%s)\n", $event->getSummary(), $start);

            }

        }

    }


    public static function editResource($client, $eventId, $param)
    {
        $service = new Google_Service_Calendar($client);

        $event = $service->events->get('gregorio.grande@stl.tech', $eventId);

        if(empty($param['allDay'])){
            $event->start->setDateTime( Carbon::parse($param['start']));
            $event->end->setDateTime( Carbon::parse($param['end']));
            $event->start->setDate(null);
            $event->end->setDate(null);
        }else{
            $event->start->setDate(Carbon::parse($param['start'])->format('Y-m-d'));
            $event->end->setDate(Carbon::parse($param['end'])->format('Y-m-d'));
            $event->start->setDateTime( null);
            $event->end->setDateTime( null);
        }
        $event->start->setTimeZone('Europe/Amsterdam');
        $event->end->setTimeZone('Europe/Amsterdam');


        $updatedEvent = $service->events->update('primary', $event->getId(), $event);

        $updatedEvent->getUpdated();
    }

    public static function deletedResource($client, $event_id)
    {
        $service = new Google_Service_Calendar($client);
        $service->events->delete('primary', $event_id);
    }

    public static function getResources($client,$filter = null)
    {

        $service = new Google_Service_Calendar($client);


        // On the user’s calenda print the next 10 events .

        $calendarId = [
            ['label'=>'Gregorio Grande', 'id'=>'gregorio.grande@stl.tech'],
            ['label'=>'Commerciale', 'id'=>'sterlite.com_188espiaif2riib0jmt4vkocrfgbk6gb6sp38e1m6co3ge9p70@resource.calendar.google.com']
        ];

        $date_expiration = date('Y-m-d', strtotime("+60 days"));
        $start = date('Y-m',strtotime($filter->start));

        $optParams = array(

            'maxResults' => 1000,

            'orderBy' => 'startTime',

            'singleEvents' => true,

            'timeMin' => $start.'-01T00:00:00.000Z',

        );
        $r_events = [];
        foreach ($calendarId as $val){
            $results = $service->events->listEvents($val['id'], $optParams);

            $events = $results->getItems();


            if (empty($events)) {
                Log::channel('stderr')->info('No upcoming events found');

            } else {
                foreach ($events as $event) {

                    $statTime = false;
                    $endTime = false;
                    $start = $event->start->dateTime;
                    $end = $event->end->dateTime;

                    if (empty($start)){
                        $statTime = true;
                        $start = $event->start->date;
                    }
                    if (empty($end)){
                        $endTime = true;
                        $start = $event->end->date;
                    }

                    //Log::channel('stderr')->info($event->attendees);
                    $partecipanti = [];
                    foreach ($event->attendees as $key => $guests)
                        $partecipanti[] = $guests->displayName;

                    $r_events[]=[
                        'id' => $event['id'],
                        'title' => $event->getSummary(),
                        'start' => $start,
                        'end' => $end,
                        //'url' => 'pippo',
                        'extendedProps' => ['calendar'=>$val['label'] , 'guests'=>$partecipanti, 'location'=>$event['location'], 'description' =>'' ]  ,
                        'allDay' => ($statTime && $endTime ? true:false),
                    ];


                    // Log::channel('stderr')->info($event->description);


                    //printf("%s (%s)\n", $event->getSummary(), $start);

                }

            }
        }

        return $r_events;
    }

    public static function getDateTime($dateTime, $only_date = null)
    {

        //$date = new Google_Service_Calendar_EventDateTime();

        if ($only_date) {
            $newformat = Carbon::parse($dateTime);
            //$date->setDate($newformat);
        } else {
            $newformat = Carbon::parse($dateTime);
            //$date->setDateTime($newformat);
        }

        //$date->setTimeZone('Europe/Amsterdam');

        return $newformat;

    }

    public static function newResource($client, $param)
    {

        $service = new Google_Service_Calendar($client);

        $event = new Google_Service_Calendar_Event($param);

        $calendarId = 'primary';
        $event = $service->events->insert($calendarId, $event);
        return $event->id;

    }
}
