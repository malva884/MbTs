<?php
namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait TenantAttributeTrait
{
    public static function bootTenantAttributeTrait()
    {
        static::creating(function ($model) {
            $model->company_id = auth()->user()->company_id;
        });


        static::retrieved(function ($model) {

            $model->where('company_id', auth()->user()->company_id);
        });
    }
}
