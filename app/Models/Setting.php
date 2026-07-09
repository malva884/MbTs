<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    protected static function booted()
    {
        static::saved(function ($setting) {
            app(\App\Services\SettingService::class)->clearCache($setting->key);
        });

        static::deleted(function ($setting) {
            app(\App\Services\SettingService::class)->clearCache($setting->key);
        });
    }
}
