<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileLifestyle extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['hobbies' => 'array']; }

    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }
}
