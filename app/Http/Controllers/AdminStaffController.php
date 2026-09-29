<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Support\AuditLog;
use App\Support\SecurityLog;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

/**
 * Staff accounts and roles (owner only) and each admin's own two-step
 * sign-in with an authenticator app, including the sign-in challenge.
 */
class AdminStaffController extends AdminController
{
    private const PENDING = 'admin_2fa_pending';

    /* Staff ------------------------------------------------------------------ */

    public function index(Request $request): Response|RedirectResponse
    {
        $me = $this->admin($request);
        if (! $me) {
            return redirect()->route('admin.login');
        }

        return $this->page('Admin/Staff', [
            'staff' => Admin::query()->orderByRaw("FIELD(role, 'superadmin', 'admin', 'box_office')")->orderBy('name')->get()->map(fn (Admin $admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
                'role_label' => $admin->roleLabel(),
                'active' => (bool) $admin->is_active,
                'two_factor' => $admin->hasTwoFactor(),
                'last_login' => $admin->last_login_at?->diffForHumans() ?? 'Never',
                'me' => $admin->id === $me->id,
            ])->values(),
            'roles' => collect(Admin::ROLES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
        ], ['title' => 'Staff & roles | Admin', 'description' => 'Back office accounts.', 'robots' => 'noindex, nofollow']);
    }

    public function store(Request $request): RedirectResponse
    {
        $me = $this->admin($request) ?? abort(403);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150', 'unique:admins,email'],
            'role' => ['required', Rule::in(array_keys(Admin::ROLES))],
            'password' => ['required', 'string', 'max:72', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $admin = Admin::create([...$data, 'email' => strtolower($data['email']), 'is_active' => true, 'created_by' => $me->id]);
        AuditLog::record('admin.staff.created', $admin, ['role' => $admin->role], $request, 'admin', $me->id);

        return back()->with('status', $admin->name.' can now sign in as '.$admin->roleLabel().'.');
    }

    public function update(Request $request, Admin $staff): RedirectResponse
    {
        $me = $this->admin($request) ?? abort(403);
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(Admin::ROLES))],
            'active' => ['required', 'boolean'],
        ]);

        if ($staff->id === $me->id && ($data['role'] !== 'superadmin' || ! $data['active'])) {
            return back()->withErrors(['role' => 'You cannot demote or deactivate yourself. Ask another owner.']);
        }

        // Never leave the site without an active owner (checked under a lock).
        $result = DB::transaction(function () use ($staff, $data) {
            $owners = Admin::query()->where('role', 'superadmin')->where('is_active', true)->lockForUpdate()->pluck('id');
            if ($owners->count() === 1 && $owners->first() === $staff->id && ($data['role'] !== 'superadmin' || ! $data['active'])) {
                return false;
            }
            $staff->forceFill(['role' => $data['role'], 'is_active' => $data['active']])->save();

            return true;
        });

        if (! $result) {
            return back()->withErrors(['role' => 'There must always be at least one active owner.']);
        }

        // A deactivated admin is refused on their very next request (see AdminController::admin()).
        AuditLog::record('admin.staff.updated', $staff, $data, $request, 'admin', $me->id);

        return back()->with('status', $staff->name.' is now '.$staff->roleLabel().($staff->is_active ? '.' : ' and deactivated.'));
    }

    public function resetTwoFactor(Request $request, Admin $staff): RedirectResponse
    {
        $me = $this->admin($request) ?? abort(403);
        $staff->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        AuditLog::record('admin.staff.2fa_reset', $staff, [], $request, 'admin', $me->id);

        return back()->with('status', 'Two-step sign-in was reset for '.$staff->name.'. They can set it up again.');
    }

    /* My security ------------------------------------------------------------ */

    public function security(Request $request): Response|RedirectResponse
    {
        $admin = $this->admin($request);
        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $pending = $request->session()->get('admin_2fa_setup');

        return $this->page('Admin/Security', [
            'enabled' => $admin->hasTwoFactor(),
            'setup' => $pending ? [
                'secret' => trim(chunk_split($pending, 4, ' ')),
                'uri' => Totp::uri($pending, $admin->email, config('app.name', 'BookMyMovie').' Admin'),
            ] : null,
            'recoveryCodes' => $request->session()->pull('admin_2fa_codes'),
            'codesLeft' => count((array) $admin->two_factor_recovery_codes),
            'role' => $admin->roleLabel(),
            'profile' => ['name' => $admin->name, 'email' => $admin->email],
        ], ['title' => 'Security | Admin', 'description' => 'Two-step sign-in.', 'robots' => 'noindex, nofollow']);
    }

    public function beginSetup(Request $request): RedirectResponse
    {
        $this->admin($request) ?? abort(403);
        $request->session()->put('admin_2fa_setup', Totp::secret());

        return back();
    }

    public function confirmSetup(Request $request): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $secret = (string) $request->session()->get('admin_2fa_setup');

        if ($secret === '' || Totp::verify($secret, $data['code']) === null) {
            return back()->withErrors(['code' => 'That code did not match. Check the time on your phone and try the newest code.']);
        }

        $codes = Totp::recoveryCodes();
        $admin->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(fn ($code) => Hash::make($code), $codes),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $request->session()->forget('admin_2fa_setup');
        $request->session()->flash('admin_2fa_codes', $codes);
        AuditLog::record('admin.2fa.enabled', $admin, [], $request, 'admin', $admin->id);

        return back()->with('status', 'Two-step sign-in is on. Save your recovery codes now: they are shown only once.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $admin = $this->admin($request) ?? abort(403);
        $data = $request->validate(['password' => ['required', 'string', 'max:72']]);

        if (! Hash::check($data['password'], $admin->password)) {
            return back()->withErrors(['password' => 'That password is not right.']);
        }

        $admin->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        AuditLog::record('admin.2fa.disabled', $admin, [], $request, 'admin', $admin->id);

        return back()->with('status', 'Two-step sign-in is off.');
    }

    /* Sign-in challenge ------------------------------------------------------ */

    public function challenge(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has(self::PENDING)) {
            return redirect()->route('admin.login');
        }

        return $this->page('Auth/AdminTwoFactor', [], ['title' => 'Two-step sign-in | Admin', 'description' => 'Enter the code from your authenticator app.', 'robots' => 'noindex, nofollow']);
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::PENDING);
        if (! is_array($pending) || ($pending['until'] ?? 0) < now()->timestamp) {
            $request->session()->forget(self::PENDING);

            return redirect()->route('admin.login')->withErrors(['email' => 'That sign-in took too long. Please start again.']);
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:12']]);
        $admin = Admin::query()->whereKey($pending['id'])->where('is_active', true)->first();
        $key = 'admin-2fa:'.$pending['id'];

        if (! $admin || RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Wait a few minutes and sign in again.']);
        }

        $code = strtoupper(trim($data['code']));
        $ok = false;

        if (preg_match('/^\d{6}$/', preg_replace('/\s/', '', $code) ?? '')) {
            // The step cache stops the same code being used twice.
            $step = Totp::verify((string) $admin->two_factor_secret, $code, (int) cache()->get('admin-2fa-step:'.$admin->id, 0) ?: null);
            if ($step !== null) {
                cache()->put('admin-2fa-step:'.$admin->id, $step, now()->addMinutes(5));
                $ok = true;
            }
        } else {
            // A recovery code works once and is then removed.
            $codes = (array) $admin->two_factor_recovery_codes;
            foreach ($codes as $index => $hash) {
                if (Hash::check($code, $hash)) {
                    unset($codes[$index]);
                    $admin->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    $ok = true;
                    break;
                }
            }
        }

        if (! $ok) {
            RateLimiter::hit($key, 300);
            SecurityLog::record(SecurityLog::FAILED_LOGIN, $request, ['guard' => 'admin_2fa', 'admin_id' => $admin->id]);

            return back()->withErrors(['code' => 'That code did not match.']);
        }

        RateLimiter::clear($key);
        $request->session()->forget(self::PENDING);

        return $this->completeAdminLogin($request, $admin);
    }

    /** Shared by the password step (no 2FA) and the code step. */
    public static function completeAdminLogin(Request $request, Admin $admin): RedirectResponse
    {
        $admin->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();
        AuditLog::record('admin.login', $admin, ['two_factor' => $admin->hasTwoFactor()], $request, 'admin', $admin->id);
        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_authenticated_at', now()->timestamp);
        SecurityLog::record(SecurityLog::ADMIN_LOGIN, $request, ['admin_id' => $admin->id]);

        $intended = (string) $request->session()->pull('admin_intended', '');
        if ($intended !== '' && Str::startsWith($intended, url('/admin/'))) {
            return redirect()->to($intended);
        }

        return redirect()->route('admin.dashboard');
    }

    public static function startChallenge(Request $request, Admin $admin): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put(self::PENDING, ['id' => $admin->id, 'until' => now()->addMinutes(5)->timestamp]);

        return redirect()->route('admin.two-factor');
    }
}
