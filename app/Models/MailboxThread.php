<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MailboxThread extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['last_message_at' => 'datetime']; }

    public function messages(): HasMany
    {
        return $this->hasMany(MailboxMessage::class, 'thread_id')->orderBy('created_at');
    }

    public function exchange(): HasOne
    {
        return $this->hasOne(ContactExchange::class, 'thread_id');
    }

    public function otherProfileId(int $mine): int
    {
        return $this->profile_a_id === $mine ? $this->profile_b_id : $this->profile_a_id;
    }

    public function includes(int $profileId): bool
    {
        return in_array($profileId, [$this->profile_a_id, $this->profile_b_id], true);
    }

    /** Contact details pass only when BOTH sides have acted. Never sold. */
    public function contactExchanged(): bool
    {
        return (bool) $this->exchange?->accepted_at;
    }

    public static function between(int $a, int $b): self
    {
        [$lo, $hi] = $a < $b ? [$a, $b] : [$b, $a];

        return static::firstOrCreate(
            ['profile_a_id' => $lo, 'profile_b_id' => $hi],
            ['last_message_at' => now()],
        );
    }
}
