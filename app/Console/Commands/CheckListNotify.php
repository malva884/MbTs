<?php

namespace App\Console\Commands;

use App\Http\Controllers\UserController;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class CheckListNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio email check list preposto';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $users = Utility::users_notify(['check-list-preposto']);

        Mail::send('emails/emailCheckList', [], function ($message) use($users){
            $message
                ->to($users)
                ->subject('Preposto Check List '. date('MM/Y'));
        });

    }
}
