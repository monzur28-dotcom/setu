<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileCareer extends Model
{
    protected $guarded = ['id'];

    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }
}
