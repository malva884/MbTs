<?php

namespace App\Console\Commands;

use App\Models\ToCable;
use App\Models\ToCableStructure;
use App\Models\ToCategory;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Revolution\Google\Sheets\Facades\Sheets;


class ImportCavi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import_cavi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report Checker settimanle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);

        $cavi = DB::connection('mysql_old')->table('cables')->get();
        foreach ($cavi as $cavo) {
            $categoria = ToCategory::where('categoria', $cavo->category_name)->first();
            $obj_cavo = new ToCable();
            $obj_cavo->codice = $cavo->name;
            $obj_cavo->descrizione = $cavo->description;
            $obj_cavo->categoria = $categoria->categoria;
            $obj_cavo->categoria_id = $categoria->id;
            $obj_cavo->save();

            $strutturaCavo = DB::connection('mysql_old')->table('cable_structures')->where('cable', $cavo->id)->orderby('position','asc')->get();
            $i = 1;
            foreach ($strutturaCavo as $row) {
                $strutturaObj = new ToCableStructure();
                $strutturaObj->cavo_id = $obj_cavo->id;
                $strutturaObj->centro = $row->centro;
                $strutturaObj->materiale = $row->cod;
                $strutturaObj->descrizione = $row->description;
                $strutturaObj->nota = $row->note;
                if (!is_null($row->diametro))
                    $strutturaObj->diametro = $row->diametro;
                if (!is_null($row->peso))
                    $strutturaObj->peso = $row->peso;
                if (!is_null($row->ordinata))
                    $strutturaObj->ordinata = $row->ordinata;
                if (!is_null($row->position)){
                    $strutturaObj->posizione = $row->position;
                }
                else{
                    $strutturaObj->posizione = $i;
                }
                if (!is_null($row->n_el))
                    $strutturaObj->elementi = $row->n_el;
                $strutturaObj->save();

                $i++;
            }

        }
    }
}
