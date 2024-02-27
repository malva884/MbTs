<?php

namespace App\Services;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Directory;
use Illuminate\Support\Facades\Log;

class GoogleCalendar
{
    public static function getClient()
    {

        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google/client_secret.json'));
        $client->addScope(\Google\Service\Calendar::CALENDAR);
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

        $credentialsPath = storage_path('app/google/client_secret_generated.json');

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

        $event = $service->events->get('primary', $eventId);
        $start = new Google_Service_Calendar_EventDateTime();

        $newformat = date('Y-m-d\TH:i:s', strtotime('2023-11-10 13:00:00'));

        $start->setDateTime($newformat);
        $start->setTimeZone('Europe/Amsterdam');
        $event->setStart($start);

        $updatedEvent = $service->events->update('primary', $event->getId(), $event);

        dd($updatedEvent->getUpdated());
    }

    public static function deletedResource($client, $event_id)
    {
        $service = new Google_Service_Calendar($client);
        $service->events->delete('primary', $event_id);
    }

    public static function getResources($client)
    {

        $service = new Google_Service_Calendar($client);


        // On the user’s calenda print the next 10 events .

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

            print "No upcoming events found . \n";

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
