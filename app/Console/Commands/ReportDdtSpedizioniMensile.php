<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReportDdtSpedizioniMensile extends Command
{
    protected $signature = 'app:report-ddt-spedizioni-mensile {--mese= : Mese di riferimento YYYY-MM (default: mese precedente)}';

    protected $description = 'Invia via email il report mensile dei costi di spedizione DDT';

    public function handle(): int
    {
        $this->info('[ReportDdtSpedizioniMensile] Inizio comando');

        // Mese di riferimento (default: mese precedente)
        try {
            $mese = $this->option('mese')
                ? Carbon::createFromFormat('Y-m', $this->option('mese'))->startOfMonth()
                : Carbon::now()->subMonth()->startOfMonth();
        } catch (\Exception $e) {
            $this->error('[ReportDdtSpedizioniMensile] Formato mese non valido, usare YYYY-MM');
            return self::FAILURE;
        }

        $fine = (clone $mese)->endOfMonth();
        $periodo = $mese->format('m/Y');

        $this->info("[ReportDdtSpedizioniMensile] Periodo: {$periodo}");

        // Statistiche generali
        $stats = DB::table('ddt_spedizioni')
            ->whereBetween('data_ddt', [$mese, $fine])
            ->selectRaw('COUNT(*) as totale')
            ->selectRaw('SUM(CASE WHEN costo_spedizione IS NOT NULL THEN 1 ELSE 0 END) as con_costo')
            ->selectRaw('SUM(CASE WHEN costo_spedizione IS NULL THEN 1 ELSE 0 END) as senza_costo')
            ->selectRaw('COALESCE(SUM(n_colli), 0) as colli')
            ->selectRaw('COALESCE(SUM(peso_lordo_kg), 0) as peso')
            ->selectRaw('COALESCE(SUM(costo_spedizione), 0) as costo_totale')
            ->first();

        if ($stats->totale == 0) {
            $this->info('[ReportDdtSpedizioniMensile] Nessun DDT nel periodo, report non inviato.');
            return self::SUCCESS;
        }

        // Breakdown per vettore (solo vettori con listino attivo)
        $perVettore = DB::table('ddt_spedizioni')
            ->join('listini_spedizioni', 'ddt_spedizioni.listino_id', '=', 'listini_spedizioni.id')
            ->where('listini_spedizioni.attivo', true)
            ->whereBetween('ddt_spedizioni.data_ddt', [$mese, $fine])
            ->select('listini_spedizioni.vettore')
            ->selectRaw('COUNT(*) as numero_ddt')
            ->selectRaw('COALESCE(SUM(ddt_spedizioni.n_colli), 0) as colli')
            ->selectRaw('COALESCE(SUM(ddt_spedizioni.peso_lordo_kg), 0) as peso')
            ->selectRaw('COALESCE(SUM(ddt_spedizioni.costo_spedizione), 0) as costo_totale')
            ->selectRaw('SUM(CASE WHEN ddt_spedizioni.costo_spedizione IS NULL THEN 1 ELSE 0 END) as senza_costo')
            ->groupBy('listini_spedizioni.vettore')
            ->orderByDesc('costo_totale')
            ->get();

        // DDT senza costo calcolato: solo quelli per cui un listino era stato trovato
        // (i vettori senza listino finiscono nel riepilogo motivi, non sono anomalie)
        $senzaCosto = DB::table('ddt_spedizioni')
            ->whereBetween('data_ddt', [$mese, $fine])
            ->whereNull('costo_spedizione')
            ->whereNotNull('listino_id')
            ->select('numero_ddt', 'vettore', 'destinazione_nome', 'costo_note')
            ->orderBy('data_ddt')
            ->limit(20)
            ->get();

        $senzaCostoConListino = DB::table('ddt_spedizioni')
            ->whereBetween('data_ddt', [$mese, $fine])
            ->whereNull('costo_spedizione')
            ->whereNotNull('listino_id')
            ->count();

        // Riepilogo motivi dei costi non calcolati (tutti i DDT senza costo del periodo)
        $nonCalcolati = DB::table('ddt_spedizioni')
            ->whereBetween('data_ddt', [$mese, $fine])
            ->whereNull('costo_spedizione')
            ->select('vettore', 'costo_note', 'listino_id')
            ->get();

        $sommarioMotivi = $nonCalcolati->groupBy(function ($d) {
            $nota = mb_strtolower($d->costo_note ?? '');
            if (empty($d->vettore)) {
                return 'Vettore mancante';
            }
            if (str_contains($nota, 'peso')) {
                return 'Peso lordo mancante/non valido';
            }
            if (str_contains($nota, 'provincia') || str_contains($nota, 'indirizzo')) {
                return 'Indirizzo non identificato';
            }
            if (str_contains($nota, 'tariffa')) {
                return 'Nessuna tariffa nel listino';
            }
            if (is_null($d->listino_id) || str_contains($nota, 'listino')) {
                return 'Nessun listino per il vettore';
            }
            return 'Altro';
        })->map(fn($g, $motivo) => [
            'motivo' => $motivo,
            'numero' => $g->count(),
            'vettori' => $g->pluck('vettore')->filter()->unique()->sort()->take(5)->implode(', '),
        ])->sortByDesc('numero')->values();

        $users = Utility::users_notify(['sp_ddt_report_mensile']);

        if (empty($users)) {
            $this->warn('[ReportDdtSpedizioniMensile] Nessun destinatario configurato per la notifica "sp_ddt_report_mensile".');
            Log::warning('[ReportDdtSpedizioniMensile] Nessun destinatario per sp_ddt_report_mensile');
            return self::SUCCESS;
        }

        $oggetto = "Report Costi Spedizioni DDT - {$periodo}";

        Mail::send('emails/report_ddt_spedizioni', compact('periodo', 'stats', 'perVettore', 'senzaCosto', 'senzaCostoConListino', 'sommarioMotivi'), function ($message) use ($users, $oggetto) {
            $message->to($users)->subject($oggetto);
        });

        $this->info('[ReportDdtSpedizioniMensile] Email inviata a ' . count($users) . ' destinatari.');
        Log::info('[ReportDdtSpedizioniMensile] Report inviato', ['periodo' => $periodo, 'destinatari' => count($users)]);

        return self::SUCCESS;
    }
}
