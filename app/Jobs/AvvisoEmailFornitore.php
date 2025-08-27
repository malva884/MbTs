<?php

namespace App\Jobs;

use App\Models\FiShippedHead;
use App\Models\FiShippedRow;
use App\Models\QtSupplierNotice;
use App\Models\QtSupplierUser;
use App\Models\Utility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AvvisoEmailFornitore implements ShouldQueue
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
        $obj = QtSupplierNotice::find($this->id);

        $users = DB::connection('sqlsrv_fornitori')
            ->table('users')
            ->where('supplier_id',$obj->supplier_id)
            ->pluck('email');

        $info = [
            'titolo' => $obj->titolo,
            'periodo' =>$obj->testo,
        ];


        $subject = 'Report Spedito';


        Mail::send('emails/email_spedito', compact('info'), function ($message) use ($users,$subject) {
            $message
                ->to($users)
                ->from("portale.metallurgica@stl.tech", "Metallurgica Bresciana S.p.a")
                ->subject($subject);
        });
    }
}
