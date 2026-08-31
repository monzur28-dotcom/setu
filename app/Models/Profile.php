<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_of_birth'   => 'date',
            'languages_known' => 'array',
            'moderated_at'    => 'datetime',
            'submitted_at'    => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function visibility(): HasOne { return $this->hasOne(ProfileVisibility::class); }
    public function location(): HasOne { return $this->hasOne(ProfileLocation::class); }
    public function career(): HasOne { return $this->hasOne(ProfileCareer::class); }
    public function family(): HasOne { return $this->hasOne(ProfileFamily::class); }
    public function lifestyle(): HasOne { return $this->hasOne(ProfileLifestyle::class); }
    public function preference(): HasOne { return $this->hasOne(Preference::class); }
    public function photos(): HasMany { return $this->hasMany(Photo::class)->orderBy('order'); }

    public function approvedPhotos(): HasMany
    {
        return $this->photos()->where('status', 'APPROVED');
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'from_profile_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'to_profile_id');
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth?->age ?? 0;
    }

    /** True while an edit is queued beside the approved text. */
    public function hasPendingEdit(): bool
    {
        return $this->pending_headline !== null || $this->pending_about_me !== null;
    }

    public function isPublished(): bool
    {
        return $this->moderation_status === 'APPROVED';
    }

    /** First name only unless the owner opted into showing the full name. */
    public function displayName(bool $full = false): string
    {
        $name = $this->user->candidate_name;

        return $full ? $name : explode(' ', trim($name))[0];
    }

    // ------------------------------------------------------------- scopes

    /**
     * The gate every discovery query starts from. Applied at the QUERY level,
     * never in application code afterwards. Spec 15.1.
     */
    public function scopeDiscoverable(Builder $q): Builder
    {
        // Nothing reaches a visitor before a moderator has read it. This
        // belongs in the same query as every other gate: a check applied in
        // PHP afterwards is a check one code path will eventually skip.
        return $q->where('profiles.moderation_status', 'APPROVED')->whereHas('user', function (Builder $u) {
            $u->where('status', 'ACTIVE')
              ->whereIn('verification_level', ['PHONE', 'PHONE_EMAIL', 'NID', 'NID_SELFIE'])
              ->where(function (Builder $c) {
                  $c->where('registrant_relationship', 'SELF')
                    ->orWhereNotNull('candidate_confirmed_at');
              });
        });
    }

    /** Exclude anyone either party has blocked, in both directions. */
    public function scopeNotBlockedWith(Builder $q, ?int $viewerProfileId): Builder
    {
        if (! $viewerProfileId) {
            return $q;
        }

        return $q->whereNotExists(function ($sub) use ($viewerProfileId) {
            $sub->selectRaw(1)->from('blocks')
                ->whereColumn('blocks.blocked_profile_id', 'profiles.id')
                ->where('blocks.blocker_profile_id', $viewerProfileId);
        })->whereNotExists(function ($sub) use ($viewerProfileId) {
            $sub->selectRaw(1)->from('blocks')
                ->whereColumn('blocks.blocker_profile_id', 'profiles.id')
                ->where('blocks.blocked_profile_id', $viewerProfileId);
        });
    }

    /** "Hide me from these numbers" — enforced in the query, not after it. */
    public function scopeNotHiddenFrom(Builder $q, ?User $viewer): Builder
    {
        if (! $viewer) {
            return $q;
        }

        return $q->whereNotExists(function ($sub) use ($viewer) {
            $sub->selectRaw(1)->from('hidden_contacts')
                ->join('users as hu', 'hu.id', '=', 'hidden_contacts.user_id')
                ->whereColumn('hu.id', 'profiles.user_id')
                ->where('hidden_contacts.mobile_hash', $viewer->mobile_hash);
        });
    }

    public function scopeSeeking(Builder $q, string $gender): Builder
    {
        return $q->where('gender', $gender === 'BRIDE' ? 'FEMALE' : 'MALE');
    }
}
