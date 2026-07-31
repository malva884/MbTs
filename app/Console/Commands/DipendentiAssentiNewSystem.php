<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class DipendentiAssentiNewSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dipendenti_assenti_new_system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invio giornaliero assenza dipendenti dal nuovo sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        
        // Esegui sincronizzazione dal vecchio sistema per la data odierna
        $this->info('Esecuzione sincronizzazione presenze dal vecchio sistema...');
        $this->call('sync:presenze-from-old', ['--date' => date('Y-m-d')]);
        
        // Recupera tutte le assenze dal nuovo sistema per oggi
        // Tipologie: 1=Ferie, 2=104, 3=Malattia, 4=Assenza, 5=Permesso
        // Esclude annullamenti: 101, 102, 105
        // Esclude richieste originali che hanno un annullamento approvato
        $annullamentoTipologie = [101, 102, 105]; // Annulamento Ferie, 104, Permesso
        $richiestaOriginaleTipologie = [1, 2, 3, 4, 5]; // Ferie, 104, Malattia, Assenza, Permesso
        
        // Recupera gli annullamenti approvati per oggi
        $annullamenti = DB::table('hr_hours_requested_details as dettagli')
            ->join('hr_hours_requesteds as richieste', 'richieste.id', 'dettagli.richiesta_id')
            ->select(
                'richieste.dipendente_matricola',
                'dettagli.data',
                'richieste.tipologia',
                'dettagli.bacheca_id'
            )
            ->whereIn('richieste.tipologia', $annullamentoTipologie)
            ->where('richieste.stato', 1) // Approvato
            ->where('dettagli.confermato', true)
            ->where('dettagli.data', date('Y-m-d'))
            ->get();
        
        // Recupera tutte le richieste originali
        $result = DB::table('hr_hours_requested_details as dettagli')
            ->join('hr_hours_requesteds as richieste', 'richieste.id', 'dettagli.richiesta_id')
            ->select(
                'richieste.dipendente_nome',
                'richieste.dipendente_cognome',
                'richieste.dipendente_matricola',
                'dettagli.data',
                'dettagli.ora_inizio',
                'dettagli.ora_fine',
                'dettagli.tipologia',
                'dettagli.bacheca_id'
            )
            ->whereIn('dettagli.tipologia', $richiestaOriginaleTipologie)
            ->whereIn('richieste.centro_di_costo', ['bluecollar_ofc', 'bluecollar_cc'])
            ->where('dettagli.confermato', true)
            ->where('dettagli.data', date('Y-m-d'))
            ->get();
        
        // Filtra in PHP escludendo le richieste con annullamento corrispondente
        $result = $result->filter(function($item) use ($annullamenti) {
            // Mappa tipologia originale -> tipologia annullamento
            $tipologiaMapping = [
                1 => 101, // Ferie -> Annullamento Ferie
                2 => 102, // 104 -> Annullamento 104
                5 => 105, // Permesso -> Annullamento Permesso
            ];
            
            $annullamentoCorrispondente = $tipologiaMapping[$item->tipologia] ?? null;
            if (!$annullamentoCorrispondente) {
                return true; // Nessun annullamento corrispondente per questa tipologia
            }
            
            // Cerca annullamento per stesso bacheca_id
            $haAnnullamento = $annullamenti->contains(function($ann) use ($item, $annullamentoCorrispondente) {
                return $ann->bacheca_id == $item->bacheca_id
                    && $ann->tipologia == $annullamentoCorrispondente;
            });
            
            return !$haAnnullamento;
        });

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $activeWorksheet->setCellValue('A1', 'Cognome');
        $activeWorksheet->setCellValue('B1', 'Nome');
        $activeWorksheet->setCellValue('C1', 'Tipologia');
        $activeWorksheet->setCellValue('D1', 'Data');
        $activeWorksheet->setCellValue('E1', 'Dalle');
        $activeWorksheet->setCellValue('F1', 'Alle');
        
        $i = 2;
        foreach ($result as $row) {
            $type = '';
            switch ($row->tipologia) {
                case 1:
                    $type = 'Ferie';
                    break;
                case 2:
                    $type = '104';
                    break;
                case 3:
                    $type = 'Malattia';
                    break;
                case 4:
                    $type = 'Assenza';
                    break;
                case 5:
                    $type = 'Permesso';
                    break;
            }
            
            $activeWorksheet->setCellValue('A' . $i, $row->dipendente_cognome);
            $activeWorksheet->setCellValue('B' . $i, $row->dipendente_nome);
            $activeWorksheet->setCellValue('C' . $i, $type);
            $activeWorksheet->setCellValue('D' . $i, $row->data);
            $activeWorksheet->setCellValue('E' . $i, $row->ora_inizio);
            $activeWorksheet->setCellValue('F' . $i, $row->ora_fine);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $path_file = '/public/file/';
        $file_dir = dirname(__DIR__, 3) . $path_file;
        
        if (!file_exists($file_dir)) {
            if (!mkdir($file_dir, 0777, true) && !is_dir($path_file)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path_file));
            }
        }
        
        $file = $file_dir . 'dipendenti_assenti.xlsx';
        $writer->save($file);

        $users = Utility::users_notify(['pr_assenza_dipendenti_new']);

        Mail::send('emails/email_assenze_dipendenti', [], function ($message) use ($file, $users) {
            $message
                ->to('gregorio.grande@stl.tech')
                ->subject('Assenze Dipendenti Del ' . date('Y-m-d'));

            $message->attach($file);
        });
        
        File::delete($file);
        
        $this->info('Email inviata con successo.');
    }
}
