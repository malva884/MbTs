<?php

namespace App\Jobs;

use App\Models\FiShippedHead;
use App\Models\Utility;
use App\Services\GoogleDrive;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Revolution\Google\Sheets\Facades\Sheets;

class SpeditoSheet implements ShouldQueue
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
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 1900);
        $folder = GoogleDrive::search('0AJw5ImqlBEwwUk9PVA',null,'dir',date('Y'));
        if($folder){
            $files = GoogleDrive::search($folder,null,'file',date('m',strtotime( date('Y-m')." -1 months")));
            $rows = Sheets::spreadsheet($files);
            Sheets::addSheet('Details');
            Sheets::addSheet('Summary');
            //Sheets::deleteSheet('Sheet1');
            $obj = DB::table('fi_shipped_heads')->select('totale_spedito')->where('id',$this->id)->first();
            $rows = DB::table('fi_shipped_rows')->select('fi_shipped_rows.material', 'fi_shipped_rows.description', 'fi_shipped_rows.total_stock', 'warehouse_details.unit', 'unitary_value', 'total_value', 'crcy', 'last_gds_mvmt','material_stores.materialClass','material_stores.fiber_count')
                ->leftJoin('material_stores','material_stores.material','warehouse_details.material')
                ->where('warehouse_id', '=', $this->id)
                ->orderBy('warehouse_details.material','asc')
                ->get();
        }
        $objs = DB::table('fi_shipped_rows')->where('head', $this->id)->get();






    }
}
