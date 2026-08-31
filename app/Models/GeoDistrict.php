<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoDistrict extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function division(): BelongsTo { return $this->belongsTo(GeoDivision::class, 'division_id'); }

    public function name(): string
    {
        return app()->getLocale() === 'bn' ? $this->name_bn : $this->name_en;
    }
}
