<?php

namespace App\Imports;


use App\Models\PrMovement;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class PrMovementsImport implements ToModel, WithHeadingRow
{
    // WithHeadingRow

    public function startRow(): int
    {
        return 2;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        if(!empty($row[0])){
            Log::channel('stderr')->info($row[0]);
            $obj = new PrMovement();
            $obj->materiale = $row[0];
            $obj->descrizione = $row[1];
            $obj->quantita = $row[2];
            $obj->importo = $row[3];
            $obj->um = $row[4];
            $obj->lotto = $row[5];
            $obj->plant = $row[6];
            $obj->posizione_archiviazione = $row[7];
            $obj->tipo_movimento = $row[8];
            $obj->special_stock = $row[9];
            $obj->documento_materiale = $row[10];
            $data_pubblicazione = explode("/",$row[11]);
            $obj->data_pubblicazione = $data_pubblicazione[2].'-'.$data_pubblicazione[0].'-'.$data_pubblicazione[1];
            $data_documento = explode("/",$row[12]);
            $obj->data_documento = $data_documento[2].'-'.$data_documento[0].'-'.$data_documento[1];
            $data_inserimento = explode("/",$row[13]);
            $obj->data_inserimento = $data_inserimento[2].'-'.$data_inserimento[0].'-'.$data_inserimento[1];
            $obj->testo_movimento = $row[14];
            $obj->user = $row[15];
            $obj->save();

        }
    }
}
