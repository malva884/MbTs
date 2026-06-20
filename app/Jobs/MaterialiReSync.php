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

class MaterialiReSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $tag = null;

    /**
     * Create a new job instance.
     */
    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mateliali = PrMaterial::where('categorie', 'like','%'.$this->tag.'%')->get();
        foreach ($mateliali as $mateliale){
            $categorie = explode(" ",$mateliale->categorie);
            if (($key = array_search($this->tag, $categorie)) !== false)
                unset($categorie[$key]);

            $mateliale->categorie = implode(" ", $categorie);
            $mateliale->save();
        }

        $categorie = PrStockCategorie::all();

        foreach ($categorie as $categoria){

            $date = '2000-01-01';
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
