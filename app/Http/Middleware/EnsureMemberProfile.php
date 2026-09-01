<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The member area needs a member profile.
 *
 * Six controllers reached for `$request->user()->profile` and used it
 * without checking, so any account without one — a staff login, or someone
 * who abandoned registration at step two — got a 500 on whichever member
 * page they opened first. Guarding it here rather than in each controller
 * means the next member route added is covered without anyone remembering.
 *
 * Staff are sent to their own console rather than to the registration form.
 * An administrator who lands on /me/search has taken a wrong turn; inviting
 * them to create a matrimonial profile would be a strange thing to do about
 * it, and would put a staff account into the member population.
 */
class EnsureMemberProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->profile) {
            return $next($request);
        }

        if ($user?->isStaff()) {
            return redirect()->route($this->consoleFor($user->role))
                ->with('status', __('nav.member_area_needs_profile'));
        }

        // Registration was never finished. This is the screen that finishes it.
        return redirect()->route('register.step2')
            ->with('status', __('auth.finish_your_profile'));
    }

    private function consoleFor(string $role): string
    {
        return match ($role) {
            'OPERATOR' => 'operator.cases',
            default    => 'admin.dashboard',
        };
    }
}
