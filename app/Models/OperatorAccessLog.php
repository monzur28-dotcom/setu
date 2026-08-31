<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatorAccessLog extends Model
{
    protected $table = 'operator_access_logs';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
