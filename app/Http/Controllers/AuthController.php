<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\Support\AuditLog;
use App\Support\CleanText;
use App\Support\DeviceIdentity;
use App\Support\FormSecurity;
use App\Support\SecurityLog;
use App\Support\TransactionalMailer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

class AuthController extends Controller
{
    private const RESET_TOKEN_MINUTES = 15;

    private const TWO_FACTOR_MINUTES = 10;

    public function showUserLogin(): Response
    {
        return $this->page('Auth/Login', [
            'captcha' => FormSecurity::forPage('user_login'),
            'providers' => $this->socialProviders(),
        ], ['title' => 'Sign in | BookMyMovie', 'description' => 'Sign in to hold seats, see your e-tickets and manage bookings.']);
    }

    public function userLogin(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'user_login');

        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:150'],
            'password' => ['required', 'string', 'max:72'],
        ]);
        $credentials['email'] = strtolower(trim($credentials['email']));

        // Check the password before revealing anything about the account, so the
        // response cannot be used to discover which emails are registered.
        if (! Auth::validate($credentials)) {
            SecurityLog::record(SecurityLog::FAILED_LOGIN, $request, ['email_hash' => hash('sha256', $credentials['email'])]);

            return back()->withErrors(['email' => 'That email and password combination did not match an active account.'])->onlyInput('email');
        }

        $user = User::query()->where('email', $credentials['email'])->firstOrFail();

        if ($user->is_blocked) {
            return back()->withErrors(['email' => 'This account has been suspended. Please contact support.'])->onlyInput('email');
        }

        if (! $user->email_verified_at) {
            $request->session()->put('verify_email', $user->email);

            return redirect()
                ->route('user.verify.notice')
                ->withErrors(['code' => 'Please verify your email address to finish setting up your account.']);
        }

        if ($user->two_factor_enabled) {
            // Password was right; hold the sign-in until the emailed code is entered.
            $request->session()->regenerate();
            $this->sendTwoFactorCode($request, $user, $request->boolean('remember'));

            return redirect()->route('user.two-factor')->with('status', 'We emailed you a 6-digit sign-in code.');
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'), 'password');
    }

    public function showTwoFactor(Request $request): Response|RedirectResponse
    {
        $pending = $request->session()->get('two_factor');

        if (! $pending || now()->timestamp > $pending['expires']) {
            return redirect()->route('user.login')->withErrors(['email' => 'Your sign-in code expired. Please sign in again.']);
        }

        return $this->page('Auth/TwoFactor', [
            'email' => Str::mask((string) User::query()->whereKey($pending['user_id'])->value('email'), '•', 2, -6),
            'minutes' => self::TWO_FACTOR_MINUTES,
        ], ['title' => 'Sign-in code | BookMyMovie', 'description' => 'Enter the code we emailed to finish signing in.']);
    }

    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);
        $pending = $request->session()->get('two_factor');

        if (! $pending || now()->timestamp > $pending['expires']) {
            $request->session()->forget('two_factor');

            return redirect()->route('user.login')->withErrors(['email' => 'Your sign-in code expired. Please sign in again.']);
        }

        if (++$pending['attempts'] > 5) {
            $request->session()->forget('two_factor');
            SecurityLog::record(SecurityLog::FAILED_LOGIN, $request, ['layer' => 'two_factor_lockout']);

            return redirect()->route('user.login')->withErrors(['email' => 'Too many wrong codes. Please sign in again.']);
        }

        $request->session()->put('two_factor', $pending);

        if (! Hash::check($data['code'], $pending['code'])) {
            SecurityLog::record(SecurityLog::FAILED_LOGIN, $request, ['layer' => 'two_factor']);

            return back()->withErrors(['code' => 'That code is not right. Check the latest email and try again.']);
        }

        $user = User::query()->whereKey($pending['user_id'])->where('is_blocked', false)->first();
        $request->session()->forget('two_factor');

        if (! $user) {
            return redirect()->route('user.login')->withErrors(['email' => 'This account is not available. Please contact support.']);
        }

        return $this->completeLogin($request, $user, (bool) $pending['remember'], 'password + email code');
    }

    public function resendTwoFactor(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('two_factor');
        $user = $pending ? User::query()->find($pending['user_id']) : null;

        if (! $user) {
            return redirect()->route('user.login');
        }

        $this->sendTwoFactorCode($request, $user, (bool) $pending['remember']);

        return back()->with('status', 'A new code is on its way. Earlier codes no longer work.');
    }

    private function sendTwoFactorCode(Request $request, User $user, bool $remember): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $request->session()->put('two_factor', [
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'expires' => now()->addMinutes(self::TWO_FACTOR_MINUTES)->timestamp,
            'remember' => $remember,
            'attempts' => 0,
        ]);

        TransactionalMailer::twoFactorCode($user, $code, self::TWO_FACTOR_MINUTES);
    }

    private function completeLogin(Request $request, User $user, bool $remember, string $method): RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        // Kept so an admin can see (and, if needed, ban) the address an account signs in from.
        $user->forceFill(['last_login_ip' => $request->ip(), 'last_login_at' => now()])->saveQuietly();
        DeviceIdentity::remember($request, $user, true);
        AuditLog::record('auth.login', $user, ['method' => $method], $request, 'user', $user->id);
        TransactionalMailer::userLogin($user, $request, ucfirst($method));

        return redirect()->intended(route('user.dashboard'));
    }

    public function showUserRegister(): Response
    {
        return $this->page('Auth/Register', [
            'captcha' => FormSecurity::forPage('user_register'),
            'providers' => $this->socialProviders(),
        ], ['title' => 'Create an account | BookMyMovie', 'description' => 'Create a free BookMyMovie account to book cinema seats in under a minute.']);
    }

    public function userRegister(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'user_register');

        $data = $request->validate([
            'name' => CleanText::nameRules(),
            'username' => CleanText::usernameRules(),
            'email' => ['required', 'email:rfc', 'max:150', 'unique:users,email', FormSecurity::disposableEmailRule()],
            'phone' => ['nullable', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\-\s()]+$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ], [
            ...CleanText::nameMessages(),
            ...CleanText::usernameMessages(),
            'terms.accepted' => 'Please accept the terms of service to create an account.',
        ]);

        $code = strtoupper(Str::random(8));

        try {
            $user = User::create([
                'name' => CleanText::normaliseName($data['name']),
                'username' => $data['username'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'email_verification_code' => Hash::make($code),
                'email_verification_expires_at' => now()->addMinutes(15),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Two sign-ups for the same email raced past validation.
            return back()->withErrors(['email' => 'An account with this email already exists.'])->onlyInput('name', 'email', 'phone');
        }

        TransactionalMailer::emailVerificationCode($user, $code);
        AuditLog::record('auth.register', $user, [], $request, 'user', $user->id);
        $request->session()->put('verify_email', $user->email);

        return redirect()
            ->route('user.verify.notice')
            ->with('status', 'Account created. We have emailed you an 8-character verification code.');
    }

    /** Live check for the sign-up form. Says only "free or not", never who owns a name. */
    public function usernameAvailable(Request $request): \Illuminate\Http\JsonResponse
    {
        $name = strtolower(trim(mb_substr((string) $request->query('u', ''), 0, 40)));
        $problem = CleanText::usernameProblem($name);
        $valid = $problem === null;
        $available = $valid && ! User::withTrashed()->where('username', $name)->exists();

        return response()->json([
            'valid' => $valid,
            'reason' => $problem,
            'available' => $available,
            'suggestion' => $valid && ! $available ? User::uniqueUsername($name) : null,
        ]);
    }

    public function showEmailVerification(Request $request): Response
    {
        return $this->page('Auth/VerifyEmail', [
            'email' => strtolower((string) ($request->old('email') ?: $request->session()->get('verify_email', ''))),
        ], ['title' => 'Verify your email | BookMyMovie', 'description' => 'Enter the 8-character code we emailed to finish creating your account.']);
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:150'],
            'code' => ['required', 'string', 'size:8'],
        ]);

        $user = User::query()->where('email', strtolower($data['email']))->first();
        $invalid = fn (string $message) => back()->withInput(['email' => $data['email']])->withErrors(['code' => $message]);

        if (! $user) {
            return $invalid('That code is not valid for this email address.');
        }

        if ($user->email_verified_at) {
            return redirect()->route('user.login')->with('status', 'Your email is already verified. Please sign in.');
        }

        if (! $user->email_verification_code || ! $user->email_verification_expires_at || now()->greaterThan($user->email_verification_expires_at)) {
            return $invalid('This code has expired. Request a new one below.');
        }

        if (! Hash::check(strtoupper($data['code']), $user->email_verification_code)) {
            SecurityLog::record(SecurityLog::CAPTCHA_FAILED, $request, ['layer' => 'email_code']);

            return $invalid('That code is not valid for this email address.');
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ])->save();

        TransactionalMailer::userSignup($user);
        AuditLog::record('auth.email_verified', $user, [], $request, 'user', $user->id);
        $request->session()->forget('verify_email');

        return redirect()->route('user.login')->with('status', 'Email verified. Your account is ready, please sign in.');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:150']]);
        $user = User::query()->where('email', strtolower($data['email']))->whereNull('email_verified_at')->first();

        if ($user) {
            $code = strtoupper(Str::random(8));
            $user->forceFill([
                'email_verification_code' => Hash::make($code),
                'email_verification_expires_at' => now()->addMinutes(15),
            ])->save();

            TransactionalMailer::emailVerificationCode($user, $code);
        }

        return back()
            ->withInput(['email' => $data['email']])
            ->with('status', 'If that address is waiting for verification, a new code is on its way.');
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->user()) {
            AuditLog::record('auth.logout', $request->user(), [], $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been signed out.');
    }

    public function redirectToProvider(string $provider): RedirectResponse
    {
        if ($response = $this->oauthSetupError($provider)) {
            return $response;
        }

        $socialite = 'Laravel\\Socialite\\Facades\\Socialite';

        return $socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(Request $request, string $provider): RedirectResponse
    {
        if ($response = $this->oauthSetupError($provider)) {
            return $response;
        }

        try {
            $socialite = 'Laravel\\Socialite\\Facades\\Socialite';
            $socialUser = $socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()->route('user.login')->withErrors(['oauth' => ucfirst($provider).' sign-in could not be completed. Please use email instead.']);
        }

        if (! $socialUser->getEmail()) {
            return redirect()->route('user.login')->withErrors(['oauth' => ucfirst($provider).' did not share an email address. Please register with email.']);
        }

        $user = User::query()->where('email', strtolower($socialUser->getEmail()))->first();

        if (! $user) {
            $user = User::forceCreate([
                'name' => Str::limit($socialUser->getName() ?: $socialUser->getNickname() ?: 'BookMyMovie member', 100, ''),
                'email' => strtolower($socialUser->getEmail()),
                'password' => Hash::make(Str::random(64)),
                // The provider has verified ownership of this address.
                'email_verified_at' => now(),
            ]);
        }

        if ($user->is_blocked) {
            return redirect()->route('user.login')->withErrors(['email' => 'This account has been suspended. Please contact support.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        DeviceIdentity::remember($request, $user, true);
        AuditLog::record('auth.login', $user, ['method' => $provider], $request, 'user', $user->id);
        TransactionalMailer::userLogin($user, $request, ucfirst($provider));

        return redirect()->intended(route('user.dashboard'));
    }

    private function oauthSetupError(string $provider): ?RedirectResponse
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            abort(404);
        }

        if (! class_exists('Laravel\\Socialite\\Facades\\Socialite')) {
            return redirect()->route('user.login')->withErrors(['oauth' => 'Social sign-in is not installed on this server.']);
        }

        $config = config("services.{$provider}");

        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect'])) {
            return redirect()->route('user.login')->withErrors(['oauth' => ucfirst($provider).' sign-in has not been configured yet. Please use email.']);
        }

        return null;
    }

    public function showForgotPassword(): Response
    {
        return $this->page('Auth/ForgotPassword', [
            'captcha' => FormSecurity::forPage('forgot_password'),
        ], ['title' => 'Reset your password | BookMyMovie', 'description' => 'Get a one-time code to reset your BookMyMovie password.']);
    }

    /**
     * Emails an 8-character, single-use code. Always answers the same way,
     * whether or not the account exists.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'forgot_password');

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:150'],
        ]);
        $email = strtolower(trim($data['email']));

        if (User::query()->where('email', $email)->where('is_blocked', false)->exists()) {
            $code = self::oneTimeCode();

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );
            Cache::forget('reset-attempts:'.$email);

            TransactionalMailer::passwordResetCode($email, $code, route('password.reset'));
            SecurityLog::record(SecurityLog::PASSWORD_RESET, $request, ['stage' => 'requested']);
        }

        $request->session()->put('reset_email', $email);

        return redirect()->route('password.reset')->with('status', 'If an account exists for '.$email.', a reset code is on its way. It expires in '.self::RESET_TOKEN_MINUTES.' minutes.');
    }

    public function showResetPassword(Request $request): Response
    {
        return $this->page('Auth/ResetPassword', [
            'email' => strtolower((string) $request->session()->get('reset_email', '')),
            'captcha' => FormSecurity::forPage('reset_password'),
        ], ['title' => 'Choose a new password | BookMyMovie', 'description' => 'Enter your reset code and set a new password.']);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'reset_password');

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:150'],
            'code' => ['required', 'string', 'size:8'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(8)->mixedCase()->numbers()->uncompromised()],
        ]);
        $email = strtolower(trim($data['email']));
        $code = strtoupper($data['code']);
        $invalid = back()->withErrors(['code' => 'That code is invalid or has expired. Request a new one.'])->onlyInput('email');

        // Five wrong guesses burn the code, on top of the route's rate limit.
        if (Cache::get('reset-attempts:'.$email, 0) >= 5) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return $invalid;
        }

        $user = DB::transaction(function () use ($email, $code, $data) {
            // Lock the row so a code can only ever be redeemed once.
            $record = DB::table('password_reset_tokens')->where('email', $email)->lockForUpdate()->first();

            if (! $record
                || now()->subMinutes(self::RESET_TOKEN_MINUTES)->greaterThan($record->created_at)
                || ! Hash::check($code, $record->token)) {
                return null;
            }

            DB::table('password_reset_tokens')->where('email', $email)->delete();

            $user = User::query()->where('email', $email)->lockForUpdate()->first();
            $user?->forceFill([
                'password' => Hash::make($data['password']),
                'remember_token' => Str::random(60),
            ])->save();

            return $user;
        });

        if (! $user) {
            Cache::add('reset-attempts:'.$email, 0, now()->addMinutes(self::RESET_TOKEN_MINUTES));
            Cache::increment('reset-attempts:'.$email);
            SecurityLog::record(SecurityLog::PASSWORD_RESET, $request, ['stage' => 'bad_code']);

            return $invalid;
        }

        Cache::forget('reset-attempts:'.$email);
        $request->session()->forget('reset_email');

        // Sign the account out everywhere else: a reset usually means a leak.
        DB::table('sessions')->where('user_id', $user->id)->delete();
        SecurityLog::record(SecurityLog::PASSWORD_RESET, $request, ['stage' => 'completed', 'user_id' => $user->id]);
        AuditLog::record('auth.password_reset', $user, [], $request, 'user', $user->id);
        TransactionalMailer::passwordChanged($user, $request);

        return redirect()->route('user.login')->with('status', 'Password updated and other devices signed out. Please sign in.');
    }

    /** Eight characters from an alphabet without look-alikes (no O/0, I/1). */
    private static function oneTimeCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($index = 0; $index < 8; $index++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    public function showAdminLogin(): Response
    {
        return $this->page('Auth/AdminLogin', [
            'captcha' => FormSecurity::forPage('admin_login'),
            'sessionMinutes' => (int) config('session.admin_lifetime', 60),
            'recoveryEnabled' => $this->adminRecoveryEnabled(),
            // Credentials are only ever shown while the account is a read-only
            // demo; with full access they would hand the site to anyone.
            'demo' => config('bookmymovie.admin.demo_enabled') && config('bookmymovie.admin.demo_read_only') ? [
                'email' => config('bookmymovie.admin.demo_email'),
                'password' => config('bookmymovie.admin.demo_password'),
                'readOnly' => (bool) config('bookmymovie.admin.demo_read_only'),
            ] : null,
        ], ['title' => 'Admin sign in | BookMyMovie', 'description' => 'Restricted area for BookMyMovie staff.', 'robots' => 'noindex, nofollow']);
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'admin_login');

        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:150'],
            'password' => ['required', 'string', 'max:72'],
        ]);
        $email = strtolower(trim($credentials['email']));

        $admin = Admin::query()->where('email', $email)->where('is_active', true)->first();

        // Hash a dummy value when the admin is unknown so response timing does
        // not reveal which admin emails exist.
        $valid = Hash::check($credentials['password'], $admin?->password ?? Hash::make(Str::random(40)));

        if (! $admin || ! $valid) {
            SecurityLog::record(SecurityLog::FAILED_LOGIN, $request, ['guard' => 'admin', 'email_hash' => hash('sha256', $email)]);

            return back()->withErrors(['email' => 'Invalid admin credentials.'])->onlyInput('email');
        }

        $admin->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();
        AuditLog::record('admin.login', $admin, [], $request, 'admin', $admin->id);
        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_authenticated_at', now()->timestamp);
        SecurityLog::record(SecurityLog::ADMIN_LOGIN, $request, ['admin_id' => $admin->id]);

        // A scanned ticket QR sent the admin here first; go back to it.
        $intended = (string) $request->session()->pull('admin_intended', '');
        if ($intended !== '' && Str::startsWith($intended, url('/admin/'))) {
            return redirect()->to($intended);
        }

        return redirect()->route('admin.dashboard');
    }

    public function showAdminForgotCredentials(): Response
    {
        abort_unless($this->adminRecoveryEnabled(), 404);

        return $this->page('Auth/AdminForgot', [], ['title' => 'Recover admin access | BookMyMovie', 'description' => 'Staff account recovery.', 'robots' => 'noindex, nofollow']);
    }

    public function sendAdminCredentialReset(Request $request): RedirectResponse
    {
        abort_unless($this->adminRecoveryEnabled(), 404);

        $data = $request->validate([
            'secret_key' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:150'],
        ]);

        if (! hash_equals((string) config('bookmymovie.admin.recovery_secret'), (string) $data['secret_key'])) {
            SecurityLog::record(SecurityLog::FAILED_LOGIN, $request, ['guard' => 'admin_recovery']);

            return back()->withErrors(['secret_key' => 'Invalid recovery secret.'])->onlyInput('email');
        }

        $email = strtolower($data['email']);

        if (Admin::query()->where('email', $email)->where('is_demo', false)->exists()) {
            $token = Str::random(64);

            DB::table('admin_password_resets')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );

            TransactionalMailer::passwordResetCode($email, $token, route('admin.credentials.reset', $token), true);
        }

        return back()->with('status', 'If that admin exists, a recovery link has been emailed. It expires in '.self::RESET_TOKEN_MINUTES.' minutes.');
    }

    public function showAdminResetCredentials(string $token): Response
    {
        abort_unless($this->adminRecoveryEnabled(), 404);

        return $this->page('Auth/AdminReset', ['token' => $token], ['title' => 'Update admin credentials | BookMyMovie', 'description' => 'Staff account recovery.', 'robots' => 'noindex, nofollow']);
    }

    public function resetAdminCredentials(Request $request, string $token): RedirectResponse
    {
        abort_unless($this->adminRecoveryEnabled(), 404);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'change_target' => ['required', 'in:password,email'],
            'new_email' => ['nullable', 'required_if:change_target,email', 'email', 'max:150', 'unique:admins,email'],
            'password' => ['nullable', 'required_if:change_target,password', 'string', 'max:72', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        $email = strtolower($data['email']);

        $updated = DB::transaction(function () use ($email, $token, $data) {
            $record = DB::table('admin_password_resets')->where('email', $email)->lockForUpdate()->first();

            if (! $record || now()->subMinutes(self::RESET_TOKEN_MINUTES)->greaterThan($record->created_at) || ! Hash::check($token, $record->token)) {
                return false;
            }

            DB::table('admin_password_resets')->where('email', $email)->delete();
            $admin = Admin::query()->where('email', $email)->lockForUpdate()->firstOrFail();

            if ($data['change_target'] === 'email') {
                $admin->email = strtolower($data['new_email']);
            } else {
                $admin->password = Hash::make($data['password']);
            }

            $admin->save();

            return true;
        });

        if (! $updated) {
            return back()->withErrors(['email' => 'Invalid or expired admin recovery link.']);
        }

        return redirect()->route('admin.login')->with('status', 'Admin credentials updated. Please sign in.');
    }

    public function showAdminRegister(): Response
    {
        abort_unless($this->adminInviteEnabled(), 404);

        return $this->page('Auth/AdminRegister', [], ['title' => 'Admin invitation | BookMyMovie', 'description' => 'Create a staff account with an invitation code.', 'robots' => 'noindex, nofollow']);
    }

    public function adminRegister(Request $request): RedirectResponse
    {
        abort_unless($this->adminInviteEnabled(), 404);

        $data = $request->validate([
            'invite_code' => ['required', 'string', 'max:200'],
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150', 'unique:admins,email'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if (! hash_equals((string) config('bookmymovie.admin.invite_code'), (string) $data['invite_code'])) {
            SecurityLog::record(SecurityLog::FAILED_LOGIN, $request, ['guard' => 'admin_invite']);

            return back()->withErrors(['invite_code' => 'Invalid invite code.'])->onlyInput('name', 'email');
        }

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_authenticated_at', now()->timestamp);

        return redirect()->route('admin.dashboard')->with('status', 'Admin account created.');
    }

    public function adminLogout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_id', 'admin_authenticated_at']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Signed out of the admin panel.');
    }

    /**
     * Social sign-in buttons are only shown for providers with credentials.
     *
     * @return list<array{key: string, label: string, url: string}>
     */
    private function socialProviders(): array
    {
        return collect(['google' => 'Google', 'facebook' => 'Facebook'])
            ->filter(fn ($label, $provider) => filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret")))
            ->map(fn ($label, $provider) => ['key' => $provider, 'label' => $label, 'url' => route('oauth.redirect', $provider)])
            ->values()
            ->all();
    }

    private function adminInviteEnabled(): bool
    {
        return filled(config('bookmymovie.admin.invite_code'));
    }

    private function adminRecoveryEnabled(): bool
    {
        return filled(config('bookmymovie.admin.recovery_secret'));
    }
}
