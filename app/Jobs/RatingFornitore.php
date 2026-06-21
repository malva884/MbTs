<?php

namespace App\Jobs;

use App\Models\QtSupplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RatingFornitore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id_fornitore;

    /**
     * Create a new job instance.
     */
    public function __construct($id_fornitore)
    {
        $this->id_fornitore = $id_fornitore;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fornitore = QtSupplier::find($this->id_fornitore);

        $certificati = DB::connection('sqlsrv_fornitori')->table('certifications')
            ->leftJoin('supplier_certifications','certifications.id','supplier_certifications.certificato_id','supplier_certifications.file_id')
            ->join('suppliers','supplier_certifications.fornitore_id','suppliers.id')
            ->select('certifications.*','supplier_certifications.valutazione','suppliers.prezzo','suppliers.servizio','supplier_certifications.file_id')
            ->where('supplier_certifications.approvato',true)
			->where('certifications.disattivo',false)
            ->where('supplier_certifications.fornitore_id',$this->id_fornitore)
            ->get();

        $tmp = ['VQ' => 7,'VEn' => 2,'VEt' => 2,'Vs' => 2 ];
        $v = [1 => 1, 2 => 0.9, 3 => 0.59, 4 => 1];
        $rating = 0;
        $cal = 0;

        foreach ($certificati as $certificato){
            $rating+= $certificato->valore_rating * $certificato->valutazione;
            if(!empty($certificato->file_id))
                $cal+= $tmp[$certificato->sigla] * 1;
        }

        $fornitore->rating = ((($rating + $cal) * $v[$certificato->prezzo] ) * $v[$certificato->servizio] );
        $fornitore->save();
    }
}
