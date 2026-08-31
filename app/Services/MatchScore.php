<?php

namespace App\Services;

use App\Models\Preference;
use App\Models\Profile;

/**
 * A 0–100 number answering "how well does this person fit MY stated
 * preferences". Deliberately explainable: every point is attributable to a
 * named criterion, so the rationale on a curated introduction can be
 * generated from the same computation. Spec 15.3.
 *
 * Note what is absent: `complexion` is never scored. The field exists because
 * users expect it; the product must not amplify the preference.
 */
class MatchScore
{
    public const DISQUALIFIED = -1;

    public function between(Profile $viewer, Profile $candidate): int
    {
        $vc = $this->oneWay($viewer, $candidate);

        if ($vc === self::DISQUALIFIED) {
            return self::DISQUALIFIED;
        }

        $cv = $this->oneWay($candidate, $viewer);

        if ($cv === self::DISQUALIFIED) {
            return self::DISQUALIFIED;
        }

        // Harmonic mean, not average. A one-sided 95% is a bad match: the
        // other person declines and both members lose a slot. The harmonic
        // mean punishes imbalance far harder.
        $base = ($vc + $cv) > 0 ? (2 * $vc * $cv) / ($vc + $cv) : 0;

        $base = $this->applyModifiers($base, $viewer, $candidate);

        return (int) max(0, min(100, round($base)));
    }

    /** @return int 0–100, or DISQUALIFIED when a MUST criterion fails. */
    public function oneWay(Profile $viewer, Profile $candidate): int
    {
        $pref = $viewer->preference;

        if (! $pref) {
            return 50;   // no stated preference — neutral, not disqualifying
        }

        $weights = config('setu.match_weights');
        $total = 0.0;
        $possible = 0.0;

        foreach ($weights as $criterion => $weight) {
            $posture = $pref->posture($criterion);

            if ($posture === 'OPEN') {
                continue;   // unspecified criteria must not dilute the score
            }

            $satisfaction = $this->satisfaction($criterion, $pref, $candidate);

            if ($posture === 'MUST' && $satisfaction < 1.0) {
                return self::DISQUALIFIED;
            }

            $possible += $weight;
            $total += $weight * $satisfaction;
        }

        // Relocation intent is always a hard filter, never a weight.
        if (! $this->relocationCompatible($viewer, $candidate)) {
            return self::DISQUALIFIED;
        }

        return $possible > 0 ? (int) round(100 * $total / $possible) : 50;
    }

    private function satisfaction(string $criterion, Preference $pref, Profile $c): float
    {
        return match ($criterion) {
            'age'                => $this->inRange($c->age, $pref->age_min, $pref->age_max),
            'height'             => $this->inRange($c->height_cm, $pref->height_min_cm, $pref->height_max_cm),
            'marital_status'     => $this->inSet($c->marital_status, $pref->marital_status),
            'religion'           => $this->inSet($c->religion, $pref->religion),
            'prayer_habit'       => $this->adjacentSet($c->prayer_habit, $pref->prayer_habit, [
                'FIVE_TIMES', 'REGULARLY', 'OCCASIONALLY', 'NOT_PRACTISING',
            ]),
            'district'           => $this->districtFit($c, $pref),
            'country'            => $this->inSet($c->location?->country, $pref->countries),
            'education'          => $this->adjacentSet($c->career?->education_level, $pref->education_level, [
                'SSC', 'HSC', 'DIPLOMA', 'BACHELOR', 'MASTER', 'MPHIL', 'PHD',
            ]),
            'profession'         => $this->inSet($c->career?->profession, $pref->profession),
            'family_involvement' => $this->inSet($c->family?->family_involvement, $pref->family_involvement),
            'marriage_timeline'  => $this->inSet($c->marriage_timeline, $pref->marriage_timeline),
            'diet'               => $pref->diet === 'ANY' ? 1.0 : ($c->lifestyle?->diet === $pref->diet ? 1.0 : 0.0),
            default              => 0.4,
        };
    }

