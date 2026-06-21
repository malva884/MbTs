<?php

namespace App\Console\Commands;

use App\Http\Controllers\UserController;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class MensaWeek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mensa_week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio prenotazioni mensa settimana.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		
		$giorno = date("D");
		 if ($giorno != 'Sat' && $giorno != 'Sun'){
			$g = new UserController();
			$g->mensa();
		 }
		
    }
}
