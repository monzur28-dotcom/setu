<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailboxMessage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'is_filtered' => 'boolean'];
    }

    public function thread(): BelongsTo { return $this->belongsTo(MailboxThread::class, 'thread_id'); }
    public function sender(): BelongsTo { return $this->belongsTo(Profile::class, 'sender_profile_id'); }
}
