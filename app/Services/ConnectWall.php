<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ConnectProfile;
use App\Models\User;
use RuntimeException;

/**
 * =============================================================================
 *  THE WALL
 * =============================================================================
 *  The single place in the codebase permitted to look across the boundary
 *  between the matrimonial product and Connect.
 *
 *  Everywhere else, the separation is structural: separate tables, separate
 *  storage disks, separate identifiers, separate controllers under a single
 *  route prefix. There is no join path from `profiles` to `connect_profiles`
 *  except through `users`, and this class is the only thing allowed to walk it.
 *
 *  Wall rules W1–W10, master specification 4.3.
 * =============================================================================
 */
class ConnectWall
{
    /**
     * Does this person have a Connect profile?
     *
     * W8: readable ONLY by the PRIVACY role, and every read is written to
     * the audit log. Not by a guardian, not by an operator, not by a
     * moderator, not by support, not by another member.
     */
    public static function membershipFor(User $subject, User $asker): bool
    {
        if ($asker->role !== 'PRIVACY') {
            throw new RuntimeException(
                'Cross-mode membership is readable only by the PRIVACY role. '
                .'If you are trying to build a feature that needs this, read '
                .'chapter 4.3 of the specification first — the answer is almost '
                .'certainly that the feature should not exist.'
            );
        }

        AuditLog::write($asker, 'cross_mode_lookup', [
            'entity_type' => 'user',
            'entity_id'   => $subject->id,
        ]);

        return (bool) $subject->dating_enabled;
    }

    /**
     * W9: warn — never block — when the same image is used in both products.
     * Reverse image search is how two identities get linked, and the member
     * is the only person who can weigh that trade-off.
     *
     * The comparison RESULT is never stored and never exposed to anyone else.
     */
    public static function photoReuseWarning(User $user, ?string $phash): bool
    {
        if (! $phash) {
            return false;
        }

        return $user->profile
            && $user->profile->photos()->where('phash', $phash)->exists();
    }

    /**
     * A ban is a person-level action and applies across both products.
     * Someone removed from Connect for harassment must not remain on the
     * marriage side. The REASON does not cross — only the sanction. Spec 4.8.
     */
    public static function applyPersonLevelBan(User $user, string $reason, User $actor): void
    {
        $user->update(['status' => 'BANNED']);

        $user->profile?->update(['deleted_at' => now()]);
        ConnectProfile::where('user_id', $user->id)->update(['status' => 'DELETED']);

        AuditLog::write($actor, 'person_level_ban', [
            'entity_type' => 'user',
            'entity_id'   => $user->id,
            'after'       => ['reason' => $reason],
        ]);
    }

    /**
     * Deleting Connect leaves the marriage account completely intact, and
     * purges Connect data within seven days — faster than the account
     * default, because someone leaving wants it gone. Spec 11.5 / 27.1 W10.
     */
    public static function deleteConnectOnly(User $user): void
    {
        $cp = $user->connectProfile;

        if (! $cp) {
            return;
        }

        $cp->update(['status' => 'DELETED']);
        $cp->delete();
        $user->update(['dating_enabled' => false]);

        AuditLog::write($user, 'connect_profile_deleted', [
            'entity_type' => 'connect_profile',
            'entity_id'   => $cp->id,
        ]);
    }
}
