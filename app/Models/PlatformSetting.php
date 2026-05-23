<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $primaryKey = 'cle';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['cle', 'valeur', 'description'];

    public static function get(string $cle, ?string $default = null): ?string
    {
        return Cache::remember("platform_setting.{$cle}", 300, function () use ($cle, $default) {
            return static::query()->find($cle)?->valeur ?? $default;
        });
    }

    public static function set(string $cle, ?string $valeur, ?string $description = null): void
    {
        static::query()->updateOrCreate(
            ['cle' => $cle],
            array_filter(['valeur' => $valeur, 'description' => $description], fn ($v) => $v !== null)
        );
        Cache::forget("platform_setting.{$cle}");
    }
}
