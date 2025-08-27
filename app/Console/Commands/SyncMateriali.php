<?php

namespace App\Console\Commands;


use App\Jobs\MaterialiSync;
use Illuminate\Console\Command;


class SyncMateriali extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync_matariali';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Avvio sincronizazione MAteriali con gp.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        MaterialiSync::dispatch();
    }
}
