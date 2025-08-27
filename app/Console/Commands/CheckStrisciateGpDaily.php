<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Revolution\Google\Sheets\Facades\Sheets;


class CheckStrisciateGpDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitoring_asset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitoraggio Connettivita Asset';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        $data = date('Y-m-d',strtotime('-1 day'));

        $result = DB::connection('sqlsrv_root_gp')
            ->table('Produzione as PRD')
            ->select('PRD.DataOraInizio', 'PRD.DataOraFine', 'O.NumeroOrdineAcquisto', 'R.Modello', 'P.NomeProdotto', 'P.DescrizioneProdotto', 'P.Conversione12', 'UM.UM', 'PRD.#Cicli as quantita')
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM', 'DM.idMaster', '=', 'DSO.IDMaster')
            ->join('Ordini as O', 'O.IDOrdine', 'DM.IDOrdine')
            ->join('Prodotti as P', 'P.IDProdotto', '=', 'PRD.IDArticolo')
            ->join('UM', 'UM.IDUM', '=', 'DSO.IDUM')
            ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis')
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            ->whereDate('PRD.DataOraInizio',$data)
            ->Where('UM.UM', 'KM')
            ->where('PRD.#Cicli', '>', 499)
            ->get();

        $users = Utility::users_notify(['pr_check_strisciate']);

        foreach ($result as $materiale){
            Mail::send('emails/email_check_strisciata', compact('materiale','data'), function ($message) use ($users) {
                $message
                    ->to($users)
                    ->subject('Strisciata da Verificare');
            });
        }
    }
}
