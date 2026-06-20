<?php

namespace App\Console\Commands;

use App\Http\Controllers\UserController;
use App\Models\QtSupplierCertification;
use App\Models\Task;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class ScadenzaTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scadenza_task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio promemoria scadenza certificazione task.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = Task::where('data_scadenza',date('Y-m-d'))
            ->where('stato','<>',2)
            ->get();

        foreach ($tasks as $task){
            $users = DB::table('task_user_assigneds')
                ->join('users','task_user_assigneds.user_id','users.id')
                ->where('task_id',$task->id)
                ->pluck('email');

            $content = 'Ciao, Il task '.$task->codice. ' è scaduto.';

            Mail::send('emails/email_white', ['content' => $content], function ($message) use($users,$task){
                $message
                    ->to($users)
                    ->subject('Task Scaduto '. $task->codice);
            });
        }
    }
}
