<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;



class FaiReminderDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fai_reminder_daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio notifica dei fai aperti.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $objs = DB::table('qt_fais')
            ->select('*')
            ->whereNull('risultato')
            ->orderBy('data_creazione','asc')
            ->get();

        $users = Utility::users_notify(['test_system']);

        Mail::send('emails/email_fai_reminder', compact('objs'), function ($message) use ($users) {
            $message
                ->to($users)
                ->subject('FAI Daily Reminder');
        });
    }
}