    private function inRange(?int $value, ?int $min, ?int $max): float
    {
        if ($value === null) {
            return 0.4;      // missing data is not a hard fail
        }

        if ($min !== null && $value < $min) return 0.0;
        if ($max !== null && $value > $max) return 0.0;

        return 1.0;
    }

    private function inSet(?string $value, ?array $set): float
    {
        if (empty($set))    return 1.0;   // "any"
        if ($value === null) return 0.4;

        return in_array($value, $set, true) ? 1.0 : 0.0;
    }

    /** An adjacent band scores partially — one education level below is close. */
    private function adjacentSet(?string $value, ?array $set, array $ladder): float
    {
        if (empty($set))     return 1.0;
        if ($value === null) return 0.4;
        if (in_array($value, $set, true)) return 1.0;

        $vi = array_search($value, $ladder, true);

        foreach ($set as $wanted) {
            $wi = array_search($wanted, $ladder, true);

            if ($vi !== false && $wi !== false && abs($vi - $wi) === 1) {
                return 0.6;
            }
        }

        return 0.0;
    }

    private function districtFit(Profile $c, Preference $pref): float
    {
        $district = $c->location?->district_id;

        if ($district && in_array($district, $pref->exclude_districts ?? [], true)) {
            return 0.0;   // explicit exclusion is absolute
        }

        if (empty($pref->districts)) {
            return 1.0;
        }

        return in_array($district, $pref->districts, true) ? 1.0 : 0.6;
    }

    /**
     * Mismatched mobility intent is the single most avoidable failure in
     * diaspora matchmaking. Treated as a gate, not a weight. Spec 14.4.
     */
    private function relocationCompatible(Profile $a, Profile $b): bool
    {
        $x = $a->location?->relocation_intent ?? 'UNDECIDED';
        $y = $b->location?->relocation_intent ?? 'UNDECIDED';

        // Two people who both refuse to move, in different countries.
        if ($x === 'WILL_NOT' && $y === 'WILL_NOT') {
            $sameCountry = ($a->location?->country) === ($b->location?->country);

            return $sameCountry;
        }

        return true;
    }

    private function applyModifiers(float $base, Profile $viewer, Profile $c): float
    {
        if (in_array($c->user->verification_level, ['NID', 'NID_SELFIE'], true)) $base *= 1.06;
        if ($c->completeness >= 90)                                              $base *= 1.05;
        if ($c->user->last_active_at?->gt(now()->subDays(7)))                    $base *= 1.03;
        if ($c->location?->district_id === $viewer->location?->district_id)      $base *= 1.05;
        if ($c->marriage_timeline === $viewer->marriage_timeline)                $base *= 1.04;
        if ($c->family?->family_involvement === $viewer->family?->family_involvement) $base *= 1.04;
        if ($c->approvedPhotos()->count() === 0)                                 $base *= 0.90;

        // Protects other members from a serial passer.
        if ($c->response_rate > 0 && $c->response_rate < 20)                     $base *= 0.85;

        return $base;
    }

    /**
     * The written reason shown above the photo on a curated introduction.
     * A card without a stated reason is judged as harshly as a dating card;
     * with one, the same profile reads as a considered suggestion. Spec 15.4.
     */
    public function rationale(Profile $a, Profile $b): string
    {
        $bits = [];

        if ($a->location?->district_id === $b->location?->district_id && $a->location?->district) {
            $bits[] = __('match.both_in', ['place' => $a->location->district->name()]);
        }

        if ($a->career?->profession && $a->career->profession === $b->career?->profession) {
            $bits[] = __('match.both_work_in', ['field' => $b->career->profession]);
        }

        if ($a->marriage_timeline === $b->marriage_timeline) {
            $bits[] = __('match.same_timeline');
        }

        if ($a->family?->family_involvement === $b->family?->family_involvement) {
            $bits[] = __('match.same_family_view');
        }

        if ($b->location?->home_district_id === $a->location?->home_district_id) {
            $bits[] = __('match.same_home_district');
        }

        return $bits ? implode('. ', array_slice($bits, 0, 3)).'.' : __('match.generic');
    }
}
