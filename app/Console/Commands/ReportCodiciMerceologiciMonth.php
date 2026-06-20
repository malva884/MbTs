<?php

namespace App\Console\Commands;

use App\Models\Gp;
use App\Models\Utility;
use App\Services\GoogleDrive;
use App\Services\GoogleSheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Revolution\Google\Sheets\Facades\Sheets;

class ReportCodiciMerceologiciMonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:report-cod-merceologici-month';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprot mensile sulla produzione per codice merceologici';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);

        $codici = DB::table('product_codes')
            ->where('disattivo', false)
            ->orderBy('created_at','asc')
            ->get();

        $materialiShhet[]= ['Material','Quantity','Product Codes'];
        $reportSheepLabel = [];
        $filter = '-1 Months';
        $reportSheep[]= [Date('Y-M',strtotime($filter))];
        $month = date('m',strtotime($filter));
        $t = substr($month,0,1);
        $titolo = Date('Y-m-d H:i',strtotime($filter));
        //$codici = ['AD-CP','MC-CP','F6-NC','FV-CP','AR-NC','F7-NC','MC-NC','FV-NC','MM-NC','DP-CP','FT-CP','FT-NC','AD-NC','BR-NC',''];

        foreach ($codici as $codice){
            $resultMateriali = Gp::reportCodMerceologico($codice,Date('Y',strtotime($filter)), Date('m',strtotime($filter)));

            $reportSheepLabel[] =[$codice];
            $reportSheep[] = [$resultMateriali->get()->sum('quantita')];
            foreach ($resultMateriali->get() as $materiale)
                $materialiShhet[] = [$materiale->cdProdotto,(float)str_replace('.',',',$materiale->quantita),$codice];
        }

        //env('ID_GOOGLE_PRODUCT_CODES')
        $fileReprot = GoogleDrive::search('1ahSiH3d_pQBdFp2r5Xe0TJFWaeNO571L', 'google', 'file', date('Y'), false);
        if(empty($fileReprot)){
            $fileReprot = GoogleSheet::createSheet(date('Y'),$fileReprot);
            Sheets::spreadsheet($fileReprot);
            Sheets::addSheet('Report');
            Sheets::deleteSheet('Foglio1');
            Sheets::sheet('Report')->range('A2')->update($reportSheepLabel);
        }
        else
            Sheets::spreadsheet($fileReprot);

        $alphabet = range('A', 'Z');
        $month = ($t == '0' ? substr($month,1,2) : $month);
        $columnStart = $alphabet[$month];

        Sheets::sheet('Report')->range($columnStart.'1')->update($reportSheep);

        Sheets::addSheet('Details '.$titolo);
        Sheets::sheet('Details '.$titolo)->range('A1')->update($materialiShhet);

    }
}
