<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoDivision extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function districts(): HasMany { return $this->hasMany(GeoDistrict::class, 'division_id'); }

    public function name(): string
    {
        return app()->getLocale() === 'bn' ? $this->name_bn : $this->name_en;
    }
}
