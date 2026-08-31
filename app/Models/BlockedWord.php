<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry on the admin's pre-publication word list. Bangla and English
 * live in the same table; `locale` narrows an entry to one language when a
 * word is innocuous in the other.
 */
class BlockedWord extends Model
{
    protected $guarded = ['id'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
