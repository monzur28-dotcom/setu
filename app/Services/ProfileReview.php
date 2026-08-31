<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ModerationItem;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The pre-publication gate. Every path that publishes profile text goes
 * through here — there is no second place that can set `moderation_status`.
 *
 * The two states worth understanding:
 *
 *   PENDING  — never approved. Not discoverable at all, because nothing on
 *              this profile has been read by a human yet.
 *   APPROVED with a pending edit — the approved text keeps serving while the
 *              new text waits in the `pending_*` columns. A member's profile
 *              does not go dark because they rewrote a sentence.
 *
 * Only free text is held. Height, district and marital status are
 * enumerations; there is nothing in them to moderate, and queueing them
 * would bury the queue in noise.
 */
class ProfileReview
{
    /**
     * Held field => the column its unapproved copy waits in, grouped by the
     * record that owns it. 'profile' is the profile row itself; the others
     * name a relation on it.
     */
    public const HELD = [
        'profile'    => ['headline' => 'pending_headline', 'about_me' => 'pending_about_me'],
        'family'     => ['about_family' => 'pending_about_family'],
        'preference' => ['about_partner' => 'pending_about_partner'],
    ];

    public function __construct(private readonly WordFilter $filter) {}

    /**
     * First submission, at the end of registration. Until a moderator
     * approves this the profile is invisible to everyone but its owner.
     */
    public function submitForFirstReview(Profile $profile): void
    {
        DB::transaction(function () use ($profile) {
            $profile->forceFill([
                'moderation_status' => 'PENDING',
                'moderation_reason' => null,
                'submitted_at'      => now(),
            ])->save();

            $this->queue($profile, $this->scan($profile));
        });
    }

    /**
     * An edit to held text. On a profile that is already live the approved
     * copy keeps serving and the new copy waits beside it; on one that has
     * never been approved there is nothing to protect, so the text is
     * written straight through and the whole profile stays at PENDING.
     *
     * @param  string  $target  a key of self::HELD
     * @param  array<string, string|null>  $values  keyed by live column name
     */
    public function submitEdit(Profile $profile, string $target, array $values): void
    {
        $map  = self::HELD[$target] ?? [];
        $held = array_intersect_key($values, $map);

        if ($held === []) {
            return;
        }

        DB::transaction(function () use ($profile, $target, $map, $held) {
            $record = $this->recordFor($profile, $target);

            if (! $record) {
                return;
            }

            if ($profile->moderation_status !== 'APPROVED') {
                $record->forceFill($held)->save();
                $profile->forceFill(['moderation_status' => 'PENDING', 'submitted_at' => now()])->save();
            } else {
                $pending = [];
                foreach ($held as $field => $value) {
                    $pending[$map[$field]] = $value;
                }
                $record->forceFill($pending)->save();
                $profile->forceFill(['submitted_at' => now()])->save();
            }

            $profile->refresh();

            $this->queue($profile, $this->scan($profile));
        });
    }

    /** Publish: every pending copy becomes the live copy. */
    public function approve(Profile $profile, User $by): void
    {
        DB::transaction(function () use ($profile, $by) {
            foreach (self::HELD as $target => $map) {
                $record = $this->recordFor($profile, $target);

                if (! $record) {
                    continue;
                }

                $live = [];
                foreach ($map as $field => $pendingField) {
                    if ($record->{$pendingField} !== null) {
                        $live[$field]        = $record->{$pendingField};
                        $live[$pendingField] = null;
                    }
                }

                if ($live !== []) {
                    $record->forceFill($live)->save();
                }
            }

            $profile->forceFill([
                'moderation_status' => 'APPROVED',
                'moderation_reason' => null,
                'moderated_by'      => $by->id,
                'moderated_at'      => now(),
            ])->save();

            $this->closeQueue($profile);
        });

        AuditLog::write($by, 'profile_approved', [
            'entity_type' => 'PROFILE', 'entity_id' => $profile->id,
        ]);
    }

    /**
     * Refuse the pending text. A profile that was already live STAYS live on
     * its approved copy — a rejected edit is not grounds to take down text a
     * moderator previously cleared.
     */
    public function reject(Profile $profile, User $by, string $reason): void
    {
        DB::transaction(function () use ($profile, $by, $reason) {
            foreach (self::HELD as $target => $map) {
                $record = $this->recordFor($profile, $target);

                if ($record) {
                    $record->forceFill(array_fill_keys(array_values($map), null))->save();
                }
            }

            $profile->forceFill([
                'moderation_status' => $profile->moderation_status === 'APPROVED' ? 'APPROVED' : 'REJECTED',
                'moderation_reason' => $reason,
                'moderated_by'      => $by->id,
                'moderated_at'      => now(),
            ])->save();

            $this->closeQueue($profile);
        });

        AuditLog::write($by, 'profile_rejected', [
            'entity_type' => 'PROFILE', 'entity_id' => $profile->id,
            'after' => ['reason' => $reason],
        ]);
    }

    /**
     * The text a moderator must read: the pending copy wherever one exists,
     * the approved copy otherwise.
     *
     * @return array<string, string>
     */
    public function textUnderReview(Profile $profile): array
    {
        $out = [];

        foreach (self::HELD as $target => $map) {
            $record = $this->recordFor($profile, $target);

            if (! $record) {
                continue;
            }

            foreach ($map as $field => $pendingField) {
                $out[$field] = $record->{$pendingField} ?? $record->{$field};
            }
        }

        return array_filter($out, fn ($v) => is_string($v) && trim($v) !== '');
    }

    /** True while an unapproved copy is waiting anywhere on the profile. */
    public function hasPendingEdit(Profile $profile): bool
    {
        foreach (self::HELD as $target => $map) {
            $record = $this->recordFor($profile, $target);

            foreach ($map as $pendingField) {
                if ($record?->{$pendingField} !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<string> listed words found in the text under review. */
    public function scan(Profile $profile): array
    {
        return $this->filter->match(...array_values($this->textUnderReview($profile)));
    }

    private function recordFor(Profile $profile, string $target)
    {
        return $target === 'profile' ? $profile : $profile->{$target};
    }

    /**
     * One open queue item per profile. A member who edits four times before
     * a moderator looks should produce one row, not four.
     */
    private function queue(Profile $profile, array $matched): void
    {
        ModerationItem::updateOrCreate(
            [
                'entity_type' => 'PROFILE',
                'entity_id'   => $profile->id,
                'status'      => 'QUEUED',
            ],
            [
                'mode'          => 'MATRIMONIAL',
                // A word-list hit goes to the front of the queue.
                'priority'      => $matched === [] ? 5 : config('setu.moderation.word_match_priority', 1),
                'matched_words' => $matched === [] ? null : json_encode($matched, JSON_UNESCAPED_UNICODE),
            ],
        );
    }

    private function closeQueue(Profile $profile): void
    {
        ModerationItem::where('entity_type', 'PROFILE')
            ->where('entity_id', $profile->id)
            ->whereIn('status', ['QUEUED', 'IN_REVIEW'])
            ->update(['status' => 'DONE']);
    }
}
