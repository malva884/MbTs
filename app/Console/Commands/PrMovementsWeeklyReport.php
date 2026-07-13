<?php

namespace App\Console\Commands;

use App\Models\PrMovement;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class PrMovementsWeeklyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pr_movements_weekly_report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report settimanale giro codice movimenti magazzino tipo 309';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $end = date('Y-m-d 23:59:59', strtotime('sunday last week'));

        $movements = PrMovement::where('tipo_movimento', 309)
            ->whereBetween('data_pubblicazione', [$start, $end])
            ->orderBy('data_pubblicazione', 'asc')
            ->get();

        $variations = [];
        $grouped = $movements->groupBy('documento_materiale');

        foreach ($grouped as $documento => $rows) {
            $da = $rows->firstWhere('quantita', '<', 0);
            $a = $rows->firstWhere('quantita', '>', 0);

            if ($da && $a) {
                $variations[] = [
                    'documento' => $documento,
                    'data_pubblicazione' => $da->data_pubblicazione,
                    'da' => $da,
                    'a' => $a,
                ];
            }
        }

        $emails = Utility::users_notify(['pr_movements_weekly']);


        if (empty($emails)) {
            $this->warn('Nessun destinatario configurato per la notifica pr_movements_weekly.');
            return;
        }

        Mail::send('emails.email_pr_movements_weekly', compact('variations', 'start', 'end', 'movements'), function ($message) use ($emails) {
            $message
                ->to($emails)
                ->subject('Report Settimanale Giro Codice - Movimenti Magazzino 309');
        });

        $this->info('Report inviato a ' . count($emails) . ' destinatari. Giri codice trovati: ' . count($variations));
    }
}
