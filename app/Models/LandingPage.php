<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'filter_json'      => 'array',
            'faq_json'         => 'array',
            'internal_links'   => 'array',
            'count_updated_at' => 'datetime',
        ];
    }

    /**
     * Fewer than 8 matching profiles and the page noindexes itself.
     * This is what keeps a landing-page network from being treated as
     * doorway spam. Spec 8.2.
     */
    public function shouldIndex(): bool
    {
        return match ($this->index_status) {
            'INDEX'   => true,
            'NOINDEX' => false,
            default   => $this->match_count >= config('setu.landing.min_profiles_to_index'),
        };
    }

    public function intro(): ?string
    {
        return app()->getLocale() === 'bn' ? $this->intro_bn : $this->intro_en;
    }
}
