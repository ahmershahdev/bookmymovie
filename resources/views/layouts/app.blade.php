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
        $metaTitle = trim($__env->yieldContent('meta_title', $__env->yieldContent('title', $siteSettings['default_meta_title'] ?? 'BookMyMovie - Book Cinema Tickets Online')));
        $metaTitle = \Illuminate\Support\Str::limit($metaTitle, 60, '');
        $metaDescription = trim($__env->yieldContent('meta_description', $siteSettings['default_meta_description'] ?? 'Book movie tickets, compare shows, reserve seats, and manage cinema bookings online with BookMyMovie.'));
        $metaDescription = \Illuminate\Support\Str::limit($metaDescription, 160, '');
        $defaultSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteSettings['site_name'] ?? 'BookMyMovie',
            'url' => $canonicalBase . '/',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $canonicalBase . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="@yield('canonical', $canonicalUrl)">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="@yield('canonical', $canonicalUrl)">
    <meta property="og:type" content="@yield('og_type', 'website')">
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

        .brand-gradient {
            background: linear-gradient(90deg, #ef4444, #f8fafc, #dc2626, #d4af37, #ef4444);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: brand-shift 5s linear infinite;
        }

        .ticker-track {
            animation: ticker-scroll 26s linear infinite;
        }

        .ticker-shell:hover .ticker-track {
            animation-play-state: paused;
        }

        .typing-text {
            overflow: hidden;
            white-space: nowrap;
            border-right: 2px solid #dc2626;
            animation: typing 4s steps(46, end) infinite alternate, caret 700ms step-end infinite;
        }

        .fly-clone {
            position: fixed;
            z-index: 80;
            pointer-events: none;
            transform-origin: center;
            animation: fly-to-target 980ms cubic-bezier(.18, .72, .18, 1) forwards;
            will-change: transform, opacity;
        }

        .nav-action-shake {
            animation: nav-action-shake 620ms ease both;
        }

        .counter-pop {
            animation: counter-pop 520ms ease both;
        }

        .premium-tilt {
            transform-style: preserve-3d;
            transition: transform 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
        }

        .premium-tilt:hover {
            transform: perspective(900px) rotateX(4deg) rotateY(-5deg) translateY(-4px);
            border-color: rgba(248, 113, 113, .42);
            box-shadow: 0 24px 70px rgba(0, 0, 0, .35), 0 0 26px rgba(220, 38, 38, .16);
        }

        .with-breadcrumb>section:first-child {
            padding-top: 11.5rem !important;
        }

        @media (min-width: 1024px) {
            .with-breadcrumb>section:first-child {
                padding-top: 9.5rem !important;
            }
        }

        .infinite-track {
            width: max-content;
            animation: ticker-scroll 32s linear infinite;
        }

        .infinite-shell:hover .infinite-track {
            animation-play-state: paused;
        }

        @keyframes brand-shift {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 300% 50%;
            }
        }

        @keyframes ticker-scroll {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-50%);
            }
        }

        @keyframes typing {
            from {
                width: 0;
            }

            to {
                width: 100%;
            }
        }

        @keyframes caret {
            50% {
                border-color: transparent;
            }
        }

        @keyframes fly-to-target {
            0% {
                opacity: 1;
                transform: translate(0, 0) scale(1) rotate(0deg);
            }

            55% {
                opacity: .92;
                transform: translate(calc(var(--fly-x) * .72), calc(var(--fly-y) * .58 - 42px)) scale(.42) rotate(5deg);
            }

            to {
                transform: translate(var(--fly-x), var(--fly-y)) scale(.12) rotate(10deg);
                opacity: 0;
            }
        }

        @keyframes nav-action-shake {
            0%, 100% {
                transform: translateX(0) scale(1);
            }

            18%, 54% {
                transform: translateX(-3px) scale(1.08);
            }

            36%, 72% {
                transform: translateX(3px) scale(1.08);
            }
        }

        @keyframes counter-pop {
            0% {
                transform: scale(.55);
            }

            60% {
                transform: scale(1.25);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen bg-gray-950 font-sans text-gray-100 antialiased selection:bg-red-600 selection:text-white"
    x-data="bookMyMovieUi({{ $initialCartCount ?? 0 }}, {{ $initialWishlistCount ?? 0 }})" x-init="init()">
    <x-navbar :movies="$navMovies ?? ($movies ?? [])" />

    <x-breadcrumbs />

    <main class="{{ request()->routeIs('home') ? '' : 'with-breadcrumb' }}">
        @if(session('status'))
            <div class="fixed right-4 top-24 z-50 rounded-lg border border-green-500/30 bg-green-950 px-5 py-3 text-sm font-bold text-green-100 shadow-2xl shadow-black/40">
                {{ session('status') }}
            </div>
        @endif
        @yield('content')
    </main>

    <x-site-footer />
    <x-scroll-to-top />

    <script nonce="{{ $cspNonce ?? '' }}">
        function bookMyMovieUi(initialCartCount = 0, initialWishlistCount = 0) {
            return {
                cartCount: initialCartCount,
                wishlistCount: initialWishlistCount,
                scrolled: false,
                init() {
                    this.scrolled = window.scrollY > 300;
                    window.addEventListener('scroll', () => {
                        this.scrolled = window.scrollY > 300;
                    });
                },
                animateTarget(target) {
                    if (!target) return;

                    target.classList.remove('nav-action-shake');
                    void target.offsetWidth;
                    target.classList.add('nav-action-shake');
                    window.setTimeout(() => target.classList.remove('nav-action-shake'), 700);
                },
                fly(event, targetId, counterKey = null, increment = 1) {
                    const source = event.submitter?.closest('[data-fly-source]') || event.currentTarget.closest('[data-fly-source]') || event.currentTarget;
                    const target = document.getElementById(targetId);

                    if (!source || !target) {
                        if (counterKey) this[counterKey] += increment;
                        return;
                    }

                    const sourceRect = source.getBoundingClientRect();
                    const targetRect = target.getBoundingClientRect();
                    const clone = source.cloneNode(true);

                    clone.classList.add('fly-clone', 'rounded-2xl', 'border', 'border-red-500/40', 'bg-gray-900');
                    clone.style.left = `${sourceRect.left}px`;
                    clone.style.top = `${sourceRect.top}px`;
                    clone.style.width = `${Math.min(sourceRect.width, 220)}px`;
                    clone.style.height = `${Math.min(sourceRect.height, 280)}px`;
                    clone.style.setProperty('--fly-x', `${targetRect.left + targetRect.width / 2 - sourceRect.left - sourceRect.width / 2}px`);
                    clone.style.setProperty('--fly-y', `${targetRect.top + targetRect.height / 2 - sourceRect.top - sourceRect.height / 2}px`);

                    document.body.appendChild(clone);
                    if (counterKey) this[counterKey] += increment;
                    this.animateTarget(target);
                    window.setTimeout(() => clone.remove(), 1040);
                },
                flyAndSubmit(event, targetId, counterKey = null) {
                    const form = event.target;

                    if (form.dataset.submitting === '1') {
                        return;
                    }

                    form.dataset.submitting = '1';
                    const selectedSeats = new FormData(form).getAll('seats[]').length;
                    const increment = Math.max(1, selectedSeats);
                    this.fly(event, targetId, counterKey, increment);
                    window.setTimeout(() => form.submit(), 820);
                },
                addToCompare(movie) {
                    const key = 'bookmymovie.compare.movies';
                    const current = JSON.parse(localStorage.getItem(key) || '[]');
                    const existing = current.findIndex(item => Number(item.id) === Number(movie.id));

                    if (existing !== -1) {
                        current.splice(existing, 1, movie);
                        localStorage.setItem(key, JSON.stringify(current.slice(0, 4)));
                        window.dispatchEvent(new CustomEvent('bookmymovie-compare-updated'));
                        return;
                    }

                    if (current.length >= 4) {
                        current.shift();
                    }

                    current.push(movie);
                    localStorage.setItem(key, JSON.stringify(current));
                    window.dispatchEvent(new CustomEvent('bookmymovie-compare-updated'));
                },
                scrollTop() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
            };
        }
    </script>
    @stack('scripts')
</body>

</html>
