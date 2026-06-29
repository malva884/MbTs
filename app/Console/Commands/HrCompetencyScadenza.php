<?php

namespace App\Console\Commands;

use App\Jobs\HrCompetencyScadenzaNotification;
use Illuminate\Console\Command;

class HrCompetencyScadenza extends Command
{
    protected $signature = 'app:hr-competency-scadenza';
    protected $description = 'Invia notifica di scadenza valutazioni competenze ai responsabili';

    public function handle(): void
    {
        HrCompetencyScadenzaNotification::dispatch();
        $this->info('Notifica scadenza valutazioni competenze dispatchata.');
    }
}
