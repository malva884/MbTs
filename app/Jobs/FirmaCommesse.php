<?php

namespace App\Jobs;

use App\Jobs\WfLogOrdrer;
use App\Models\WfOrder;
use App\Models\WfUser;
use App\Models\WfUserApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FirmaCommesse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $id;
    protected $user = [7 => 'Walter Forzanini', 34 => 'Luca Facchinetti'];

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
        foreach ($this->user as $user => $name){
            $completed = WfUserApproval::approval($this->id, 'WfOrder', $user, 'A13A37C5-5AE2-4F11-85E0-4301076E1A70', 'Approved', null);

            if($completed)
                DB::table("wf_orders")
                    ->where('id',$this->id)
                    ->orWhere('id_commessa_padre',$this->id)
                    ->update(['stato' => 'Approved', 'data_approvazione' => date('Y-m-d')]);

        }

		Dispatch(new WfLogOrdrer($this->id));
		//WfOrder::Log($this->id);
        //Dispatch(new WfLogCommessa($this->id));
    }
}
