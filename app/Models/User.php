<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'password', 'remember_token', 'mobile_enc', 'mobile_hash',
        // Never serialised. Only the PRIVACY role may read this, through
        // ConnectWall::membershipFor(), which writes an audit row. Wall W8.
        'dating_enabled',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'      => 'datetime',
            'mobile_verified_at'     => 'datetime',
            'candidate_confirmed_at' => 'datetime',
            'last_active_at'         => 'datetime',
            'password'               => 'hashed',
            'dating_enabled'         => 'boolean',
        ];
    }

    // ---------------------------------------------------------------- mobile

    public static function hashMobile(string $e164): string
    {
        return hash_hmac('sha256', preg_replace('/\D/', '', $e164), config('app.key'));
    }

    public function setMobile(string $e164): void
    {
        $this->mobile_enc  = Crypt::encryptString($e164);
        $this->mobile_hash = static::hashMobile($e164);
    }

    public function getMobileAttribute(): ?string
    {
        return $this->mobile_enc ? Crypt::decryptString($this->mobile_enc) : null;
    }

    /**
     * The public identifier. The prefix is configurable and deliberately not
     * a country code — a member in Toronto whose id starts with BD is being
     * told, every time they see it, that this product is not really theirs.
     */
    public static function generateProfileId(): string
    {
        $prefix = strtoupper(config('setu.profile_id_prefix', 'ST'));

        do {
            $id = $prefix.random_int(1000000, 9999999);
        } while (static::where('profile_id', $id)->exists());

        return $id;
    }

    // --------------------------------------------------------- relationships

    public function profile(): HasOne { return $this->hasOne(Profile::class); }
    public function connectProfile(): HasOne { return $this->hasOne(ConnectProfile::class); }
    public function consents(): HasMany { return $this->hasMany(Consent::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function entitlements(): HasMany { return $this->hasMany(Entitlement::class); }
    public function verifications(): HasMany { return $this->hasMany(Verification::class); }
    public function hiddenContacts(): HasMany { return $this->hasMany(HiddenContact::class); }

    /** Links where THIS user is the candidate being watched. */
    public function guardianLinks(): HasMany
    {
        return $this->hasMany(GuardianLink::class, 'candidate_user_id');
    }

    /** Links where THIS user is the guardian doing the watching. */
    public function wardLinks(): HasMany
    {
        return $this->hasMany(GuardianLink::class, 'guardian_user_id');
    }

    public function matchmakerCase()
    {
        return $this->hasOne(MatchmakerCase::class, 'client_user_id')->whereNull('closed_at');
    }

    // -------------------------------------------------------------- helpers

    public function isCandidateConfirmed(): bool
    {
        return $this->registrant_relationship === 'SELF' || $this->candidate_confirmed_at !== null;
    }

    /**
     * Profiles that are not candidate-confirmed are not public, not indexed,
     * and not visible to any operator. The soft consent gate — spec 9.5.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->status === 'ACTIVE'
            && $this->isCandidateConfirmed()
            && in_array($this->verification_level, ['PHONE', 'PHONE_EMAIL', 'NID', 'NID_SELFIE'], true);
    }

    public function activeSubscription(string $product = 'MATRIMONIAL'): ?Subscription
    {
        return $this->subscriptions()
            ->where('product', $product)
            ->where('status', 'ACTIVE')
            ->where('ends_at', '>', now())
            ->latest('ends_at')
            ->first();
    }

    public function planCode(string $product = 'MATRIMONIAL'): string
    {
        return $this->activeSubscription($product)?->plan?->code ?? 'free';
    }

    public function plan(string $product = 'MATRIMONIAL'): array
    {
        $key = $product === 'CONNECT' ? 'setu.connect_plans.' : 'setu.plans.';

        return config($key.$this->planCode($product), config($key.'free'));
    }

    public function can_(string $ability): bool
    {
        return (bool) ($this->plan()[$ability] ?? false);
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['OPERATOR', 'MODERATOR', 'PRIVACY', 'ADMIN'], true);
    }

    public function newInviteToken(): string
    {
        return Str::random(48);
    }
}
