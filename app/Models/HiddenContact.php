<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HiddenContact extends Model
{
    protected $table = 'hidden_contacts';
    protected $guarded = ['id'];
}
