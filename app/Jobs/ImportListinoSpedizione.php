<?php

namespace App\Jobs;

use App\Models\ListinoSpedizione;
use App\Models\ListinoSpedizioneVoce;
use App\Services\GeminiAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportListinoSpedizione implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 300;

    protected int $listinoId;
    protected string $percorsoFile;

    public function __construct(int $listinoId, string $percorsoFile)
    {
        $this->listinoId = $listinoId;
        $this->percorsoFile = $percorsoFile;
    }

    public function handle()
    {
        $listino = ListinoSpedizione::find($this->listinoId);

        if (!$listino) {
            Log::warning("[ImportListinoSpedizione] Listino {$this->listinoId} non trovato, job annullato.");
            return;
        }

        try {
            $risultato = $this->chiediAGemini($listino->tipo);

            if (empty($risultato['righe'])) {
                $listino->update([
                    'status' => ListinoSpedizione::STATUS_ERROR,
                    'error_message' => 'Gemini non ha estratto nessuna riga di listino dal PDF.',
                    'raw_response' => $risultato,
                ]);
                Log::warning("[ImportListinoSpedizione] Nessuna riga estratta per listino {$listino->id}");
                return;
            }

            $vociCreate = $listino->tipo === ListinoSpedizione::TIPO_PALLET
                ? $this->salvaVociPallet($listino, $risultato['righe'])
                : $this->salvaVociPeso($listino, $risultato['righe']);

            $listino->update([
                'status' => ListinoSpedizione::STATUS_PROCESSED,
                'error_message' => null,
                'raw_response' => $risultato,
            ]);

            Log::info("[ImportListinoSpedizione] Listino {$listino->id} importato: {$vociCreate} voci create.");
        } catch (\Exception $e) {
            Log::error("[ImportListinoSpedizione] Errore listino {$this->listinoId}: " . $e->getMessage());

            // All'ultimo tentativo marca il listino in errore
            if ($this->attempts() >= $this->tries) {
                $listino->update([
                    'status' => ListinoSpedizione::STATUS_ERROR,
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Espande le righe del listino a pallet in voci (provincia x servizio x fascia).
     */
    private function salvaVociPallet(ListinoSpedizione $listino, array $righe): int
    {
        $count = 0;

        foreach ($righe as $riga) {
            $base = [
                'listino_id' => $listino->id,
                'regione' => $riga['regione'] ?? null,
                'provincia' => $riga['provincia'] ?? null,
                'hub' => $riga['hub'] ?? null,
                'tipo_voce' => ListinoSpedizioneVoce::TIPO_VOCE_TARIFFA,
            ];

            foreach (['premium' => 'PREMIUM', 'economy' => 'ECONOMY'] as $chiave => $servizio) {
                $prezzi = $riga[$chiave] ?? [];
                if (!is_array($prezzi)) {
                    continue;
                }

                foreach ($prezzi as $fascia => $prezzo) {
                    $prezzoNum = $this->parsePrezzo($prezzo);
                    if ($prezzoNum === null) {
                        continue;
                    }

                    ListinoSpedizioneVoce::create($base + [
                        'servizio' => $servizio,
                        'fascia' => strtoupper((string) $fascia),
                        'prezzo' => $prezzoNum,
                    ]);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Espande le righe del listino a peso in voci (regione x fascia peso) + inoltro.
     */
    private function salvaVociPeso(ListinoSpedizione $listino, array $righe): int
    {
        $count = 0;

        foreach ($righe as $riga) {
            $regione = $riga['regione'] ?? null;
            $fasce = $riga['fasce'] ?? [];

            foreach ($fasce as $fascia) {
                $prezzoNum = $this->parsePrezzo($fascia['prezzo'] ?? null);
                if ($prezzoNum === null) {
                    continue;
                }

                ListinoSpedizioneVoce::create([
                    'listino_id' => $listino->id,
                    'regione' => $regione,
                    'fascia' => $fascia['fascia'] ?? null,
                    'peso_da' => $fascia['peso_da'] ?? null,
                    'peso_a' => $fascia['peso_a'] ?? null,
                    'prezzo' => $prezzoNum,
                    'tipo_voce' => ListinoSpedizioneVoce::TIPO_VOCE_TARIFFA,
                ]);
                $count++;
            }

            $inoltro = $this->parsePrezzo($riga['inoltro'] ?? null);
            if ($inoltro !== null) {
                ListinoSpedizioneVoce::create([
                    'listino_id' => $listino->id,
                    'regione' => $regione,
                    'prezzo' => $inoltro,
                    'tipo_voce' => ListinoSpedizioneVoce::TIPO_VOCE_INOLTRO,
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function parsePrezzo($valore): ?float
    {
        if ($valore === null || $valore === '') {
            return null;
        }

        if (is_numeric($valore)) {
            return (float) $valore;
        }

        $pulito = preg_replace('/[^\d,\.\-]/', '', (string) $valore);

        // Formato italiano: 1.234,56 -> 1234.56
        if (str_contains($pulito, ',') && str_contains($pulito, '.')) {
            $pulito = str_replace('.', '', $pulito);
            $pulito = str_replace(',', '.', $pulito);
        } elseif (str_contains($pulito, ',')) {
            $pulito = str_replace(',', '.', $pulito);
        }

        return is_numeric($pulito) ? (float) $pulito : null;
    }

    /**
     * Chiamata a Gemini con prompt specifico per il tipo di listino.
     */
    private function chiediAGemini(string $tipo): ?array
    {
        $prompt = $tipo === ListinoSpedizione::TIPO_PALLET
            ? $this->promptPallet()
            : $this->promptPeso();

        $service = new GeminiAiService();
        $rispostaRaw = $service->analizzaFile(
            filePath: $this->percorsoFile,
            prompt: $prompt,
            mimeType: 'application/pdf'
        );

        if ($rispostaRaw === null) {
            return null;
        }

        $rispostaPulita = trim($rispostaRaw);
        $rispostaPulita = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $rispostaPulita);

        $dati = json_decode($rispostaPulita, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $dati;
        }

        Log::error("[ImportListinoSpedizione] Risposta Gemini non valida: " . $rispostaRaw);
        return null;
    }

    private function promptPallet(): string
    {
        return 'Sei un assistente di estrazione dati strutturati. Analizza il PDF scansionato fornito: contiene un LISTINO PREZZI di spedizione a pallet, organizzato in una tabella.

La tabella ha queste colonne:
- "Regione": la regione italiana (intestazione di gruppo, vale per tutte le province sottostanti fino alla regione successiva)
- "Provincia": la sigla della provincia (es. AL, MI, CO). Puo contenere note tra parentesi, es. "CO (Lago)": riporta solo la sigla.
- "HUB": il codice hub (es. BO)
- Gruppo "PREMIUM": colonne FP, LP, ULP, HP, ELP, QP, MQP (o MQ)
- Gruppo "ECONOMY": stesse colonne FP, LP, ULP, HP, ELP, QP, MQP (o MQ)

Per OGNI riga di provincia estrai TUTTI i prezzi delle due sezioni.

Formato della risposta: restituisci ESCLUSIVAMENTE un oggetto JSON strutturato esattamente cosi, senza markdown, senza introduzioni e senza testo di contorno:
{
  "righe": [
    {
      "regione": "PIEMONTE",
      "provincia": "AL",
      "hub": "BO",
      "premium": {"FP": 79.80, "LP": 69.10, "ULP": 54.10, "HP": 56.60, "ELP": 50.30, "QP": 42.90, "MQP": 38.10},
      "economy": {"FP": 75.10, "LP": 63.60, "ULP": 50.90, "HP": 53.00, "ELP": 47.40, "QP": 40.30, "MQP": 35.10}
    }
  ]
}

Regole:
- I prezzi usano la virgola come separatore decimale nel documento: converti SEMPRE in numero con il punto (es. 79,80 -> 79.80).
- Se una cella e vuota o illeggibile, ometti quella fascia dall\'oggetto.
- Estrai TUTTE le righe di TUTTE le regioni presenti nel documento, senza tralasciarne nessuna.
- Se il documento ha piu pagine, analizzale tutte.';
    }

    private function promptPeso(): string
    {
        return 'Sei un assistente di estrazione dati strutturati. Analizza il PDF scansionato fornito: contiene un LISTINO PREZZI di spedizione a peso, organizzato in una tabella.

La tabella ha queste colonne:
- "Regione/Fascia": la regione italiana. Sotto ogni regione c\'e una riga "Inoltro" con un costo fisso.
- Colonne di fascia peso con intestazione tipo "100,000 €/kg.100", "500,000 €/kg.100", "1000,000 €/kg.100", "99999,990 €/kg.100": sono fasce di peso in kg (fino a 100, fino a 500, fino a 1000, oltre 1000). Il prezzo e in euro per 100 kg.

Per OGNI regione estrai i prezzi delle fasce di peso e il valore della riga "Inoltro".

Formato della risposta: restituisci ESCLUSIVAMENTE un oggetto JSON strutturato esattamente cosi, senza markdown, senza introduzioni e senza testo di contorno:
{
  "righe": [
    {
      "regione": "LOMBARDIA",
      "inoltro": 1.070,
      "fasce": [
        {"fascia": "kg_100", "peso_da": 0, "peso_a": 100, "prezzo": 14.300},
        {"fascia": "kg_500", "peso_da": 100, "peso_a": 500, "prezzo": 9.730},
        {"fascia": "kg_1000", "peso_da": 500, "peso_a": 1000, "prezzo": 7.460},
        {"fascia": "kg_over", "peso_da": 1000, "peso_a": 99999.99, "prezzo": 6.530}
      ]
    }
  ]
}

Regole:
- I prezzi usano la virgola come separatore decimale nel documento: converti SEMPRE in numero con il punto (es. 14,300 -> 14.300).
- Le fasce devono essere esattamente 4 per regione, con i nomi kg_100, kg_500, kg_1000, kg_over.
- Estrai TUTTE le regioni presenti nel documento, senza tralasciarne nessuna.
- Se il documento ha piu pagine, analizzale tutte.';
    }
}
