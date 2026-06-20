<?php

namespace App\Console\Commands;

use App\Http\Controllers\UserController;
use App\Models\QtSupplierCertification;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class ScadenzaCertificazioneFornitori extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mensa_week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio promemoria scadenza certificazione fornitore.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $certificazioni = QtSupplierCertification::where('scadenza',date('Y-m-d',strtotime('-2 months')))
            ->get();

        foreach ($certificazioni as $certificazione){
            $users = DB::connection('sqlsrv_fornitori')
                ->table('users')
                ->where('supplier_id',$certificazione->fornitore_id)
                ->pluck('email');

            Mail::send('emails/email_mensa', [], function ($message) use($users,$certificazioni){
                $message
                    ->to($users)
                    ->from("portale.metallurgica@stl.tech", "Metallurgica Bresciana S.p.a")
                    ->subject('Scadenza Certificazione '. $certificazioni->centificato->titolo);
            });
        }
    }
}
