<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectLike extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array { return ['created_at' => 'datetime']; }

    /**
     * A like is recorded silently. The recipient is NOT told, by any channel,
     * unless it becomes mutual — no count granularity that would identify
     * them, no timestamp, no event. Spec 27.3 S2.
     */
    public static function record(int $from, int $to, string $action): bool
    {
        static::updateOrCreate(
            ['from_connect_id' => $from, 'to_connect_id' => $to],
            ['action' => $action, 'created_at' => now()],
        );

        if ($action !== 'LIKE') {
            return false;
        }

        $reciprocal = static::where('from_connect_id', $to)
            ->where('to_connect_id', $from)
            ->where('action', 'LIKE')
            ->exists();

        if ($reciprocal) {
            ConnectMatch::forPair($from, $to);

            return true;   // mutual — and only now does either side learn anything
        }

        return false;
    }
}
