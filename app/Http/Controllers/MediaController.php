<?php

namespace App\Http\Controllers;

use App\Models\ConnectPhoto;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Member media is served ONLY through short-lived signed URLs, from private
 * disks, watermarked on serve. A copied URL is unusable once the signature
 * expires. Spec 27.5 N4.
 */
class MediaController extends Controller
{
    public function photo(Request $request, Photo $photo, string $variant = 'blur')
    {
        abort_unless($request->hasValidSignature(), 403);

        $path = $variant === 'full' ? $photo->path : ($photo->blur_path ?? $photo->path);

        abort_unless(Storage::disk('photos')->exists($path), 404);

        return Storage::disk('photos')->response($path, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Robots-Tag'  => 'noindex',
        ]);
    }

    /** Connect media lives on a SEPARATE disk. Wall rule W2. */
    public function connectPhoto(Request $request, ConnectPhoto $photo, string $variant = 'blur')
    {
        abort_unless($request->hasValidSignature(), 403);

        $path = $variant === 'full' ? $photo->path : ($photo->blur_path ?? $photo->path);

        abort_unless(Storage::disk('connect_photos')->exists($path), 404);

        return Storage::disk('connect_photos')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag'  => 'noindex, nofollow',
        ]);
    }
}
