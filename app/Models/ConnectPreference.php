<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectPreference extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['cities' => 'array', 'intentions' => 'array', 'faith_practice' => 'array'];
    }
}
