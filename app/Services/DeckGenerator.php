<?php

namespace App\Services;

use App\Models\ConnectDeck;
use App\Models\ConnectProfile;
use Illuminate\Support\Facades\DB;

/**
 * Connect discovery. A deck of suggestions, one at a time — there is no
 * search endpoint, no directory, and no way for anyone to look you up.
 * Spec 15.5.
 */
class DeckGenerator
{
    public function __construct(private readonly int $size = 12) {}

    public function buildFor(ConnectProfile $me): int
    {
        $pref = $me->preference;
        $today = today();

        $candidates = ConnectProfile::query()
            ->available()
            ->where('id', '!=', $me->id)
            ->notBlockedWith($me->id)
            // Never re-show someone within 30 days
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('connect_decks')
                ->whereColumn('connect_decks.candidate_connect_id', 'connect_profiles.id')
                ->where('connect_decks.connect_profile_id', $me->id)
                ->where('connect_decks.for_date', '>=', $today->copy()->subDays(30)))
            // Never re-show someone already acted on
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('connect_likes')
                ->whereColumn('connect_likes.to_connect_id', 'connect_profiles.id')
                ->where('connect_likes.from_connect_id', $me->id))
            // "Hide me from these numbers", both directions
            ->whereNotExists(fn ($q) => $q->selectRaw(1)
                ->from('connect_hidden_contacts as h')
                ->join('users as u', 'u.id', '=', 'connect_profiles.user_id')
                ->where('h.connect_profile_id', $me->id)
                ->whereColumn('h.mobile_hash', 'u.mobile_hash'));

        if ($pref) {
            $candidates->whereBetween('age', [$pref->age_min ?? 18, $pref->age_max ?? 70]);

            if (! empty($pref->cities)) {
                $candidates->whereIn('city', $pref->cities);
            }

            if (! empty($pref->intentions)) {
                $candidates->whereIn('intentions', $pref->intentions);
            }
        }

        $rows = $candidates->limit($this->size * 3)->get();

        $scored = $rows->map(fn ($c) => ['profile' => $c, 'score' => $this->score($me, $c)])
            ->sortByDesc('score')
            ->take($this->size);

        DB::transaction(function () use ($scored, $me, $today) {
            foreach ($scored as $row) {
                ConnectDeck::updateOrCreate([
                    'connect_profile_id'   => $me->id,
                    'candidate_connect_id' => $row['profile']->id,
                    'for_date'             => $today,
                ], ['score' => $row['score']]);
            }
        });

        return $scored->count();
    }

    private function score(ConnectProfile $me, ConnectProfile $c): int
    {
        $s = 50;

        // Intentions carry the most weight — it is what keeps this product
        // coherent and stops it drifting into a general dating app.
        if ($c->intentions === $me->intentions) {
            $s += 25;
        }

        if ($c->city === $me->city)                                   $s += 10;
        if ($c->faith_practice && $c->faith_practice === $me->faith_practice) $s += 8;
        if ($c->last_active_at?->gt(now()->subDays(3)))               $s += 5;
        if ($c->photos()->where('status', 'APPROVED')->exists())      $s += 5;

        // Anti-spray. A member who likes almost everyone is deprioritised in
        // other people's decks: indiscriminate liking is the main way a pool
        // becomes unpleasant for women, and throttling it works better than
        // any report queue. Spec 15.5.
        $likeRate = $this->likeRate($c->id);

        if ($likeRate !== null && $likeRate > 0.7) {
            $s -= 25;
        }

        return max(0, min(100, $s));
    }

    private function likeRate(int $connectId): ?float
    {
        $total = DB::table('connect_likes')->where('from_connect_id', $connectId)->count();

        if ($total < 20) {
            return null;   // not enough signal to judge
        }

        $likes = DB::table('connect_likes')
            ->where('from_connect_id', $connectId)->where('action', 'LIKE')->count();

        return $likes / $total;
    }
}
