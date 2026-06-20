<?php

namespace App\Jobs;

use App\Models\PrWarehouseBi;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Revolution\Google\Sheets\Facades\Sheets;

class ImportMagazzinoBi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $id;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cat = [
            '-FIBER-' => 'Fiber Optic OFC',
            '-JACK-' => 'Finished Product OFC',
            '-SF-' => 'Finished Product OFC',
            '-COPPERCABLE-' => 'Finished Product CC',
            '-BOB-' => 'Packaging',
            '-RAWCC-' => 'Raw Material CC',
            '-RAWWKCC-' => 'Raw Material Worked CC',
            '-RAWRGCC-' => 'Raw Material Rough CC',
            '-RAWOFC-' => 'Raw Material OFC',
            '-WIPCC-' => 'WIP CC',
            '-PATCH-' => 'OI',
            '-WIPOFC-' => 'WIP OFC',
        ];


        $notMaterial = [];
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 1900);
        $rows = PrWarehouseBi::where('verificato', false)->get();

       foreach ($rows as $row){
           $materiale = DB::table('pr_materials')->where('materiale', $row->materiale)->first();
           if(empty($materiale->id)){
               $notMaterial[] = $row->materiale;

               continue;
           }

           $categorie_mat = explode(" ", $materiale->categorie);
           $categoria = '';
           foreach ($categorie_mat as $categoria_mat){
               if(array_key_exists($categoria_mat, $cat))
                   $categoria = $cat[$categoria_mat];
           }

           $row->id_material = (!empty($materiale->id) ? $materiale->id:null);
           $row->categoria = $categoria;
           if($row->days_last_movement >= 181)
               $row->range_last_moviment = '180 Days & above';
           elseif($row->days_last_movement >= 121)
               $row->range_last_moviment = '121-180 Days';
           elseif($row->days_last_movement >= 91)
               $row->range_last_moviment = '91-120 Days';
           elseif($row->days_last_movement >= 61)
               $row->range_last_moviment = '61-90 Days';
           elseif($row->days_last_movement >= 31)
               $row->range_last_moviment = '31-60 Days';
           elseif($row->days_last_movement >= 0)
               $row->range_last_moviment = '0-30 Days';

           $row->verificato = (!empty($materiale->id) ? true:false);
           $row->save();

       }
        Log::debug('finito');
    }
}
