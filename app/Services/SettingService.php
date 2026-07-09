<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * Get a setting value by key with caching
     */
    public function get(string $key, $default = null)
    {
        return Cache::remember("settings.{$key}", 3600, function() use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $this->castValue($setting) : $default;
        });
    }

    /**
     * Set a setting value and clear cache
     */
    public function set(string $key, $value, string $type = 'string', string $group = 'general', string $description = null): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        $this->clearCache($key);

        return $setting;
    }

    /**
     * Get all settings by group
     */
    public function getByGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function() use ($group) {
            return Setting::where('group', $group)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => $this->castValue($setting)];
                })
                ->toArray();
        });
    }

    /**
     * Get all settings
     */
    public function all(): array
    {
        return Cache::remember('settings.all', 3600, function() {
            return Setting::all()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => $this->castValue($setting)];
                })
                ->toArray();
        });
    }

    /**
     * Clear cache for a specific key
     */
    public function clearCache(string $key): void
    {
        Cache::forget("settings.{$key}");
        Cache::forget('settings.all');
    }

    /**
     * Clear all settings cache
     */
    public function clearAllCache(): void
    {
        Cache::forget('settings.all');
        Setting::all()->each(function ($setting) {
            Cache::forget("settings.{$setting->key}");
        });
    }

    /**
     * Cast value based on type
     */
    protected function castValue(Setting $setting)
    {
        $value = $setting->value;

        return match ($setting->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            'float' => (float) $value,
            default => $value,
        };
    }
}
