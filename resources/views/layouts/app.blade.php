<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $siteSettings['site_name'] ?? 'BookMyMovie')</title>
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
            animation: fly-to-target 720ms cubic-bezier(.2, .8, .2, 1) forwards;
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
            to {
                transform: translate(var(--fly-x), var(--fly-y)) scale(.18) rotate(8deg);
                opacity: 0;
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
                fly(event, targetId, counterKey) {
                    const source = event.currentTarget.closest('[data-fly-source]') || event.currentTarget;
                    const target = document.getElementById(targetId);

                    if (!source || !target) {
                        this[counterKey]++;
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
                    this[counterKey]++;
                    window.setTimeout(() => clone.remove(), 760);
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
