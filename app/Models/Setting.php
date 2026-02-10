<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_group',
    ];

    private static string $cacheKey = 'app_settings_all';
    private static int $cacheTtl = 3600; // 1 hour

    public static function getValue(string $key, string $default = ''): string
    {
        $settings = static::getAllCached();
        return $settings[$key] ?? $default;
    }

    public static function getAllCached(): array
    {
        return Cache::remember(static::$cacheKey, static::$cacheTtl, function () {
            return static::pluck('setting_value', 'setting_key')->toArray();
        });
    }

    public static function setValue(string $key, ?string $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value, 'setting_group' => $group]
        );
        static::clearCache();
    }

    public static function clearCache(): void
    {
        Cache::forget(static::$cacheKey);
    }
}
