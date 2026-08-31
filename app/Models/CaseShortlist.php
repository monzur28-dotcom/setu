<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseShortlist extends Model
{
    protected $table = 'case_shortlists';
    protected $guarded = ['id'];
    public $timestamps = true;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
