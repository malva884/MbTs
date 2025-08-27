<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Revolution\Google\Sheets\Facades\Sheets;

class QtCheckerReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id','ol','user','date_create','num_fo','coil', 'fo_try', 'stage', 'not_conformity','note','km','lavorazione','tipo_cavo'
    ];

    static function getSheet()
    {
        return  Sheets::spreadsheet('1Fx3y_JNkGrMwSeOTxfnADwpy8xTmUcNcM92M0zsHzaE')->sheet('TOT BOB X OL')->all();
    }
}
