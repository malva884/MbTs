<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToQuoteCable extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','preventivo_id','cavo_id','codice','descrizione','metri','scarto','costo_scarto','diametro','bobina_id',
        'bobina','bobina_numero','costo_bobina','totale_costo_bobine','peso','peso_materie','m3','m3_totale',
        'pezzatura','costo','parametro','costo_manodopera','somma_materiali','costo_materiali','netto','lordo','variante_rame','calcolato','posizione','nota','company_id','norma'];

    public function preventivo_obj()
    {
        return $this->hasOne(ToQuote::class,'id','preventivo_id');
    }

    public function calcola_totali(){
        try{

            $struttura = DB::table('to_quote_cable_structures')->select('to_quote_cable_structures.*','to_center_costs.costo as costoCentro','to_materials.costo as costo_materia')
                ->leftJoin('to_center_costs','to_center_costs.centro','to_quote_cable_structures.centro')
                ->leftJoin('to_materials','to_materials.materiale','to_quote_cable_structures.materiale')
                ->where('cavo_id','=',$this->id)->get();

            $total=['mp'=>0.00,'mano'=>0.00,'smp'=>0.00];
            $rame = 0;
            $peso = 0;
            foreach ($struttura as $row){

                if(substr($row->materiale ,0,2) == "RA")
                    $rame+=$row->peso;

                if(substr($row->materiale ,0,2) != "FO")
                    $peso = $peso + $row->peso;

                $total['mp'] = $total['mp'] + $row->costo_materia_prima;
                if(substr($row->materiale ,0,2) != "FO" && !in_array(substr($row->materiale ,0,3),['FRP','GFW','UPC']))
                    $total['smp'] = $total['smp'] + $row->costo_materia_prima;
                $total['mano'] = $total['mano'] + $row->costo_lavorazione;
            }

            $scarto = round(($total['mp'] * $this->scarto) / 100,4);
            $netto = round($this->metri * $peso,4);
            $sum_peso_bobbine = round(($this->peso * $this->bobina_numero) ,4).'000';
            $lordo = $sum_peso_bobbine + $netto;

            $variante = 0.000;
            if($rame)
                $variante = round(((($this->scarto / 100) + 1) * $rame) / 1000 , 4);

            if($variante > 0)
                $this->costo = $total['mp'] + $total['mano'] + $scarto + ($variante * $this->preventivo_obj->cu);
            else
                $this->costo = $total['mp'] + $total['mano'] + $scarto ;

            $cu = 0.00;
            if(!empty($this->preventivo_obj->cu))
                $cu = round($variante * $this->preventivo_obj->cu,4);

            $this->parametro = round(($total['mp'] + $total['mano']) / $this->preventivo_obj->parametro, 4);
            $this->costo_manodopera = $total['mano'];
            $this->costo_materiali =  $total['mp'] + $scarto + $cu;
            $this->somma_materiali = $total['smp'];
            $this->costo_scarto = $scarto;
            $this->netto = $netto;
            $this->lordo = $lordo;
            $this->variante_rame = $variante;
            $this->peso_materie = $peso;
            $this->save();
            Log::channel('stderr')->info((array)$this);


        }catch (\Exception $e){

        }
    }
}
