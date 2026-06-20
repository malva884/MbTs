<?php

namespace App\Console\Commands;


use App\Models\PrMovement;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Revolution\Google\Sheets\Facades\Sheets;


class ImportMovement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import_movement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'importazione movimenti magazino.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '3048M');
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);
        //$files = GoogleDrive::search('0AARdHHHpnqtAUk9PVA','google','file','Movimenti.XLSX',false);
        $rows = Sheets::spreadsheet('1tG7jX5HD18y1CqONTgGH62fVjRaraEHYg3vCJWLrXCQ')->sheet('Sheet1')->all();
        //Log::channel('stderr')->info($rows);
        $i = 1;
        //Log::channel('stderr')->info(count($rows));
        $time = strtotime('2026-03-31');
        foreach ($rows as $row) {

            if($i == 1){
                $i++;
                continue;
            }

            if(empty($row[0]))
                continue;

            $data_inserimento = strtotime($row[13]);

            if (!empty($row[0]) && $data_inserimento >= $time) {
                $obj = New PrMovement();
                $obj->materiale = $row[0];
                $obj->descrizione = $row[1];
                $quantita = str_replace(',','.', str_replace('.','',$row[2]));
                $obj->quantita = (strpos($quantita, ".") === FALSE ?  $quantita.'.000':$quantita);
                $import = str_replace(',','.',str_replace('.','',$row[3]));
                $obj->importo = (strpos($import, ".") === FALSE ?  $import.'.00':$import);
                $obj->um = $row[4];
                $obj->lotto = $row[5];
                $obj->plant = $row[6];
                $obj->posizione_archiviazione = $row[7];
                $obj->tipo_movimento = $row[8];
                $obj->special_stock = $row[9];
                $obj->documento_materiale = $row[10];
                $data_pubblicazione = explode("/", $row[11]);
                $obj->data_pubblicazione = $data_pubblicazione[2] . '-' . $data_pubblicazione[0] . '-' . $data_pubblicazione[1];
                $data_documento = explode("/", $row[12]);
                $obj->data_documento = $data_documento[2] . '-' . $data_documento[0] . '-' . $data_documento[1];
                $data_inserimento = explode("/", $row[13]);
                $obj->data_inserimento = $data_inserimento[2] . '-' . $data_inserimento[0] . '-' . $data_inserimento[1];
                $obj->testo_movimento = $row[14];
                $obj->user = $row[15];
                $obj->save();

            }
            $i++;
        }
    }
}
