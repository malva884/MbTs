<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Usato solo per leggere il file "Stato Lavori" in array grezzo
 * tramite Excel::toArray(). La persistenza e' gestita da WsRow::storeRow().
 */
class WsRowsImport implements WithStartRow
{
    public function startRow(): int
    {
        return 2;
    }
}
