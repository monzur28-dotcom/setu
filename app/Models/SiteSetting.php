<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime presentation settings, read on nearly every front-page render, so
 * the whole table is cached as one row-set rather than queried per key.
 *
 * Defaults live here beside the clamps. A setting whose valid range is only
 * known by the form that writes it is a setting that breaks the day someone
 * writes to it another way.
 */
class SiteSetting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    private const CACHE_KEY = 'site_settings';

    /** key => [default, min, max] for the numeric ones. */
    public const NUMERIC = [
        // How much of the surface colour the doorway cards keep. 100 is a
        // solid card; 0 is invisible glass with only its border and blur.
        'door_tint' => [56, 0, 100],
        // The frost behind them, in pixels.
        'door_blur' => [16, 0, 30],

        // Typography. Ranges, not free numbers: 900 on a face that stops at
        // 700 is synthesised by the browser into something smeared, and
        // very light body text fails contrast for a lot of readers.
        'base_font_px'   => [15, 13, 19],
        'heading_weight' => [500, 400, 700],
        'body_weight'    => [400, 300, 600],
    ];

    public static function all_(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all_()[$key] ?? $default;
    }

    /** A numeric setting, clamped to its declared range whatever is stored. */
    public static function number(string $key): int
    {
        [$default, $min, $max] = self::NUMERIC[$key] ?? [0, 0, 100];

        $value = self::get($key);

        if ($value === null || ! is_numeric($value)) {
            return $default;
        }

        return (int) max($min, min($max, (int) $value));
    }

    public static function put(string $key, mixed $value, ?User $by = null): void
    {
        static::updateOrCreate(['key' => $key], [
            'value'      => (string) $value,
            'updated_by' => $by?->id,
        ]);

        Cache::forget(self::CACHE_KEY);
    }
}
