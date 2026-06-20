<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QtSupplierQuestionnaire extends Model
{
    use HasUuids;

    protected $connection = 'sqlsrv_fornitori';
    protected $table = 'questionnaires';

    protected $fillable = [
        'id',
        'supplier_id',
        'certificato_id',
        'stato',
        'r_1',
        'r_2',
        'r_3',
        'r_4',
        'r_5',
        'r_6',
        'r_7',
        'r_8',
        'r_9',
        'r_10',
        'r_11',
        'r_12',
        'r_13',
        'r_14',
        'r_15',
        'r_16',
        'r_17',
        'r_18',
        'r_19',
        'r_20',
        'r_21',
        'r_22',
        'r_23',
        'r_24',
        'r_25',
        'r_26',
        'r_27',
        'r_28',
        'r_29',
        'r_30',
        'r_31',
        'r_32',
        'r_33',
        'r_34',
        'r_35',
        'r_36',
        'r_37',
        'r_38',
        'r_39',
        'r_40',
        'r_41',
        'r_42',
        'r_43',
        'r_44',
        'r_45',
    ];
}
