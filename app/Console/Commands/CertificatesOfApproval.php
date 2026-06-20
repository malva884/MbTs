<?php

namespace App\Console\Commands;


use App\Models\QtSupplierCertification;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;



class CertificatesOfApproval extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:certificates_of_approval';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio notifica di certificati in attesa di approvazione.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $objs = QtSupplierCertification::whereNull('approvato')->orderBy('fornitore_id', 'asc')->get();

        $certificati = [];
        foreach ($objs as $obj)
            $certificati[] = ['Fornitore' => $obj->supplier->ragioneSociale, 'idFornitore' => $obj->fornitore_id, 'Certificato' => $obj->certificato->titolo];


        $users = Utility::users_notify(['caricamento-certificato-fornitori']);

        Mail::send('emails/email_certificates_of_approval', ['certificati'=>$certificati], function ($message) use ($users){
            $message
                ->to($users)
                ->subject('Certificati in attesa di approvazione');
        });

    }
}
