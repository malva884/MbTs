<?php

namespace App\Services;
use Google\Client;
use Google\Service\Calendar;
use Google_Service_Calendar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GoogleCalendarService {
    protected $client;
    protected $calendarService;

    public function __construct() {
        $this->client = new Client();
        $this->client->setClientId(env('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
       //$this->client->addScope(Calendar::CALENDAR);
        $this->client->addScope([
            \Google\Service\Calendar::CALENDAR,
            \Google\Service\Calendar::CALENDAR_EVENTS,
            \Google_Service_Calendar::CALENDAR_EVENTS_READONLY,
            \Google_Service_Calendar::CALENDAR_READONLY,
            \Google_Service_Oauth2::OPENID,
            \Google_Service_Oauth2::USERINFO_EMAIL,
            \Google_Service_Oauth2::USERINFO_PROFILE,
            \Google_Service_Drive::DRIVE,
        ]);
        $this->calendarService = new Calendar($this->client);
    }

    public function authenticate($code) {

        $this->client->fetchAccessTokenWithAuthCode($code);
        session(['google_access_token' => $this->client->getAccessToken()]);

    }

    public function getClient()  {

        if (session('google_access_token')) {
            $this->client->setAccessToken(Session::get('google_access_token'));
        }
        else
            redirect('google/login/google/callback');
        return $this->client;
    }

    public function listEvents($filter = null)  {

        $service = new Google_Service_Calendar($this->getClient());

        // On the user’s calenda print the next 10 events .
        $filter->calendars = str_replace("[","",$filter->calendars);
        $filter->calendars = str_replace("]","",$filter->calendars);
        $filter->calendars = str_replace('"',"",$filter->calendars);
        $filter->calendars = explode(",",$filter->calendars);
        // Log::channel('stderr')->info($filter->calendars);



        $calendarId = [
            'sterlite.com_188espiaif2riib0jmt4vkocrfgbk6gb6sp38e1m6co3ge9p70@resource.calendar.google.com'=>'Commerciale',
            'sterlite.com_3830383535383937343438@resource.calendar.google.com'=>'Ghitti',
            'sterlite.com_3933323434333537393933@resource.calendar.google.com'=>'Vascelli'
        ];


        $start = date('Y-m', strtotime($filter->start)) . '-01T00:00:00.000Z';

        // On the user's calenda print the next 10 events .

        //$calendarId = 'primary';

        $optParams = array(

            //'maxResults' => 10,

            'orderBy' => 'startTime',

            'singleEvents' => true,

            'timeMin' => $start,//date('c'),

        );

/*
        $results = $service->events->listEvents($calendarId, $optParams);

        $events = $results->getItems();



        $events = $this->calendarService->events->listEvents($calendarId);
        return $events->getItems();
*/
        $r_events = [];
        foreach ($filter->calendars as $val) {

            $results = $service->events->listEvents($val, $optParams);

            $events = $results->getItems();


            if (empty($events)) {
                // Log::channel('stderr')->info('No upcoming events found: ' . $val);
            } else {
                if(empty($calendarId[$val]))
                    $calendarId[$val] = Auth::user()->full_name;
                foreach ($events as $event) {

                    $statTime = false;
                    $endTime = false;
                    $start = $event->start->dateTime;
                    $end = $event->end->dateTime;

                    if (empty($start)) {
                        $statTime = true;
                        $start = $event->start->date;
                    }
                    if (empty($end)) {
                        $endTime = true;
                        $start = $event->end->date;
                    }

                    $partecipanti = [];
                    foreach ($event->attendees as $key => $guests)
                        $partecipanti[] = $guests->displayName;

                    $r_events[] = [
                        'id' => $event['id'],
                        'title' => $event->getSummary(),
                        'start' => $start,
                        'end' => $end,
                        //'url' => 'pippo',
                        'extendedProps' => ['calendar' => $calendarId[$val], 'guests' => $partecipanti, 'location' => $event['location'], 'description' => ''],
                        'allDay' => ($statTime && $endTime ? true : false),
                    ];
                }
            }
        }

        return $r_events;
    }

    public function createEvent($eventData, $calendarId = 'primary')  {
        $this->getClient();
        $event = new \Google\Service\Calendar\Event($eventData);
        $event = $this->calendarService->events->insert($calendarId, $event);

        // You can also insert the event in DB to retrieve it from there later
        return $event;
    }
}
