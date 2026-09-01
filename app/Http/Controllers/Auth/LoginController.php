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

    /**
     * Email, profile id, or phone number — whichever the member typed.
     *
     * Three things here were wrong and are worth naming, because each fails
     * silently as "no such account" rather than as an error:
     *
     *  - The email lookup was case-sensitive in effect. MySQL's default
     *    collation hid that; PostgreSQL does not, so Admin@… would stop
     *    matching admin@… the moment the database moved. Emails are stored
     *    folded and compared folded.
     *
     *  - The profile id pattern was /^BD\d{7}$/, left behind when the prefix
     *    became configurable. Every id issued since is ST…, so logging in by
     *    profile id had simply stopped working — the id fell through to the
     *    phone branch and was mangled into a number.
     *
     *  - The phone branch assumed +88. A member in Toronto typing their own
     *    number got +88 glued to the front of it.
     */
    private function resolve(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if (str_contains($identifier, '@')) {
            return User::where('email', mb_strtolower($identifier))->first();
        }

        if (preg_match('/^[A-Za-z]{2}\d{7}$/', $identifier)) {
            return User::where('profile_id', strtoupper($identifier))->first();
        }

        return User::where('mobile_hash', User::hashMobile($this->toE164($identifier)))->first();
    }

    /**
     * A typed phone number in E.164. A leading + is taken at face value —
     * the member has told us the country. Without one we fall back to the
     * configured home market, which is a guess, so it is the last resort
     * rather than the rule.
     */
    private function toE164(string $input): string
    {
        $digits = preg_replace('/\D/', '', $input);

        if (str_starts_with(trim($input), '+')) {
            return '+'.$digits;
        }

        $dial = ltrim((string) config('setu.default_country_code', '+1'), '+');

        // Already carries the home dial code, e.g. 8801711111111 in Bangladesh.
        if ($dial !== '' && str_starts_with($digits, $dial)) {
            return '+'.$digits;
        }

        return '+'.$dial.ltrim($digits, '0');
    }
}
