<?php

namespace App\Console\Commands;

use App\Http\Controllers\UserController;
use App\Models\ExternalUserNotification;
use App\Models\QtSupplierUser;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Revolution\Google\Sheets\Facades\Sheets;


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
        try {

            ini_set('max_execution_time', -1);

            $inv = Sheets::spreadsheet('1t8v4vu0BFiwoWR81ZiDyfbZeERmY1jydheAdgADAPz8')->sheet('email fornitori')->all();
            $arr = [];

            $invio_email = [];
            foreach ($inv as $invio){
                if(!empty($invio[0])){
                    $invio_email[] = [$invio[0]];
                    $arr[] = $invio[0];
                }
            }

            $fornitori = DB::connection('sqlsrv_fornitori')->table('suppliers')
                ->select('suppliers.*')
                ->whereNotIn('codiceSap',$arr)
                //->where('id','A0002759-E487-45E1-B5C4-335F9C84E586')
                ->get();

            $o = 0;
            foreach ($fornitori as $fornitore){
                if($o >= 20)
                    break;

                $emails =[];
                if(filter_var($fornitore->email, FILTER_VALIDATE_EMAIL))
                    $emails[$fornitore->email] = $fornitore->email;
                $users = DB::connection('sqlsrv_fornitori')->table('users')->where('supplier_id',$fornitore->id)->get();
                foreach ($users as $user)
                    if(filter_var($user->email, FILTER_VALIDATE_EMAIL))
                        $emails[$user->email] = $user->email;

                if($fornitore->nazione == 'IT')
                    $oggetto = 'Urgente - Raccolta dati Portale fornitori';
                else
                    $oggetto = 'Urgent - Supplier Portal Data Collection';

                if(count($emails) == 1){
                    $obj = DB::connection('sqlsrv_fornitori')->table('users')->where('email',$fornitore->email)->first();
                    if(!empty($obj->id)){
                        $us = new QtSupplierUser();
                        $us->supplier_id = $fornitore->id;
                        $us->nome = $fornitore->ragioneSociale;
                        $us->email = $fornitore->email;
                        $us->save();
                    }

                }

                if(count($emails)){
                    /*Mail::send('emails/email_avviso_portale_fornitore', [''], function ($message) use ($emails,$oggetto) {
                        $message
                            ->to(array_values($emails))
                            ->from("portale.metallurgica@stl.tech", "Metallurgica Bresciana S.p.a")
                            ->subject($oggetto);
                    });
                    */
                    $invio_email[] = [$fornitore->codiceSap];
                    $o++;
                }else{
                    $invio_email[] = [$fornitore->codiceSap,$fornitore->email,'Error Email'];
                }
            }
        } catch (\Exception $e) {
            Sheets::sheet('email fornitori')->update($invio_email);
        }

        Sheets::sheet('email fornitori')->update($invio_email);




    }
}
