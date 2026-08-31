<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
    }

    /** Append-only. Retained seven years. */
    public static function write(?User $actor, string $action, array $ctx = []): void
    {
        static::create([
            'actor_id'    => $actor?->id,
            'actor_role'  => $actor?->role,
            'action'      => $action,
            'entity_type' => $ctx['entity_type'] ?? null,
            'entity_id'   => $ctx['entity_id'] ?? null,
            'before'      => $ctx['before'] ?? null,
            'after'       => $ctx['after'] ?? null,
            'ip'          => request()?->ip(),
        ]);

        Log::channel('audit')->info($action, ['actor' => $actor?->profile_id] + $ctx);
    }
}
