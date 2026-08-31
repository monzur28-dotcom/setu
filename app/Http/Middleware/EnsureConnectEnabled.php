<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ---------------------------------------------------------------------------
 *  THE WALL — route guard.
 * ---------------------------------------------------------------------------
 *  Everything under /connect passes through here. Three things happen:
 *
 *   1. Roles that must never reach Connect are refused outright — an operator
 *      or guardian token gets 404, not 403, because even confirming the route
 *      exists is more than they are entitled to know. (W5, W4)
 *
 *   2. Connect requires its own, higher verification bar: 18+, phone
 *      verified, AND a selfie liveness check. Spec 27.3 S7.
 *
 *   3. Every response is stamped noindex, and Connect is excluded from every
 *      sitemap elsewhere. (W3)
 * ---------------------------------------------------------------------------
 */
class EnsureConnectEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 404);

        // W5 / W4: staff and guardians have no Connect surface at all.
        if (in_array($user->role, ['OPERATOR', 'GUARDIAN'], true)) {
            abort(404);
        }

        // The opt-in screen is reachable before a Connect profile exists;
        // everything else is not.
        $entryRoutes = ['connect.start', 'connect.enable'];

        if (! $user->dating_enabled && ! in_array($request->route()?->getName(), $entryRoutes, true)) {
            return redirect()->route('connect.start');
        }

        if ($user->dating_enabled && ! $user->connectProfile) {
            return redirect()->route('connect.profile.edit');
        }

        $response = $next($request);

        // W3: belt and braces. robots.txt disallows it, the sitemap excludes
        // it, and the header says it too — the cost of getting this wrong is
        // somebody's safety.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }
}
