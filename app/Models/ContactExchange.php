<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactExchange extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array { return ['accepted_at' => 'datetime']; }
}
