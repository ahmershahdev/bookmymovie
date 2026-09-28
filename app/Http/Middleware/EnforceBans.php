<?php

namespace App\Http\Middleware;

use App\Models\BannedDevice;
use App\Models\BannedIp;
use App\Support\DeviceIdentity;
use App\Support\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin bans, checked on every request from cached sets (no query per hit):
 *
 *  - a banned IP or a banned browser sees a plain 403 on every page, so a
 *    banned member cannot simply sign up again from the same place;
 *  - a banned account is signed out on its next request, on every device.
 *
 * The staff area stays reachable so an admin sharing a network with a
 * banned member (office, mobile carrier NAT) is never locked out.
 */
class EnforceBans
{
    public function handle(Request $request, Closure $next): Response
    {
        $staffArea = $request->is('admin', 'admin/*');
        $device = DeviceIdentity::hash($request);

        if (! $staffArea && (BannedIp::isBanned($request->ip()) || BannedDevice::isBanned($device))) {
            // One log line per client per ten minutes, however hard it knocks.
            if (Cache::add('banned_visit:'.$request->ip().':'.substr($device, 0, 12), 1, now()->addMinutes(10))) {
                SecurityLog::record(SecurityLog::BANNED_VISIT, $request, ['device' => substr($device, 0, 12)]);
            }

            abort(403, 'Access from this device or network has been suspended. Contact support if you think this is a mistake.');
        }

        $user = Auth::user();
        if ($user && $user->is_blocked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('user.login')->withErrors(['email' => 'This account has been suspended. Contact support if you think this is a mistake.']);
        }

        if ($user) {
            DeviceIdentity::remember($request, $user);
        }

        return $next($request);
    }
}
