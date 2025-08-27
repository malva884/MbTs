<?php

namespace App\Jobs;

use App\Models\RecipientCoordinate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SpeditoCalcoloDistanzaKm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id;

    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $rows = DB::table('fi_shipped_rows')->where('head', $this->id)->get();
        foreach ($rows as $row) {
            $coordinates = null;
            $coordinates = DB::table('recipient_coordinates')->select('id', 'cap', 'citta', 'latitudine', 'longitudine', 'km')
                ->where('cap', '=', $row->postal_code)
                ->where('citta', '=', $row->city)->first();
            $distance_km = '';

            if (empty($coordinates->id)) {
                $row->city = str_replace("&", "", $row->city);
                $streamContext = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
                $check = false;
                try {
                    $getMovieList = file_get_contents('https://api.myptv.com/geocoding/v1/locations/by-text?searchText=' . str_replace(" ", "%20", $row->city) . '%20' . $row->postal_code . '&apiKey=YmRjZDNhMjk1ZWY4NGViNGI0ZWQ5N2Q5ZGZmOWFkNzg6MTRhNzI4NTItNzM3Ny00Njg5LWI3M2YtMmRlODBhODczNWQw', false, $streamContext);
                    $movieNameList = json_decode($getMovieList);

                } catch (\Exception $e) {
                    $movieNameList = [];
                    $distance_km = '0';
                }
                if ($movieNameList) {
                    foreach ($movieNameList->locations as $location) {
                        if (strpos(str_replace(" ", "", $location->address->postalCode), str_replace(" ", "", $row->postal_code)) !== false && $check == false) {
                            $check = true;
                            $coordinates = new RecipientCoordinate();
                            $coordinates->citta = $row->city;
                            $coordinates->cap = $row->postal_code;
                            $coordinates->latitudine = $location->referencePosition->latitude;
                            $coordinates->longitudine = $location->referencePosition->longitude;
                            try {
                                //$getMovieList = file_get_contents('https://api.tomtom.com/routing/1/calculateRoute/45.41807307791388,10.08573766113273:' . $coordinates->latitude . ',' . $coordinates->longitude . '/json?key=gq6gj6iFOWlEiElYrYemOwxPpg4cmld5');
                                $getMovieList = file_get_contents('https://api.tomtom.com/routing/1/calculateRoute/45.41807307791388,10.08573766113273:' . $coordinates->latitudine . ',' . $coordinates->longitudine . '/json?key=gq6gj6iFOWlEiElYrYemOwxPpg4cmld5');
                                $movieNameList = json_decode($getMovieList);
                                $distance_km = $coordinates->km = round((int)$movieNameList->routes[0]->summary->lengthInMeters / 1000, 0);
                            } catch (\Exception $e) {
                                $distance_km = $coordinates->km = '0';
                                //Log::channel('stderr')->info('https://api.tomtom.com/routing/1/calculateRoute/45.41807307791388,10.08573766113273:' . $coordinates->latitudine . ',' . $coordinates->longitudine . '/json?key=gq6gj6iFOWlEiElYrYemOwxPpg4cmld5');

                            }
                            $coordinates->save();
                            //break;
                        }
                    }
                }
            } else
                $distance_km = $coordinates->km;


            DB::table('fi_shipped_rows')->where('id', '=', $row->id)->update([
                'km_distance' => $distance_km
            ]);

        }


    }
}
