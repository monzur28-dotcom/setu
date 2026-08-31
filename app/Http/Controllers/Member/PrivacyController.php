<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\HiddenContact;
use App\Models\User;
use App\Services\VisibilitySerializer;
use Illuminate\Http\Request;

/**
 * The most important settings screen in the product — where the central
 * promise either becomes real to the member or does not. Spec 10.2.
 */
class PrivacyController extends Controller
{
    public function __construct(private readonly VisibilitySerializer $serializer) {}

    public function edit(Request $request)
    {
        $profile = $request->user()->profile;

        return view('member.privacy', [
            'profile'    => $profile,
            'visibility' => $profile->visibility,
            // The live side-by-side preview: this is what anyone sees,
            // this is what someone you have granted access sees.
            'publicView'  => $this->serializer->forViewer($profile, null),
            'privateView' => $this->serializer->forViewer($profile, $request->user()),
            'hidden'      => $request->user()->hiddenContacts,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'show_photos'           => ['boolean'],
            'show_name'             => ['boolean'],
            'show_gender'           => ['boolean'],
            'show_height'           => ['boolean'],
            'show_city'             => ['boolean'],
            'show_profession'       => ['boolean'],
            'show_hobbies'          => ['boolean'],
            'allow_operator_access' => ['boolean'],
        ]);

        $request->user()->profile->visibility->update($data);

        return back()->with('status', __('privacy.updated'));
    }

    /**
     * Indexing is OFF by default and requires an explicit, informed opt-in.
     * Withdrawal de-indexes within 72 hours via an active removal request,
     * not by waiting for a recrawl. Spec 16.3.
     */
    public function indexing(Request $request)
    {
        $on = $request->boolean('public_indexing');
        $user = $request->user();

        $user->update(['public_indexing' => $on ? 'INDEXED' : 'NOINDEX']);

        $on
            ? Consent::record($user->id, 'PUBLIC_INDEXING', $request)
            : Consent::revoke($user->id, 'PUBLIC_INDEXING');

        AuditLog::write($user, $on ? 'indexing_opt_in' : 'indexing_opt_out', [
            'entity_type' => 'user', 'entity_id' => $user->id,
        ]);

        if (! $on) {
            // TODO: queue a search-engine removal request for this URL.
        }

        return back()->with('status', $on ? __('privacy.indexing_on') : __('privacy.indexing_off'));
    }

    /** "Hide me from these numbers" — relatives, colleagues, an ex. */
    public function hide(Request $request)
    {
        $request->validate(['mobile' => ['required', 'string', 'max:20']]);

        $digits = preg_replace('/\D/', '', $request->input('mobile'));
        $e164 = str_starts_with($digits, '88') ? '+'.$digits : '+88'.ltrim($digits, '0');

        HiddenContact::firstOrCreate([
            'user_id'     => $request->user()->id,
            'mobile_hash' => User::hashMobile($e164),
        ]);

        return back()->with('status', __('privacy.hidden_added'));
    }
}
