<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\BlockDemoAdminWrites;
use App\Http\Middleware\EnforceBans;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ShieldAgainstAbuse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('user.login'));
        $middleware->redirectUsersTo(fn () => route('user.dashboard'));

        // The shield runs first so banned or flooding clients are rejected
        // before a session is started or the database is touched.
        $middleware->web(prepend: [
            ShieldAgainstAbuse::class,
        ], append: [
            SetLocale::class,
            EnforceBans::class,
            HandleInertiaRequests::class,
            AddSecurityHeaders::class,
        ]);

        $middleware->encryptCookies(except: []);

        // Payment providers post results back cross-site without our CSRF
        // token; those endpoints verify the provider's signature instead.
        $middleware->validateCsrfTokens(except: [
            'payments/jazzcash/callback',
            'payments/easypaisa/*/confirm',
            'payments/*/return',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();

        // Inertia visits get the React error page instead of an HTML document
        // shown in a modal. Local debug keeps Laravel's own exception page.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if ($status === 419) {
                return back()->with('status', 'The page expired, please try again.');
            }

            if (! in_array($status, [403, 404, 429, 500, 503], true) || (app()->hasDebugModeEnabled() && $status >= 500)) {
                return $response;
            }

            // Inertia visits always get the React page. Direct browser loads do
            // too for "not found"-style errors, so a broken link lands on the
            // full site (navbar, search, films) rather than a bare page.
            // Server errors and non-HTML clients keep the plain responses.
            $directPage = ! $request->header('X-Inertia') && $request->isMethod('GET') && $request->acceptsHtml() && ! $request->expectsJson();
            if (! $request->header('X-Inertia') && ! ($directPage && in_array($status, [403, 404, 429], true))) {
                return $response;
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
