<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $canonicalBase = rtrim($siteSettings['canonical_base_url'] ?? 'https://bookmymovie.ahmershah.dev', '/');
        $canonicalPath = '/' . ltrim(request()->getPathInfo(), '/');
        $canonicalUrl = $canonicalBase . ($canonicalPath === '/' ? '/' : $canonicalPath);
        $metaTitle = \Illuminate\Support\Str::limit(trim($__env->yieldContent('meta_title', $__env->yieldContent('title', ($siteSettings['site_name'] ?? 'BookMyMovie') . ' Account'))), 60, '');
        $metaDescription = \Illuminate\Support\Str::limit(trim($__env->yieldContent('meta_description', 'Access your BookMyMovie account to manage movie bookings, saved seats, wishlists, and profile details.')), 160, '');
        $defaultSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $metaTitle,
            'url' => $canonicalUrl,
        ];
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="@yield('canonical', $canonicalUrl)">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="@yield('canonical', $canonicalUrl)">
    <meta property="og:type" content="website">
    <script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">@yield('json_ld', json_encode($defaultSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))</script>
    <meta name="csp-nonce" content="{{ $cspNonce ?? '' }}">
    <link rel="icon" href="{{ asset('images/favicon/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('images/site.webmanifest') }}">
    <meta name="theme-color" content="#05070d">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script nonce="{{ $cspNonce ?? '' }}" defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style nonce="{{ $cspNonce ?? '' }}">
        html {
            scrollbar-width: thin;
            scrollbar-color: #dc2626 #05070d;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #05070d;
        }

        ::-webkit-scrollbar-thumb {
            cursor: grab;
            border: 2px solid #05070d;
            border-radius: 999px;
            background: linear-gradient(180deg, #ef4444, #991b1b 48%, #f87171);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .18), 0 0 18px rgba(220, 38, 38, .35);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #f87171, #dc2626 48%, #7f1d1d);
        }

        ::-webkit-scrollbar-thumb:active {
            cursor: grabbing;
            background: linear-gradient(180deg, #fecaca, #ef4444 48%, #7f1d1d);
        }

        [x-cloak] {
            display: none !important;
        }

        .auth-orbit {
            background:
                radial-gradient(circle at 18% 22%, rgba(220, 38, 38, .28), transparent 30%),
                radial-gradient(circle at 86% 18%, rgba(212, 175, 55, .16), transparent 26%),
                linear-gradient(145deg, #090d16 0%, #111827 48%, #130b0c 100%);
        }

        .auth-shell {
            transform-style: preserve-3d;
            transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
        }

        .auth-shell:hover {
            transform: perspective(1200px) rotateX(1.8deg) rotateY(-1.8deg);
            border-color: rgba(248, 113, 113, .32);
            box-shadow: 0 32px 90px rgba(0, 0, 0, .54), 0 0 40px rgba(220, 38, 38, .14);
        }

        .auth-glass {
            position: relative;
            overflow: hidden;
        }

        .auth-glass::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(120deg, transparent 15%, rgba(255, 255, 255, .08), transparent 38%);
            transform: translateX(-120%);
            transition: transform 650ms ease;
        }

        .auth-glass:hover::before {
            transform: translateX(120%);
        }

        .auth-field {
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
        }

        .auth-field:focus-within {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(0, 0, 0, .2), 0 0 0 1px rgba(248, 113, 113, .18);
        }

        .brand-gradient {
            background: linear-gradient(90deg, #ef4444, #f8fafc, #dc2626, #d4af37, #ef4444);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: brand-shift 5s linear infinite;
        }

        @keyframes brand-shift {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 300% 50%;
            }
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen bg-ink font-sans text-gray-100 antialiased selection:bg-red-600 selection:text-white">
    <main class="auth-orbit min-h-screen px-4 py-6 sm:px-6 lg:px-8">
        <x-breadcrumbs auth="true" />

        <section
            class="auth-shell mx-auto grid min-h-[calc(100vh-3rem)] w-full max-w-6xl overflow-hidden rounded-lg border border-white/10 bg-gray-950/80 shadow-2xl shadow-black/50 lg:grid-cols-[.95fr_1.05fr]">
            <aside class="relative hidden min-h-full border-r border-white/10 bg-black/20 p-8 lg:flex lg:flex-col">
                <a href="{{ route('home') }}"
                    class="flex w-max items-center gap-3 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400">
                    <img src="{{ asset('images/logo.png') }}" alt="BookMyMovie logo"
                        class="h-12 w-12 rounded-md object-cover ring-1 ring-red-500/30">
                    <span class="text-xl font-black"><span class="brand-gradient">{{ $siteSettings['site_name'] ?? 'BookMyMovie' }}</span></span>
                </a>

                <div class="mt-auto space-y-8">
                    <div>
                        <p class="text-sm font-bold uppercase text-gold">Fast booking access</p>
                        <h1 class="mt-3 max-w-md text-4xl font-black leading-tight text-white">
                            Pick seats, save favorites, and manage every ticket from one account.
                        </h1>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg border border-white/10 bg-white/[.04] p-4">
                            <p class="text-2xl font-black text-white">24/7</p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">Ticket access</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[.04] p-4">
                            <p class="text-2xl font-black text-white">1-tap</p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">Wishlist saves</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[.04] p-4">
                            <p class="text-2xl font-black text-white">Secure</p>
                            <p class="mt-1 text-xs font-semibold text-gray-400">Account tools</p>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="flex min-h-full items-center justify-center px-4 py-8 sm:px-8 lg:px-12">
                <section class="w-full max-w-md">
                    <a href="{{ route('home') }}" class="mb-8 flex w-max items-center gap-3 rounded-md lg:hidden">
                        <img src="{{ asset('images/logo.png') }}" alt="BookMyMovie logo"
                            class="h-11 w-11 rounded-md object-cover">
                        <span class="text-lg font-black"><span class="brand-gradient">{{ $siteSettings['site_name'] ?? 'BookMyMovie' }}</span></span>
                    </a>

                    <div class="auth-glass mb-8 rounded-lg border border-white/10 bg-white/[.03] p-5">
                        @hasSection('eyebrow')
                            <p class="text-sm font-bold uppercase text-gold">@yield('eyebrow')</p>
                        @endif
                        <h2 class="mt-2 text-3xl font-black leading-tight text-white">@yield('heading', 'Welcome back')</h2>
                        @hasSection('subheading')
                            <p class="mt-3 text-sm leading-6 text-gray-300">@yield('subheading')</p>
                        @endif
                    </div>

                    @if(session('status'))
                        <p
                            class="mb-4 rounded-md border border-green-500/30 bg-green-950 px-4 py-3 text-sm font-bold text-green-100">
                            {{ session('status') }}
                        </p>
                    @endif
                    @if(session('reset_link'))
                        <div class="mb-4 rounded-md border border-gold/30 bg-gold/10 px-4 py-3">
                            <p class="text-sm font-bold text-gold">Reset link</p>
                            <a href="{{ session('reset_link') }}"
                                class="mt-2 block break-all text-sm font-semibold text-gray-100 underline decoration-red-400 underline-offset-4 hover:text-white">
                                {{ session('reset_link') }}
                            </a>
                        </div>
                    @endif
                    @error('oauth')
                        <p
                            class="mb-4 rounded-md border border-amber-500/30 bg-amber-950 px-4 py-3 text-sm font-bold text-amber-100">
                            {{ $message }}
                        </p>
                    @enderror

                    @yield('content')
                </section>
            </div>
        </section>
    </main>

    <script nonce="{{ $cspNonce ?? '' }}">
        function passwordStrengthForm(initialPassword = '') {
            return {
                password: initialPassword,
                showPassword: false,
                showConfirmation: false,
                checks: [
                    { label: '8+ characters', test: value => value.length >= 8 },
                    { label: 'Upper and lower case', test: value => /[a-z]/.test(value) && /[A-Z]/.test(value) },
                    { label: 'Number included', test: value => /\d/.test(value) },
                    { label: 'Symbol included', test: value => /[^A-Za-z0-9]/.test(value) },
                ],
                score() {
                    return this.checks.filter(check => check.test(this.password)).length;
                },
                width() {
                    return `${Math.max(12, this.score() * 25)}%`;
                },
                label() {
                    return ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'][this.score()];
                },
                color() {
                    return ['bg-red-600', 'bg-red-600', 'bg-amber-400', 'bg-lime-500', 'bg-emerald-500'][this.score()];
                },
            };
        }
    </script>
    @stack('scripts')
</body>

</html>
