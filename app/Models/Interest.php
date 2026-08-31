<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function from(): BelongsTo { return $this->belongsTo(Profile::class, 'from_profile_id'); }
    public function to(): BelongsTo { return $this->belongsTo(Profile::class, 'to_profile_id'); }

    /**
     * A declined interest is reported to the sender as "no longer pending",
     * never as an explicit decline, and the reason is never disclosed.
     * Spec 16.2 — the sender must not be able to distinguish decline from expiry.
     */
    public function statusForSender(): string
    {
        return in_array($this->status, ['DECLINED', 'EXPIRED'], true) ? 'CLOSED' : $this->status;
    }
}
