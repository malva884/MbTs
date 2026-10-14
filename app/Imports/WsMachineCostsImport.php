<?php

namespace App\Imports;

use App\Models\WsMachineCost;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class WsMachineCostsImport implements ToModel, WithStartRow
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

        return new WsMachineCost([
            'machine' => (string)$row[0],
            'cost' => (float)($row[1] ?? 0),
            'manpower_cost' => isset($row[2]) ? (float)$row[2] : null,
            'date_import' => date('Y-m-d'),
        ]);
    }
}
