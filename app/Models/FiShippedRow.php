<?php

namespace App\Models;

use App\Traits\TenantAttributeTrait;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiShippedRow extends Model
{
    use HasFactory, HasUuids, TenantAttributeTrait, TenantScoped;

    protected $fillable = ['id','head','date_row','code_client','client','item','material','description','type','commessa','code_recipient','recipient','unit','qty_value','cost_value','fiber_counter','delivered_qty','qty_fkm','price_km','cost_km','std_price','order','net_profit','profit_perc','exchange_rate','postal_code','city','document','km_distance','company_id'];

}
