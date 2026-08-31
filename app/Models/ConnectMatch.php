<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectMatch extends Model
{
    protected $table = 'connect_matches';
    protected $guarded = ['id'];

    protected function casts(): array { return ['matched_at' => 'datetime']; }

    public function messages(): HasMany
    {
        return $this->hasMany(ConnectMessage::class, 'match_id')->orderBy('created_at');
    }

    public static function between(int $a, int $b): ?self
    {
        [$lo, $hi] = $a < $b ? [$a, $b] : [$b, $a];

        return static::where('a_connect_id', $lo)->where('b_connect_id', $hi)->first();
    }

    /** Created ONLY on a mutual like. There is no other path to contact. */
    public static function forPair(int $a, int $b): self
    {
        [$lo, $hi] = $a < $b ? [$a, $b] : [$b, $a];

        return static::firstOrCreate(
            ['a_connect_id' => $lo, 'b_connect_id' => $hi],
            ['matched_at' => now(), 'status' => 'ACTIVE'],
        );
    }

    public function otherId(int $mine): int
    {
        return $this->a_connect_id === $mine ? $this->b_connect_id : $this->a_connect_id;
    }

    public function includes(int $connectId): bool
    {
        return in_array($connectId, [$this->a_connect_id, $this->b_connect_id], true);
    }
}
