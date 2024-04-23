<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class QtTypeTestDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:qt-type-test-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio reprot prove inserite.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = date('Y-m-d',strtotime( date('Y-m-d')." -1 days"));
        $tests = DB::table('qt_type_tests')->select('qt_type_tests.ol','qt_type_tests.materiale','qt_type_tests.esito','qt_categories.categoria')
            ->leftJoin('qt_categories','qt_categories.id','qt_type_tests.tipo')
            ->whereDate('qt_type_tests.created_at','=',$date)
            ->get();

        $users = Utility::users_notify('qt.prove.tipo.report');
        //$users = Greneric::users_notify(['qt_laboratory_notify'], 'qt-laboratory-external-user-day');
        if(count($tests))
            foreach ($users as $user) {
                $name = $user['name'];
                Mail::send('content/apps/quality/laboratory/mail/notilydaily', compact('tests','name','date'), function ($message) use ($user,$date) {
                    $message
                        ->to($user['email'])
                        ->subject('Prove di Tipo del '.$date);
                });
            }
    }
}
