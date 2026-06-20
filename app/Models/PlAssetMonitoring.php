<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlAssetMonitoring extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','asset_id','id_client','data','tipo_log','hostname','gp_stato','stl_app',
        'portale_stato','dc_stato','ip_uno_stato','ip_due_stato','ip_tre_stato','ip_quatro_stato','ip_cinque_stato'];
}
