<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuardianLink extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime', 'revoked_at' => 'datetime', 'created_profile' => 'boolean'];
    }

    public function guardian(): BelongsTo { return $this->belongsTo(User::class, 'guardian_user_id'); }
    public function candidate(): BelongsTo { return $this->belongsTo(User::class, 'candidate_user_id'); }
    public function accessLogs(): HasMany { return $this->hasMany(GuardianAccessLog::class); }
    public function notes(): HasMany { return $this->hasMany(GuardianNote::class); }

    public function isActive(): bool
    {
        return $this->link_status === 'ACTIVE' && $this->revoked_at === null;
    }

    public function level(): int
    {
        return match ($this->visibility_level) {
            'L3_FULL'          => 3,
            'L2_INTRODUCTIONS' => 2,
            default            => 1,
        };
    }

    /**
     * Twelve things a guardian can never do, regardless of level, tier or
     * support request. These are not settings — the capability does not
     * exist in the codebase. Spec 12.2 (G1–G12).
     */
    public function may(string $capability): bool
    {
        return match ($capability) {
            'see_progress'        => true,
            'keep_notes'          => true,
            'see_connected'       => $this->level() >= 2,
            'see_pending'         => $this->level() >= 3,
            'see_shortlist'       => $this->level() >= 3,
            'contact_other_family'=> $this->level() >= 2,

            // Never. At any level. For anyone. Support cannot override.
            'read_mailbox'        => false,   // G4
            'act_on_interest'     => false,   // G5
            'edit_profile'        => false,   // G6
            'upload_photos'       => false,   // G6
            'see_connect'         => false,   // G12 — the wall
            default               => false,
        };
    }
}
