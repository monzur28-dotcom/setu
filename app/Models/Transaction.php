<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'raw_payload'    => 'array',
            'verified_at'    => 'datetime',
            'reconciled_at'  => 'datetime',
        ];
    }
}
