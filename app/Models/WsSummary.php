<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WsSummary extends Model
{
    use HasFactory;

    protected $table = 'work_course_summaries';

    protected $fillable = [
        'id', 'order', 'head', 'total_material', 'total_consuntivo', 'note',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'total_material' => 'float',
        'total_consuntivo' => 'float',
    ];
}
