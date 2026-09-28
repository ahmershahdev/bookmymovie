<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Emails are queued. Where no long-running worker (Supervisor, systemd) is
| available, the scheduler drains the queue every minute instead:
|   * * * * * php /path/to/artisan schedule:run
*/
// Expired seat holds free seats silently; this sweep tells the waitlist.
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('carts')->where('expires_at', '<=', now())->delete();
    \Illuminate\Support\Facades\DB::table('show_waitlists')->whereNull('notified_at')->distinct()->pluck('show_id')
        ->each(fn ($showId) => \App\Jobs\ProcessShowWaitlist::dispatch((int) $showId));
})->everyMinute()->name('waitlist-sweep')->withoutOverlapping();

Artisan::command('push:keys', function () {
    if (PHP_OS_FAMILY === 'Windows' && ! getenv('OPENSSL_CONF') && is_file(dirname(PHP_BINARY).'/extras/ssl/openssl.cnf')) {
        putenv('OPENSSL_CONF='.dirname(PHP_BINARY).'/extras/ssl/openssl.cnf');
    }
    $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
    $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
    $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
})->purpose('Print a new VAPID key pair for web push');

Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
