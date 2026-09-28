<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role checks for the whole back office in one place. Each admin route maps
 * to an ability (see Admin::ABILITIES); anything not listed needs "manage",
 * so a new route is closed to box office staff until someone opens it.
 * Signed-out requests pass through: the controllers send them to sign in.
 */
class AuthorizeAdminRole
{
    /** Route name patterns => ability. First match wins. */
    private const MAP = [
        'admin.login' => null, 'admin.logout' => null, 'admin.two-factor*' => null,
        'admin.credentials.*' => null, 'admin.register' => null,
        'admin.staff*' => 'own', 'admin.settings*' => 'own',
        'admin.security*' => 'view',
        'admin.tickets.verify' => 'admit', 'admin.tickets.admit' => 'admit',
    ];

    /** Pages every role may open (GET only). */
    private const VIEW_PAGES = ['admin.dashboard', 'admin.dashboard.movie', 'admin.activity', 'admin.users', 'admin.users.show', 'admin.reviews', 'admin.analytics'];

    public function handle(Request $request, Closure $next): Response
    {
        $name = (string) $request->route()?->getName();
        $ability = $this->abilityFor($name, $request);

        if ($ability === null) {
            return $next($request);
        }

        $id = $request->hasSession() ? $request->session()->get('admin_id') : null;
        $admin = $id ? Admin::query()->find($id) : null;

        if ($admin && ! $admin->allows($ability)) {
            if ($request->isMethodSafe()) {
                abort(403, 'Your role ('.$admin->roleLabel().') cannot open this page.');
            }

            return back()->withErrors(['role' => 'Your role ('.$admin->roleLabel().') cannot do that. Ask the owner.']);
        }

        return $next($request);
    }

    private function abilityFor(string $name, Request $request): ?string
    {
        foreach (self::MAP as $pattern => $ability) {
            if (Str::is($pattern, $name)) {
                return $ability;
            }
        }

        if ($request->isMethodSafe() && in_array($name, self::VIEW_PAGES, true)) {
            return 'view';
        }

        // Every admin may edit their own profile from the dashboard form.
        if ($name === '' && $request->is('admin/dashboard')) {
            return match ($request->input('_action')) {
                'update_profile' => 'view',
                'update_settings', 'update_pages' => 'own',
                default => 'manage',
            };
        }

        return 'manage';
    }
}
