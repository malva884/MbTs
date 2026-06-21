<?php

namespace App\Console\Commands;

use App\Http\Controllers\UserController;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class fornitoriStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fornitori-start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio email fornitori';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        ini_set('max_execution_time', -1);
        $fornitori = DB::connection('sqlsrv_fornitori')->table('suppliers')
            ->select('suppliers.*')
            //->where('id','A0002759-E487-45E1-B5C4-335F9C84E586')
            ->get();

        foreach ($fornitori as $fornitore){
            $emails =[];
            $emails[$fornitore->email] = $fornitore->email;
            $users = DB::connection('sqlsrv_fornitori')->table('users')->where('supplier_id',$fornitore->id)->get();
            foreach ($users as $user)
                $emails[$user->email] = $user->email;

            if($fornitore->nazione == 'IT')
                $oggetto = 'Urgente - Raccolta dati Portale fornitori';
            else
                $oggetto = 'Urgent - Supplier Portal Data Collection';

                    Mail::send('emails/email_avviso_portale_fornitore', [''], function ($message) use ($emails,$oggetto) {
                        $message
                            ->to(array_values($emails))
                            ->from("portale.metallurgica@stl.tech", "Metallurgica Bresciana S.p.a.")
                            ->subject($oggetto);
                    });

        }

    }
}
