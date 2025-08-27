<?php

namespace App\Jobs;

use App\Models\PrMaterial;
use App\Models\PrStockCategorie;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MaterialiSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $categorie = PrStockCategorie::all();

        foreach ($categorie as $categoria){
            $latestDate = DB::table('pr_materials')
                ->select('updated_at')
                ->where('categorie','LIKE','%'.$categoria->tag.'%')
                ->orderBy('updated_at','desc')
                ->first();

            $date = null;
            if(!empty($latestDate->updated_at))
                $date = $latestDate->updated_at;

            $result = PrMaterial::getItems($categoria->condizioni, $date);
            foreach ($result as $giacenza){
                $obj = PrMaterial::where('materiale',$giacenza->cdProdotto)->first();
                if(empty($obj->id))
                    $obj = new PrMaterial();

                $obj->materiale = $giacenza->cdProdotto;
                $obj->um = $giacenza->cdUM;
                $obj->valore = $giacenza->Valore;
                $obj->categorie = (strpos($obj->categorie, $categoria->tag) ? $obj->categorie : $obj->categorie.' '.$categoria->tag );
                $obj->ragruppamento = $giacenza->dcRaggruppamentoPF;
                $obj->data_ultimo_movimento = $giacenza->dtUltimoMovimento;
                $obj->tipologia = $giacenza->cdMateriale;
                //$obj->periodo = date('Y-m-d');
                $obj->save();
            }

        }
    }
}
