<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WsHead extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'work_status_heads';

    protected $fillable = [
        'id', 'status', 'total_final_mp', 'total_mp', 'total_old', 'total_final',
        'total_raw_material', 'total_raw_material2', 'total_machine_manpower_cost',
        'total_machine_manpower_cost2', 'raw_material_machine_manpower_cost',
        'import_error', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'total_final_mp' => 'float',
        'total_final' => 'float',
        'total_raw_material' => 'float',
        'total_machine_manpower_cost' => 'float',
        'raw_material_machine_manpower_cost' => 'float',
    ];

    public function rows()
    {
        return $this->hasMany(WsRow::class, 'head');
    }

    public function summaries()
    {
        return $this->hasMany(WsSummary::class, 'head');
    }
}
