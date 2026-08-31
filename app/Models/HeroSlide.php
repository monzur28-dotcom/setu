<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One photograph in the front-page slideshow.
 *
 * Marketing media, served straight from the web root — deliberately NOT
 * through the signed-URL path that member photographs use. See the migration
 * for why the two must not share a disk.
 */
class HeroSlide extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** The slides for one half of the front page, plus the shared ones. */
    public function scopeFor(Builder $q, string $product): Builder
    {
        return $q->where('is_active', true)
            ->whereIn('product', ['BOTH', $product])
            ->orderBy('sort_order')->orderBy('id');
    }

    public function url(): string
    {
        return Storage::disk('hero')->url($this->path);
    }
}
