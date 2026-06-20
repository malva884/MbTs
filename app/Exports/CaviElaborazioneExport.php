<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CaviElaborazioneExport implements WithMultipleSheets
{
    protected $righeNuove = [];
    protected $righeTotali = [];

    public function __construct($filePath)
    {
        // 1. Preveniamo limiti di tempo e memoria per script lunghi
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '3024M'); // Incrementato a 1GB per gestire in sicurezza la scrittura
        ignore_user_abort(true);

        $righeNuove = [];
        $totaliTipologia20 = [];
        $totaliTipologia41 = [];
        $totaliClienti     = [];

        // 2. Configura il Reader per estrarre solo il testo (leggerissimo)
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Trasformiamo il foglio in array associativo [A => valore, B => valore...]
        $rows = $worksheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return;
        }

        // 3. Identifichiamo le colonne dall'intestazione (Header)
        $firstRowKey = array_key_first($rows);
        $header = $rows[$firstRowKey];
        unset($rows[$firstRowKey]); // Rimuoviamo l'header dal ciclo dei dati

        $colMaterial = array_search('Material', $header);
        $colQty      = array_search('Order Quantity', $header);
        $colClient   = array_search('Name 1', $header);

        // Costruiamo il nuovo header
        $nuovoHeader = array_values($header);
        $nuovoHeader = array_merge($nuovoHeader, [
            'Numero Fibre', 'Classe', 'Costo', 'Unita Misura', 'Tipologia', 'Quantita Convertita (Metri)', 'CKM', 'FKM'
        ]);
        $righeNuove[] = $nuovoHeader;

        // 4. 🚀 OTTIMIZZAZIONE STRATEGICA: Raccogliamo tutti i codici materiale unici
        $tuttiCodiciMateriale = [];
        foreach ($rows as $rowData) {
            $materialCode = $rowData[$colMaterial] ?? null;
            if (!empty($materialCode) && trim($materialCode) !== '' && trim($materialCode) !== 'Material') {
                $tuttiCodiciMateriale[] = trim($materialCode);
            }
        }
        $tuttiCodiciMateriale = array_unique($tuttiCodiciMateriale);

        // Eseguiamo UNA SOLA QUERY massiva per tutti i materiali estratti dal file
        $mappaMateriali = [];
        if (!empty($tuttiCodiciMateriale)) {
            $prodottiDb = DB::connection('sqlsrv_gp')->table('AGG_PRODOTTI_TMP')
                ->select('cdProdotto as materiale', 'Valore', 'cdUM as unita_misura', 'Conversione as numero_fibre', 'cdMateriale as tipologia', 'dcRaggruppamentoPF as classe')
                ->whereIn('cdProdotto', $tuttiCodiciMateriale)
                ->get();

            // Trasformiamo il risultato in una mappa associativa per un accesso istantaneo O(1)
            foreach ($prodottiDb as $prodotto) {
                $mappaMateriali[trim($prodotto->materiale)] = $prodotto;
            }
        }

        // 5. Ciclo sui dati alla velocità della luce (senza interrogare il DB ad ogni riga)
        foreach ($rows as $rowData) {

            $materialCode = $rowData[$colMaterial] ?? null;

            if (empty($materialCode) || trim($materialCode) === '' || trim($materialCode) === 'Material') {
                continue;
            }

            $materialCode = trim($materialCode);
            $confirmedQty = floatval($rowData[$colQty] ?? 0);
            $clienteName  = $rowData[$colClient] ?? 'CLIENTE SCONOSCIUTO';

            // Valori di default
            $numeroFibre   = '';
            $classe        = '';
            $costo         = 0;
            $unitaMisura   = '';
            $tipologia     = '';
            $quantitaMetri = $confirmedQty;

            // Cerchiamo le informazioni nella mappa in memoria, senza toccare il DB!
            if (isset($mappaMateriali[$materialCode])) {
                $info = $mappaMateriali[$materialCode];

                $numeroFibre = $info->numero_fibre;
                $classe      = $info->classe;
                $costo       = $info->Valore;
                $unitaMisura = strtoupper($info->unita_misura);
                $tipologia   = $info->tipologia;

                if ($unitaMisura === 'KM') {
                    $quantitaMetri = $confirmedQty * 1000;
                }
            }

            // Calcoli matematici
            $ckm = $confirmedQty / 1000;
            $fkm = $ckm * floatval($numeroFibre);

            // Raggruppamento per Foglio 2
            if ($tipologia == 20) {
                $totaliTipologia20[$numeroFibre] = ($totaliTipologia20[$numeroFibre] ?? 0) + $quantitaMetri;
            } elseif ($tipologia == 41) {
                $totaliTipologia41[$classe] = ($totaliTipologia41[$classe] ?? 0) + $quantitaMetri;
            }
            $totaliClienti[$clienteName] = ($totaliClienti[$clienteName] ?? 0) + $quantitaMetri;

            // Prepariamo la riga finale da inserire
            $rigaCompleta = array_values($rowData);
            $rigaCompleta[] = $numeroFibre;
            $rigaCompleta[] = $classe;
            $rigaCompleta[] = $costo;
            $rigaCompleta[] = $unitaMisura;
            $rigaCompleta[] = $tipologia;
            $rigaCompleta[] = $quantitaMetri;
            $rigaCompleta[] = $ckm;
            $rigaCompleta[] = $fkm;

            $righeNuove[] = $rigaCompleta;
        }

        // 6. Generazione dati dello Sheet 2 (Totali)
        $righeTotali = [];

        $righeTotali[] = ['TOTALI TIPOLOGIA 20 (DIVISI PER NUMERO FIBRE)'];
        $righeTotali[] = ['Numero Fibre', 'Totale Metri'];
        foreach ($totaliTipologia20 as $fibra => $totale) {
            $righeTotali[] = [$fibra, $totale];
        }

        $righeTotali[] = [];

        $righeTotali[] = ['TOTALI TIPOLOGIA 41 (DIVISI PER CLASSE)'];
        $righeTotali[] = ['Classe', 'Totale Metri'];
        foreach ($totaliTipologia41 as $cl => $totale) {
            $righeTotali[] = [$cl, $totale];
        }

        $righeTotali[] = [];

        $righeTotali[] = ['TOTALI GENERALI PER CLIENTE (COLONNA NAME 1)'];
        $righeTotali[] = ['Cliente', 'Totale Metri Complessivi'];
        foreach ($totaliClienti as $cliente => $totale) {
            $righeTotali[] = [$cliente, $totale];
        }

        $this->righeNuove = $righeNuove;
        $this->righeTotali = $righeTotali;
    }

    public function sheets(): array
    {
        return [
            new class($this->righeNuove) implements FromArray, WithTitle {
                private $data;
                public function __construct($data) { $this->data = $data; }
                public function array(): array { return $this->data; }
                public function title(): string { return 'Dati Elaborati'; }
            },
            new class($this->righeTotali) implements FromArray, WithTitle {
                private $data;
                public function __construct($data) { $this->data = $data; }
                public function array(): array { return $this->data; }
                public function title(): string { return 'Riepilogo Totali'; }
            }
        ];
    }
}
