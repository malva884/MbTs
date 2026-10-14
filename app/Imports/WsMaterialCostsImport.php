<?php

namespace App\Imports;

use App\Models\WsMaterialCost;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class WsMaterialCostsImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 2;
    }

    /**
     * @param  array  $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if (empty($row[0]))
            return null;

        return new WsMaterialCost([
            'material' => (string)$row[0],
            'cost' => (float)($row[13] ?? 0),
            'date_import' => date('Y-m-d'),
        ]);
    }
}
