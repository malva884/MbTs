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
            ->select('materiale','descrizione','quantita','importo','um','user',
                DB::raw("CASE user
                    WHEN '23920632' THEN 'Ghidin Roberta'
                    WHEN '23910700' THEN 'Varisco Francesca'
                    WHEN '23910519' THEN 'Busetti Daniela'
                    WHEN '23910470' THEN 'Vignoni Davide'
                    WHEN '23910430' THEN 'Guerreschi Antonio'
                    WHEN '23910263' THEN 'Betella Gloria'
                    WHEN '23920511' THEN 'Fogliata Vanni'
                    WHEN '23920619' THEN 'Carrera Chiara'
                    WHEN '23920599' THEN 'Vitarelli Gianpaolo'
                    WHEN '23910730' THEN 'Singh Sunpreet'
                    WHEN '23910839' THEN 'Ricca Asia'
                    WHEN '23920682' THEN 'Roberta Cossetti'
                    ELSE user END as fullname"))
            ->whereBetween('data_documento',[$stratDate,$andDate])
            ->whereIn('tipo_movimento',[701,702,201,202])
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
