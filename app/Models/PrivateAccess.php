<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PrivateAccess extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /**
     * Granting access grants it in return. One action, both directions —
     * neither party is more exposed than the other. This mutual mechanic is
     * the single best idea taken from the teardowns. Spec 16.1.
     */
    public static function grantMutually(int $grantorProfileId, int $granteeProfileId): void
    {
        DB::transaction(function () use ($grantorProfileId, $granteeProfileId) {
            foreach ([[$grantorProfileId, $granteeProfileId], [$granteeProfileId, $grantorProfileId]] as [$a, $b]) {
                static::updateOrCreate(
                    ['grantor_profile_id' => $a, 'grantee_profile_id' => $b],
                    ['granted_at' => now(), 'revoked_at' => null],
                );
            }
        });
    }

    public static function exists_(int $ownerProfileId, int $viewerProfileId): bool
    {
        return static::where('grantor_profile_id', $ownerProfileId)
            ->where('grantee_profile_id', $viewerProfileId)
            ->whereNull('revoked_at')
            ->exists();
    }
}
