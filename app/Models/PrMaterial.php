<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrMaterial extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','materiale','um','valore','categorie','ragruppamento','data_ultimo_movimento','periodo','tipologia','updated_at'];

    static function getItems($categora,$latestDate = null)
    {
        $condizioni = explode(";", $categora);

        $sql = "SELECT * FROM AGG_PRODOTTI_TMP WHERE 1=1 ";
        foreach ($condizioni as $condizione){

            $struttura = explode("-", $condizione);
            if(count($struttura) != 3)
                continue;

            $tipoCondizione = $struttura[1];
            $condizione =  $struttura[0];
            switch ($tipoCondizione) {
                case '=':
                    $sql.=($condizione == '&' ? 'AND':'OR')." cdProdotto = '".substr($condizione, 2, strlen($condizione))."' ";
                    break;
                case '%':
                    $sql.=($condizione == '&' ? 'AND':'OR')." cdProdotto LIKE '".str_replace("*","%", $struttura[2]). "' ";
                    break;
                case '!%':
                    $sql.=($condizione == '&' ? 'AND':'OR')." cdProdotto NOT LIKE '".str_replace("*","%", $struttura[2])."' ";
                    break;
                case '!':
                    $sql.=($condizione == '&' ? 'AND':'OR')." cdProdotto <> '".substr($condizione, 2, strlen($condizione))."' ";
                    break;
            }
        }
        //$sql.="AND Valore < ".$categora->quantita;
        if($latestDate)
            $sql.="AND dataInserimento >= ".$latestDate." ";
        $sql.= "ORDER BY cdProdotto ";
        return DB::connection('sqlsrv_gp')->select($sql);

    }
}
