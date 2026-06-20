<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PortaleFornitori extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:portale-fornitori';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio email portale fornitori';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $objs = DB::connection('sqlsrv_fornitori')->table('users')
            ->select('users.*')
            ->whereNotIn('supplier_id', function($query){
                $query->select( 'fornitore_id')
                    ->from('supplier_certifications')
                    ->distinct();
            })
            ->get();

        $fornitori = [];
        foreach ($objs as $obj)
            if(!empty($obj->email))
                $fornitori[$obj->supplier_id][] = $obj->email;

        foreach ($fornitori as $utenti)
            Mail::send('emails/email_avviso_portale_fornitore', [], function ($message) use($utenti){
                $message
                    ->to($utenti)
                    ->subject('Urgent - Supplier Portal Data Collection');
            });

    }
}
