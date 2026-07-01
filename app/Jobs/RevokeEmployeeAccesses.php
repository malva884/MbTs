<?php

namespace App\Jobs;

use App\Models\HrEmployeeAccess;
use App\Models\HrEmployee;
use App\Services\GoogleDrive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RevokeEmployeeAccesses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employeeId;

    public function __construct($employeeId)
    {
        $this->employeeId = $employeeId;
    }

    public function handle(): void
    {
        $employee = HrEmployee::find($this->employeeId);

        if (!$employee) {
            return;
        }

        $accesses = HrEmployeeAccess::with('accessResource')
            ->where('employee_id', $this->employeeId)
            ->get();

        foreach ($accesses as $access) {
            $resource = $access->accessResource;
            if ($resource && $resource->drive_file_id && $employee->email) {
                try {
                    GoogleDrive::removeShared($resource->drive_file_id, $employee->email);
                } catch (\Exception $e) {
                    Log::error("Errore revoca Google Drive per dipendente {$employee->matricola}, resource {$resource->id}: " . $e->getMessage());
                }
            }
            $access->delete();
        }
    }
}
