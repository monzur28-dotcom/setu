<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileVisibility extends Model
{
    protected $table = 'profile_visibility';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'show_photos'           => 'boolean',
            'show_name'             => 'boolean',
            'show_gender'           => 'boolean',
            'show_height'           => 'boolean',
            'show_city'             => 'boolean',
            'show_profession'       => 'boolean',
            'show_hobbies'          => 'boolean',
            'allow_operator_access' => 'boolean',
        ];
    }

    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }
}
