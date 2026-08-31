<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * Language is a PER-ACCOUNT setting, not per household: a Toronto
     * candidate may prefer English while her mother needs Bangla on the
     * same case. Spec 19.5.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->query('lang')
            ?? $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? config('app.locale');

        if (! in_array($locale, ['bn', 'en'], true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
