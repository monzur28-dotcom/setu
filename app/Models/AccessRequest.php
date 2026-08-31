<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['responded_at' => 'datetime']; }

    public function from(): BelongsTo { return $this->belongsTo(Profile::class, 'from_profile_id'); }
    public function to(): BelongsTo { return $this->belongsTo(Profile::class, 'to_profile_id'); }
}
