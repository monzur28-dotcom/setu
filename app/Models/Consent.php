<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Consent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'granted'    => 'boolean',
            'evidence'   => 'array',
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** A consent is a row with evidence, never a boolean column. Spec 16.4. */
    public static function record(?int $userId, string $type, ?Request $request = null, array $extra = []): self
    {
        return static::create([
            'user_id'      => $userId,
            'consent_type' => $type,
            'granted'      => true,
            'version'      => $extra['version'] ?? '1.0',
            'expires_at'   => $extra['expires_at'] ?? null,
            'evidence'     => array_filter([
                'ip'         => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'at'         => now()->toIso8601String(),
            ]) + ($extra['evidence'] ?? []),
        ]);
    }

    public static function revoke(int $userId, string $type): void
    {
        static::where('user_id', $userId)->where('consent_type', $type)
            ->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }
}
