<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Entitlement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date'];
    }

    public function remaining(): int
    {
        return max(0, $this->allowance - $this->used);
    }

    /**
     * Decrement inside a transaction with a row lock. Checking and then
     * writing without a lock is how members get free interests. Spec 22.6.
     */
    public static function consume(int $userId, string $key, int $allowance): bool
    {
        return DB::transaction(function () use ($userId, $key, $allowance) {
            $row = static::where('user_id', $userId)
                ->where('key', $key)
                ->where('period_start', today())
                ->lockForUpdate()
                ->first();

            $row ??= static::create([
                'user_id'      => $userId,
                'key'          => $key,
                'allowance'    => $allowance,
                'used'         => 0,
                'period_start' => today(),
                'period_end'   => today(),
            ]);

            if ($row->used >= $row->allowance) {
                return false;
            }

            $row->increment('used');

            return true;
        });
    }
}
