<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckFabbisogniOrdiniDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-fabbisogni-ordini-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'verifica che gli ordini importati abbiano i fabbisogni.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ordiniSenzaFabbisogni = DB::connection('sqlsrv_gp')
            ->table('AGG_MASTER_TMP as o')
            ->select('o.cdOrdine','o.cdProdotto','o.cdUM','o.QtaOrdinata','o.dataInserimento')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('AGG_FABBISOGNI_TMP as f')
                    ->whereColumn('f.cdOrdine', 'o.cdOrdine');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('AGG_EXP_PRODUZIONE_TMP as p')
                    ->whereColumn('p.Ordine', 'o.cdOrdine');
            })
            ->where('o.dataInserimento','>=','2026-01-01')
            ->where(function ($q) {
                $q->whereNull('o.Note')
                    ->orWhere('o.Note', 'NOT LIKE', '§%');
            })
            ->orderBy('o.dataInserimento', 'desc')
            ->get();

        if(count($ordiniSenzaFabbisogni)){
            $users = Utility::users_notify(['pr_fabbisogni_mancanti']);

            if (empty($users)) {
                $this->warn('Nessun destinatario configurato per pr_fabbisogni_mancanti.');
                return 0;
            }

            Mail::send('emails/email_fabbisogni_mancanti', ['ordini' => $ordiniSenzaFabbisogni], function ($message) use ($users) {
                $message
                    ->to($users)
                    ->subject('Ordini senza Fabbisogni - ' . date('d/m/Y'));
            });

            $this->info('Notifica inviata: ' . count($ordiniSenzaFabbisogni) . ' ordini senza fabbisogni.');
        }

        return 0;
    }
}
