<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets the public demo admin look around the back office while refusing
 * every change it tries to make (see config bookmymovie.admin.demo_read_only).
 */
class BlockDemoAdminWrites
{
    private const ALWAYS_ALLOWED = ['admin.login', 'admin.logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || in_array($request->route()?->getName(), self::ALWAYS_ALLOWED, true)) {
            return $next($request);
        }

        $adminId = $request->hasSession() ? $request->session()->get('admin_id') : null;
        $admin = $adminId ? Admin::query()->find($adminId) : null;

        if ($admin?->isReadOnly()) {
            return back()->withErrors(['demo' => 'The demo admin is read-only on the live site, so nothing was changed. Run the project locally to try edits.']);
        }

        return $next($request);
    }
}
