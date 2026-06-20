<?php

namespace App\Console\Commands;


use App\Models\PrMovement;
use App\Models\Utility;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;


class InventoryAdjustment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:inventory_adjustment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'i';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $time = strtotime(date('Y-m-d').' -7 Day');
        $stratDate = date('Y-m-d', $time);
        $time = strtotime(date('Y-m-d').' -1 Day');
        $andDate = date('Y-m-d', $time);
        $objs = DB::table('pr_movements')
            ->select('materiale','descrizione','quantita','importo','um',
                DB::raw("CASE WHEN user = '23910730' THEN 'Sunpreet Singh' ELSE 'Sunpreet Singh' END as fullname"))
            ->whereBetween('data_documento',[$stratDate,$andDate])
            ->whereIn('tipo_movimento',[701,702])
            ->orderBy('data_documento','asc')
            ->orderBy('materiale','desc')
            ->orderBy('quantita','desc')
            ->get();

        $result = [
            'inizio' => $stratDate,
            'fine' => $andDate,
            'rows' => $objs
        ];

        $users = Utility::users_notify(['pr_inventory_adjustment']);

        Mail::send('emails/email_inventory_adjustment', ['data' => $result], function ($message) use($users){
            $message
                ->to($users)
                ->subject('Inventory Adjustment - Metallurgica Bresciana');
        });
    }
}
