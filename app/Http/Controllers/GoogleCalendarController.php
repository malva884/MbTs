<?php

namespace App\Http\Controllers;

use App\Jobs\RegisterNotifiche;
use App\Models\RegistroAccountWifi;
use App\Models\RpCalendarEnvent;
use App\Models\RpRegisterLog;
use App\Models\RpRegisterNotification;
use App\Models\User;
use App\Services\GoogleCalendar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        $tokeninfo = $client->verifyIdToken($accessToken['id_token']);


        $credentialsPath = storage_path('app/google/'.$tokeninfo['email'].'_client_secret_generated.json');

        //Exchange authorization code for an access token .

        //$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

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
        $attendees = [
            ['email' => 'portale.metallurgica@stl.tech','responseStatus' => 'accepted']
        ];
        foreach ($request['extendedProps']['guests'] as $user){
            $attendees[] = ['email' => $user,'responseStatus' => 'accepted'];
        }

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
            'attendees' => $attendees
        );

        $client = GoogleCalendar::oauth();

        $eventId = GoogleCalendar::newResource($client, $param);

        $event = new RpCalendarEnvent();
        $event->titolo = $request->title;
        $event->data_inizio = $request->get('start');
        $event->data_fine = $request->get('end');
        $event->evento_id = $eventId;
        $event->save();

        $esterni = $request->extendedProps['esterni'];
        foreach ($esterni as $esterno){
            $obj = new RpRegisterLog();
            $obj->cod_riferimento = $esterno['id'];
            $obj->user = Auth::id();
            $obj->evento_id = $event->id;
            $obj->nome = ucwords(strtolower($esterno['nome']));
            $obj->email = strtolower($esterno['email']);
            $username = explode("@", $obj->email);
            $obj->username_wifi = $username[0];
            $obj->password_wifi = Str::password(8, true, true, false, false);
            $obj->azienda = '';
            $obj->wifi = true;
            $obj->data_prevista = $request->get('start');
            $obj->data_scadenza = $request->get('end');
            $obj->email = $esterno['email'];
            $obj->save();
            RegistroAccountWifi::create($obj->nome, $obj->email, $obj->username_wifi, $obj->password_wifi, $obj->azienda,  $obj->data_prevista, $obj->data_scadenza, $obj->user, $obj->id);
            foreach ($request['extendedProps']['guests'] as $user){
                $user = User::all()->where('email',$user)->first();
                $userIntero = new RpRegisterNotification();
                $userIntero->user = $user->id;
                $userIntero->register_id = $obj->id;
                $userIntero->cod_riferimento = $obj->cod_riferimento;
                $userIntero->save();
            }

            RegisterNotifiche::dispatch();
        }

        return $eventId;
    }

    public function editEvent(Request $request){

        $client = GoogleCalendar::oauth();

        GoogleCalendar::editResource($client, $request->id,$request);
    }

    public function getResources(Request $request){

        // Get the authorized client object and fetch the resources.

        $client = GoogleCalendar::oauth();
        //Log::channel('stderr')->info(GoogleCalendar::getResources($client));
        return GoogleCalendar::getResources($client,$request);

    }

    public function test(){
        Log::channel('stderr')->info('FUNZIONA');

        return view('/emails/email_test', ['image'=>'']);

    }
}
