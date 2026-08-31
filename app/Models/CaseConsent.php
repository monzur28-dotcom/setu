<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseConsent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function isLive(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * No row, no access. This single query is what makes the service tier
     * honest — and it is the whole difference from the reference model,
     * whose published privacy page permits disclosure "at will". Spec 16.5.
     */
    public static function live(int $caseId, int $subjectUserId, string $scope = 'VIEW_PRIVATE'): ?self
    {
        return static::where('case_id', $caseId)
            ->where('subject_user_id', $subjectUserId)
            ->where('scope', $scope)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }
}
