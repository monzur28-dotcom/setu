<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchmakerCase extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function client(): BelongsTo { return $this->belongsTo(User::class, 'client_user_id'); }
    public function operator(): BelongsTo { return $this->belongsTo(User::class, 'operator_id'); }
    public function consents(): HasMany { return $this->hasMany(CaseConsent::class, 'case_id'); }
    public function contacts(): HasMany { return $this->hasMany(CaseContact::class, 'case_id'); }
    public function shortlist(): HasMany { return $this->hasMany(CaseShortlist::class, 'case_id'); }
    public function successFee(): HasMany { return $this->hasMany(SuccessFee::class, 'case_id'); }

    public function daysOpen(): int
    {
        return (int) $this->opened_at->diffInDays(now());
    }
}
