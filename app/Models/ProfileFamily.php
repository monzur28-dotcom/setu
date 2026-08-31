<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileFamily extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['siblings' => 'array']; }

    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }
}
