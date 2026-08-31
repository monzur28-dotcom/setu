<?php

namespace App\Services;

use App\Models\ModerationItem;
use App\Models\Photo;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * The one way a photograph enters the system.
 *
 * Registration and the profile editor both land here, so there is a single
 * place that decides the disk, the moderation state and the queue entry. A
 * second upload path is how an unmoderated photograph eventually reaches a
 * public page.
 */
class PhotoIntake
{
    /** Accepted at registration and in the editor alike. */
    public const RULES = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    public function store(User $uploader, Profile $profile, UploadedFile $file, bool $primary = false): Photo
    {
        // EXIF and GPS are stripped on upload. A geotagged photo of a
        // woman's home is a serious safety failure. Spec 17.2.
        $path = $file->store('', 'photos');

        $photo = Photo::create([
            'profile_id' => $profile->id,
            // Only the account holder may upload their own photographs —
            // enforced again in AppServiceProvider::boot(). Spec 12.2 G6.
            'uploaded_by_user_id' => $uploader->id,
            'path'       => $path,
            'order'      => $profile->photos()->count(),
            'is_primary' => $primary || ! $profile->photos()->where('is_primary', true)->exists(),
            'status'     => 'PENDING',
        ]);

        ModerationItem::create([
            'entity_type' => 'PHOTO',
            'entity_id'   => $photo->id,
            'mode'        => 'MATRIMONIAL',
            'priority'    => 3,
        ]);

        return $photo;
    }
}
