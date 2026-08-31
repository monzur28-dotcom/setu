<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\Profile;
use Illuminate\Support\Facades\Cache;

/**
 * Landing pages are DATA, not code. Counts are cached hourly — never run a
 * count query per page view on a page designed to receive organic spikes.
 */
class LandingPageService
{
    public function query(array $filters, ?int $viewerProfileId = null)
    {
        $q = Profile::query()->discoverable()->notBlockedWith($viewerProfileId)
            ->with(['user', 'visibility', 'location.district', 'location.division', 'career', 'approvedPhotos']);

        if (! empty($filters['gender']))   $q->where('gender', $filters['gender']);
        if (! empty($filters['religion'])) $q->where('religion', $filters['religion']);

        if (! empty($filters['district_id'])) {
            $q->whereHas('location', fn ($l) => $l->where('district_id', $filters['district_id']));
        }

        if (! empty($filters['division_id'])) {
            $q->whereHas('location', fn ($l) => $l->where('division_id', $filters['division_id']));
        }

        if (! empty($filters['profession'])) {
            $q->whereHas('career', fn ($c) => $c->where('profession', $filters['profession']));
        }

        if (! empty($filters['marital_status'])) {
            $q->whereIn('marital_status', (array) $filters['marital_status']);
        }

        if (! empty($filters['age_min']) || ! empty($filters['age_max'])) {
            $min = $filters['age_min'] ?? 18;
            $max = $filters['age_max'] ?? 70;
            $q->whereBetween('date_of_birth', [
                now()->subYears($max + 1)->toDateString(),
                now()->subYears($min)->toDateString(),
            ]);
        }

        if (! empty($filters['has_photo'])) {
            $q->whereHas('photos', fn ($p) => $p->where('status', 'APPROVED'));
        }

        return $q;
    }

    public function count(LandingPage $page): int
    {
        return Cache::remember(
            "landing:{$page->id}:count",
            now()->addMinutes(config('setu.landing.count_cache_minutes')),
            fn () => $this->query($page->filter_json)->count(),
        );
    }

    /** Nightly: refresh counts and re-evaluate the noindex threshold. */
    public function refreshAll(): int
    {
        $touched = 0;

        LandingPage::query()->each(function (LandingPage $page) use (&$touched) {
            $count = $this->query($page->filter_json)->count();
            $page->update(['match_count' => $count, 'count_updated_at' => now()]);
            $touched++;
        });

        return $touched;
    }
}
