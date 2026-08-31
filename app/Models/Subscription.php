<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'auto_renew' => 'boolean'];
    }

    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
