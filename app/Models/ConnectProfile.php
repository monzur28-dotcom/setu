<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ---------------------------------------------------------------------------
 *  CONNECT — the second product, behind the wall.
 * ---------------------------------------------------------------------------
 *  This model shares NOTHING with Profile. No inheritance, no shared trait,
 *  no shared scope. The only link between the two is `user_id`, and reading
 *  across it is restricted to the PRIVACY role via ConnectWall.
 *
 *  If you find yourself wanting to join this to `profiles`, stop and read
 *  chapter 4.3 of the specification first.
 * ---------------------------------------------------------------------------
 */
class ConnectProfile extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['interests' => 'array', 'last_active_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function photos(): HasMany { return $this->hasMany(ConnectPhoto::class)->orderBy('order'); }
    public function prompts(): HasMany { return $this->hasMany(ConnectPrompt::class); }
    public function preference(): HasOne { return $this->hasOne(ConnectPreference::class); }

    public static function generateConnectId(): string
    {
        // W7: deliberately a different shape from profile_id, and not
        // derivable from it.
        do {
            $id = 'CX'.strtoupper(bin2hex(random_bytes(4)));
        } while (static::where('connect_id', $id)->exists());

        return $id;
    }

    public function scopeAvailable(Builder $q): Builder
    {
        return $q->where('status', 'ACTIVE')
                 ->whereHas('user', fn (Builder $u) => $u->whereIn('status', ['ACTIVE'])
                                                          ->where('dating_enabled', true));
    }

    /** Absolute and silent, in both directions. */
    public function scopeNotBlockedWith(Builder $q, int $viewerId): Builder
    {
        return $q->whereNotExists(fn ($s) => $s->selectRaw(1)->from('connect_blocks')
                ->whereColumn('connect_blocks.blocked_connect_id', 'connect_profiles.id')
                ->where('connect_blocks.blocker_connect_id', $viewerId))
            ->whereNotExists(fn ($s) => $s->selectRaw(1)->from('connect_blocks')
                ->whereColumn('connect_blocks.blocker_connect_id', 'connect_profiles.id')
                ->where('connect_blocks.blocked_connect_id', $viewerId));
    }

    public function isMatchedWith(int $otherId): bool
    {
        return ConnectMatch::between($this->id, $otherId)?->status === 'ACTIVE';
    }

    /**
     * Photos are blurred until a mutual match, by default. The member may
     * choose otherwise; the default protects those who never open settings.
     */
    public function photosVisibleTo(?int $viewerConnectId): bool
    {
        if ($viewerConnectId === $this->id) {
            return true;
        }

        if ($this->photo_visibility === 'VISIBLE_TO_SUGGESTIONS') {
            return true;
        }

        return $viewerConnectId !== null && $this->isMatchedWith($viewerConnectId);
    }
}
