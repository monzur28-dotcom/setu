<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseContact extends Model
{
    protected $table = 'case_contacts';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
