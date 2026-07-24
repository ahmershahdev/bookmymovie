<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\Support\FormSecurity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showUserLogin(): View
    {
        return view('auth.user-login');
    }

    public function userLogin(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'user_login');

        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'min:6', 'max:150', FormSecurity::disposableEmailRule()],
            'password' => ['required', 'string', 'min:8', 'max:72'],
        ]);

        if (Auth::attempt([...$credentials, 'is_blocked' => false], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('user.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials or blocked account.'])->onlyInput('email');
    }

    public function showUserRegister(): View
    {
        return view('auth.user-register');
    }

    public function userRegister(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'user_register');

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'min:6', 'max:150', 'unique:users,email', FormSecurity::disposableEmailRule()],
            'phone' => ['nullable', 'string', 'min:10', 'max:20', 'regex:/^[0-9+\-\s()]+$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        unset($data['password_confirmation']);

        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('user.dashboard')->with('status', 'Account created.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
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
        } catch (\Throwable $exception) {
            return redirect()
                ->route('user.login')
                ->withErrors([
                    'oauth' => ucfirst($provider) . ' login could not be completed. Please try email login.',
                ]);
        }

        if (! $socialUser->getEmail()) {
            return redirect()
                ->route('user.login')
                ->withErrors([
                    'oauth' => ucfirst($provider) . ' did not share an email address. Please use email registration.',
                ]);
        }

        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'BookMyMovie Member',
                'password' => Str::random(40),
            ]
        );

        if ($user->is_blocked) {
            return redirect()
                ->route('user.login')
                ->withErrors(['email' => 'Invalid credentials or blocked account.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('user.dashboard'));
    }

    private function oauthSetupError(string $provider): ?RedirectResponse
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            abort(404);
        }

        if (! class_exists('Laravel\\Socialite\\Facades\\Socialite')) {
            return redirect()
                ->route('user.login')
                ->withErrors([
                    'oauth' => 'OAuth is ready in the UI, but laravel/socialite must be installed to enable provider login.',
                ]);
        }

        $config = config("services.{$provider}");

        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect'])) {
            return redirect()
                ->route('user.login')
                ->withErrors([
                    'oauth' => ucfirst($provider) . ' login needs client ID, client secret, and redirect URI in .env.',
                ]);
        }

        return null;
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'forgot_password');

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'min:6', 'max:150', 'exists:users,email', FormSecurity::disposableEmailRule()],
        ]);
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $data['email']],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        return back()
            ->with('status', 'Password reset link generated.')
            ->with('reset_link', route('password.reset', $token));
    }

    public function showResetPassword(string $token): View
    {
        return view('auth.reset-password', compact('token'));
    }

    public function resetPassword(Request $request, string $token): RedirectResponse
    {
        FormSecurity::validateRecaptcha($request, 'reset_password');

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'min:6', 'max:150', 'exists:users,email', FormSecurity::disposableEmailRule()],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid reset token.']);
        }

        User::where('email', $data['email'])->update(['password' => Hash::make($data['password'])]);
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect()->route('user.login')->with('status', 'Password updated. Please login.');
    }

    public function showAdminLogin(): View
    {
        return view('auth.admin-login');
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::query()->where('email', $credentials['email'])->where('is_active', true)->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            return back()->withErrors(['email' => 'Invalid admin credentials.'])->onlyInput('email');
        }

        $admin->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);

        return redirect()->route('admin.dashboard');
    }

    public function showAdminRegister(): View
    {
        return view('auth.admin-register');
    }

    public function adminRegister(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invite_code' => ['required', 'string'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($data['invite_code'] !== env('BOOKMYMOVIE_ADMIN_INVITE', 'BOOKMYMOVIE-ADMIN')) {
            return back()->withErrors(['invite_code' => 'Invalid invite code.'])->onlyInput('name', 'email');
        }

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin',
            'is_active' => true,
        ]);

        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);

        return redirect()->route('admin.dashboard')->with('status', 'Admin account created.');
    }

    public function adminLogout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_id');
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
