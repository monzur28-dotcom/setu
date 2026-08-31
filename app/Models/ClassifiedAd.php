<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassifiedAd extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['no_media_flag' => 'boolean', 'expires_at' => 'datetime'];
    }

    public function district(): BelongsTo { return $this->belongsTo(GeoDistrict::class, 'district_id'); }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('status', 'LIVE')->where('expires_at', '>', now());
    }

    /**
     * নো-মিডিয়া. Ads whose poster asked for no intermediaries are excluded
     * from every operator-facing query AT THE DATA LAYER — a rule enforced by
     * a query cannot be forgotten under commission pressure. Spec 5.6 / 16.5 M6.
     */
    public function scopeVisibleToOperators(Builder $q): Builder
    {
        return $q->live()->where('no_media_flag', false);
    }
}
