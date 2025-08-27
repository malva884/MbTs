<?php

namespace App\Imports;

use App\Models\PlAsset;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WorkStatusImport implements ToModel, WithHeadingRow
{
    // WithHeadingRow

    public function __construct()
    {

    }

    public function startRow(): int
    {
        return 1;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

    }

}
