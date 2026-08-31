<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Introduction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'expires_at' => 'datetime', 'connected_at' => 'datetime'];
    }

    public function a(): BelongsTo { return $this->belongsTo(Profile::class, 'profile_a_id'); }
    public function b(): BelongsTo { return $this->belongsTo(Profile::class, 'profile_b_id'); }

    public function sideFor(int $profileId): string
    {
        return $this->profile_a_id === $profileId ? 'a' : 'b';
    }

    /**
     * A member sees only their OWN status. The other side's column is never
     * serialised — you are never told you were passed on, and never told
     * someone is waiting. Only mutual interest produces a signal. Spec 16.2.
     */
    public function myStatus(int $profileId): string
    {
        return $this->{'status_'.$this->sideFor($profileId)};
    }

    public function isMutual(): bool
    {
        return $this->status_a === 'INTERESTED' && $this->status_b === 'INTERESTED';
    }
}
