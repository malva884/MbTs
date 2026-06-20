<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
       // $schedule->command('app:log-diametri-daily')
       //     ->timezone('Europe/Amsterdam')
       //     ->daily();

       /* $schedule->command('app:hr-richiesta-giorni')
            ->timezone('Europe/Amsterdam')
            ->everyMinute();

        $schedule->command('app:ftr_optical_daily')
            ->timezone('Europe/Amsterdam')
            ->dailyAt('15:40');

        // Prenotazioni Mensa
        $schedule->command('app:mensa_week')
            ->timezone('Europe/Amsterdam')
            ->weeklyOn(5, '13:15');

       $schedule->command('app:sync_matariali')
            ->timezone('Europe/Amsterdam')
            ->dailyAt( '16:45');

       $schedule->command('app:check-list')
            ->timezone('Europe/Amsterdam')
            ->monthly();



        $schedule->command('app:pr_check_quantity_stock')
            ->timezone('Europe/Amsterdam')
            ->dailyAt( '12:18');

 */
        $schedule->command('app:wf_procedure_to_be_approved')
            ->timezone('Europe/Amsterdam')
            ->everyMinute();

        $schedule->command('app:process-quality-pdf')
            ->timezone('Europe/Amsterdam')
            ->everyFiveMinutes();

        $schedule->command('app:process-quality-ddc-pdf')
            ->timezone('Europe/Amsterdam')
            ->everyFiveMinutes();

    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
