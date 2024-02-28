<?php

namespace App\Services;

use Carbon\Carbon;
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

        $client = new \Google\Client();
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
        Log::channel('stderr')->info($service->calendarList->listCalendarList());

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
        Log::channel('stderr')->info($param['start']);
        $event = $service->events->get('gregorio.grande@stl.tech', $eventId);
        $start = new Google_Service_Calendar_EventDateTime();
        $end = new Google_Service_Calendar_EventDateTime();
        $newstart = date('Y-m-dTH:i:00', strtotime($param['start']));
        $newend = date('Y-m-dTH:i:00', strtotime($param['end']));
        Log::channel('stderr')->info( $event->start->getDateTime());
        $start->setDateTime($newstart);
        $start->setTimeZone('Europe/Amsterdam');
        Log::channel('stderr')->info($start->getDateTime());
        $end->setDateTime($newend);
        $end->setTimeZone('Europe/Amsterdam');

        if(empty($param->all_day)){
            $event->start->setDateTime(Carbon::parse('2024-02-21T13:00:00+01:00'));
            $event->end->setDateTime($end);

        }else{
            $event->start->setDate(Carbon::parse($param['start']));
            $event->end->setDate(Carbon::parse($param['end']));

        }
        $event->start->setTimeZone('Europe/Amsterdam');
        $event->end->setTimeZone('Europe/Amsterdam');



        Log::channel('stderr')->info( 'qui');


        $updatedEvent = $service->events->update('primary', $event->getId(), $event);

        dd($updatedEvent->getUpdated());
    }

    public static function deletedResource($client, $event_id)
    {
        $service = new Google_Service_Calendar($client);
        $service->events->delete('primary', $event_id);
    }

    public static function getResources($client,$calendarIds = null)
    {

        $service = new Google_Service_Calendar($client);


        // On the user’s calenda print the next 10 events .

        $calendarId = 'gregorio.grande@stl.tech';

        $date_expiration = date('Y-m-d', strtotime("+60 days"));
        $optParams = array(

            'maxResults' => 1000,

            'orderBy' => 'startTime',

            'singleEvents' => true,

            'timeMin' => date('c',strtotime("-1 year")),

        );

        $results = $service->events->listEvents($calendarId, $optParams);

        $events = $results->getItems();

        $r_events = [];

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


                $r_events[]=[
                    'id' => $event['id'],
                    'title' => $event->getSummary(),
                    'start' => $start,
                    'end' => $end,
                    //'url' => 'pippo',
                    'extendedProps' => ['calendar' => 'Gregorio Grande', 'guests', 'location', 'description' =>$event->description ]  ,
                    'allDay' => ($statTime && $endTime ? true:false),
                ];


               // Log::channel('stderr')->info($event->description);


               //printf("%s (%s)\n", $event->getSummary(), $start);

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
