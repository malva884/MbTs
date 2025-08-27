<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if(auth()->user()){
            $builder->where('company_id', auth()->user()->company_id);
        }
    }
}
