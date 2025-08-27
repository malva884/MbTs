<?php

namespace App\Exports;

use App\Models\QtConformita;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MensaExport implements FromCollection, WithHeadings
{


    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        $path = 'https://app.metallurgicabresciana.it/turni/mb/menza/api/get.php?';
        $path.= 'time=2025-01-23';
        $getMovieList = file_get_contents($path);
        $result = json_decode($getMovieList);
        $data = [12,"Hey",123,4234,5632435,"Nope",345,345,345,345];

        return collect($data);
    }


}
