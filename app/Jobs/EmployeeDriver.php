<?php

namespace App\Jobs;

use App\Jobs\WfLogCommessa;
use App\Models\HrEmployee;
use App\Models\WfUserApproval;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeDriver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $id;


    /**
     * Create a new job instance.
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $obj = HrEmployee::find($this->id);

        $newName = $obj->matricola.' ( '.$obj->nome_completo.' )';

        GoogleDrive::rename_dir($obj->path_drive,$newName,'google');
    }
}
