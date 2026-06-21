<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Revolution\Google\Sheets\Facades\Sheets;


class CheckerReportWeekly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:checker_reprot_weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report Checker settimanle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);

        $report = DB::table('qt_checker_reports')
            ->selectRaw("full_name, SUM(CASE WHEN stage = 'BUF' THEN km END) as km_buf, SUM(CASE WHEN stage <> 'BUF' THEN km END) as km, SUM(fo_try) as fo_try, COUNT(*) as bob")
            ->join('users','users.id','qt_checker_reports.user')
            ->where('lavorazione',5420)
            ->whereYear('date_create',date('Y'))
            ->whereMonth('date_create',date('m'))
            ->groupBy(['full_name'])
            ->get();

        $emails = Utility::users_notify(['qt_checker_report_settimanale']);

        Mail::send('emails/email_checker_report_weekly', compact('report'), function ($message) use ($emails) {
            $message
                ->to($emails)
                ->subject('Report Checker');
        });
    }
}
