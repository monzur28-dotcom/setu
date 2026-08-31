<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class Photo extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'moderated_at' => 'datetime'];
    }

    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }

    /**
     * Short-lived signed URL. Photos are never served from a public bucket
     * and are watermarked on serve. Spec 17.2.
     */
    public function url(bool $blurred = false): string
    {
        return URL::temporarySignedRoute('media.photo', now()->addMinutes(15), [
            'photo'   => $this->id,
            'variant' => $blurred ? 'blur' : 'full',
        ]);
    }
}
