<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Preference extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'marital_status'     => 'array',
            'religion'           => 'array',
            'sect'               => 'array',
            'prayer_habit'       => 'array',
            'districts'          => 'array',
            'exclude_districts'  => 'array',
            'countries'          => 'array',
            'education_level'    => 'array',
            'profession'         => 'array',
            'family_involvement' => 'array',
            'marriage_timeline'  => 'array',
            'postures'           => 'array',
        ];
    }

    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }

    /** MUST | PREFER | OPEN — only MUST is a hard constraint. */
    public function posture(string $criterion): string
    {
        return $this->postures[$criterion] ?? 'OPEN';
    }

    public function isMust(string $criterion): bool
    {
        return $this->posture($criterion) === 'MUST';
    }
}
