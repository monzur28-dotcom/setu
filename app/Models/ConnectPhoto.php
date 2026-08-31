<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class ConnectPhoto extends Model
{
    protected $guarded = ['id'];

    public function connectProfile(): BelongsTo { return $this->belongsTo(ConnectProfile::class); }

    /** Served from the SEPARATE connect_photos disk. Wall rule W2. */
    public function url(bool $blurred = true): string
    {
        return URL::temporarySignedRoute('media.connect_photo', now()->addMinutes(15), [
            'photo'   => $this->id,
            'variant' => $blurred ? 'blur' : 'full',
        ]);
    }
}
