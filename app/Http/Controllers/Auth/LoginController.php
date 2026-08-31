<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function show() { return view('auth.login'); }

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password'   => ['required', 'string'],
        ]);

        $key = 'login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['identifier' => __('auth.throttled', [
                'seconds' => RateLimiter::availableIn($key),
            ])]);
        }

        $user = $this->resolve($data['identifier']);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 900);

            return back()->withErrors(['identifier' => __('auth.failed')]);
        }

        RateLimiter::clear($key);
        Auth::login($user, true);
        $user->update(['last_active_at' => now(), 'last_login_ip' => $request->ip()]);

        return redirect()->intended(route('member.dashboard'));
    }

    /** Many members will not remember a password. Code login is equal-weight. */
    public function requestCode(Request $request)
    {
        $request->validate(['identifier' => ['required', 'string']]);

        $user = $this->resolve($request->input('identifier'));

        if ($user) {
            $this->otp->issue($user->mobile, 'LOGIN');
            session(['login_otp_user' => $user->id]);
        }

        // Do not reveal whether the identifier exists.
        return redirect()->route('login.code')->with('status', __('auth.code_sent'));
    }

    public function showCode() { return view('auth.login-code'); }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = User::find(session('login_otp_user'));

        if (! $user || ! $this->otp->verify($user->mobile, $request->input('code'), 'LOGIN')) {
            return back()->withErrors(['code' => __('auth.otp_invalid')]);
        }

        Auth::login($user, true);
        session()->forget('login_otp_user');

        return redirect()->route('member.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function resolve(string $identifier): ?User
    {
        if (str_contains($identifier, '@')) {
            return User::where('email', $identifier)->first();
        }

        if (preg_match('/^BD\d{7}$/i', $identifier)) {
            return User::where('profile_id', strtoupper($identifier))->first();
        }

        $digits = preg_replace('/\D/', '', $identifier);
        $e164 = str_starts_with($digits, '88') ? '+'.$digits : '+88'.ltrim($digits, '0');

        return User::where('mobile_hash', User::hashMobile($e164))->first();
    }
}
