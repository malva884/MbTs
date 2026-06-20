<?php

namespace App\Console\Commands;


use App\Jobs\FirmaCommesse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class SingWfOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sing_order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Firma Commesse';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $commesse = DB::table('wf_orders')
            ->select('id','commessa')
            ->whereNotIn('id',DB::table('wf_user_approvals')->select('model_id')->where('user_id',7))
            ->where('stato','In-Approval')
            ->whereNull('id_commessa_padre')
            ->orderBy('created_at', 'asc')
            ->orderBy('tipologia', 'asc')
            ->take(10)
            ->get();

        foreach ($commesse as $commessa){
            $obj = DB::table('wf_user_approvals')->where('model_id',$commessa->id)->where('user_id',7)->first();

            if(empty($obj->id))
                Dispatch(new FirmaCommesse($commessa->id));

        }
    }
}
