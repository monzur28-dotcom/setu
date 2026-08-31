<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileLocation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['visiting_bd_from' => 'date', 'visiting_bd_to' => 'date'];
    }

    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }
    public function district(): BelongsTo { return $this->belongsTo(GeoDistrict::class, 'district_id'); }
    public function division(): BelongsTo { return $this->belongsTo(GeoDivision::class, 'division_id'); }
    public function homeDistrict(): BelongsTo { return $this->belongsTo(GeoDistrict::class, 'home_district_id'); }
}
