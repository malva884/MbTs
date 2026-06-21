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
        // $schedule->command('inspire')->hourly();
		
		// creazione log giornalienro dei diametri su google driver
		$schedule->command('app:log-diametri-daily')
            ->timezone('Europe/Amsterdam')
			->daily();
		
		// invio reminder fai giornalienro
		$schedule->command('app:fai_reminder_daily')
            ->timezone('Europe/Amsterdam')
            ->dailyAt('07:00');
		
		// invio report ftr giornalienro
		$schedule->command('app:ftr_optical_daily')
            ->timezone('Europe/Amsterdam')
			->dailyAt('22:00');
		
		// invio report checker settimanale
		$schedule->command('app:checker_reprot_weekly')
            ->timezone('Europe/Amsterdam')
            ->weekly();	
			
		// controllo strisciate gp
		$schedule->command('app:check_strisciate_gp')
            ->timezone('Europe/Amsterdam')
			->hourly();
            //->dailyAt('08:00');	
		
		// monitoraggio Client
		//$schedule->command('app:monitoring_client')
        //    ->timezone('Europe/Amsterdam')
        //    ->everyTwoMinutes();
		
		// monitoraggio asset	
		//$schedule->command('app:monitoring_asset')
        //    ->timezone('Europe/Amsterdam')
        //    ->everyMinute();
			
			
		// Prenotazioni Mensa
        /*$schedule->command('app:mensa_week')
            ->timezone('Europe/Amsterdam')
            ->dailyAt('10:30');*/
			
		// Richiesta Ferie	
		$schedule->command('app:hr-richiesta-giorni')
            ->timezone('Europe/Amsterdam')
            ->everyFiveMinutes();
			
		
		// Approvazioni Richiesta Ferie	
		$schedule->command('app:hr-approvazioni-richieste')
            ->timezone('Europe/Amsterdam')
			->everyThreeMinutes();		

		// Assenza Dipendenti
        $schedule->command('app:dipendenti_assenti')
            ->timezone('Europe/Amsterdam')
            ->dailyAt('04:00');	

		// Sollecito Richieste Dipendenti
        $schedule->command('app:hr-sollecito-richiesta-giorni')
            ->timezone('Europe/Amsterdam')
            ->dailyAt('00:30');	

		// Sincronizzazione Materiali da GP
		$schedule->command('app:sync_matariali')
            ->timezone('Europe/Amsterdam')
			->everyTwoHours($minutes = 0);

		// Check task Scaduti
        $schedule->command('app:scadenza_task')
            ->timezone('Europe/Amsterdam')
            ->dailyAt('09:30');		

		// Preposti Check List 
        $schedule->command('app:check-list')
            ->timezone('Europe/Amsterdam')
            ->monthly('06:00');	
		
		// Creazione Commesse/Revisioni
		$schedule->command('app:commesse')
            ->timezone('Europe/Amsterdam')
            ->everyThreeHours($minutes = 10);

		// Documenti Commesse
		$schedule->command('app:orderDocument')
            ->timezone('Europe/Amsterdam')
			->everyThreeHours($minutes = 15);
			
		// Inventory Adjustment
        $schedule->command('app:inventory_adjustment')
            ->timezone('Europe/Amsterdam')
            ->weeklyOn(1, '12:00');	
			
		// Notifiche nuovi ducumenti procedure
        $schedule->command('app:wf_procedure_to_be_approved')
            ->timezone('Europe/Amsterdam')
            ->dailyAt('18:00');		
		
		
		// Check Quantità Giacenza Materiali Magazzino
/*		$schedule->command('app:pr_check_quantity_stock')
            ->timezone('Europe/Amsterdam')
            ->dailyAt('10:17');
*/			

		
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
