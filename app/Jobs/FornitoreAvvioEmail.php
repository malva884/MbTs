<?php

namespace App\Jobs;


use App\Models\QtSupplierNotice;
use App\Models\QtSupplierUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class FornitoreAvvioEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $id_supplier;
    protected $id_notice;

    /**
     * Create a new job instance.
     */
    public function __construct($id,$notice)
    {
        $this->id_supplier = $id;
        $this->id_notice = $notice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $objs = QtSupplierUser::where('supplier_id',$this->id_supplier)->get();
        $notice = QtSupplierNotice::find($this->id_notice);
        $subject = $notice->titolo;
        $users = [];
        foreach ($objs as $obj)
            if(filter_var($obj->email, FILTER_VALIDATE_EMAIL))
                $users[] = $obj->email;


        Mail::send('emails/email_fornitore_notice', compact('notice'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->subject($subject);
        });
    }
}
