<?php

namespace App\Services;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Directory;

class GoogleCalendar
{
    public function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName(config('app.name'));
        $client->setScopes(Google_Service_Directory::ADMIN_DIRECTORY_RESOURCE_CALENDAR_READONLY);
        $client->setAuthConfig(storage_path('keys/client_secret.json'));
        $client->setAccessType('offline');
        //$client->setApprovalPrompt('force');
        $client->setPrompt('consent');
        $redirect_uri = url('/google-calendar/auth-callback');
        $client->setRedirectUri($redirect_uri);
        return $client;
    }


    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */

    public function oauth()

    {

        $client = $this->getClient();


        // Load previously authorized credentials from a file.

        $credentialsPath = storage_path('keys/client_secret_generated.json');

        if (!file_exists($credentialsPath)) {

            return false;

        }


        $accessToken = json_decode(file_get_contents($credentialsPath), true);

        $client->setAccessToken($accessToken);


        // Refresh the token if it's expired.

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
}
