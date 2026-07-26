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
        $metaTitle = \Illuminate\Support\Str::limit(trim($__env->yieldContent('meta_title', $__env->yieldContent('title', ($siteSettings['site_name'] ?? 'BookMyMovie') . ' Admin'))), 60, '');
        $metaDescription = \Illuminate\Support\Str::limit(trim($__env->yieldContent('meta_description', 'Manage BookMyMovie movies, pricing, bookings, users, content, SEO, and site settings from the admin dashboard.')), 160, '');
        $defaultSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $metaTitle,
            'url' => $canonicalUrl,
        ];
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="noindex,nofollow">
    <link rel="canonical" href="@yield('canonical', $canonicalUrl)">
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
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: var(--admin-accent) var(--admin-scroll-track);
        }

        ::selection {
            background: color-mix(in srgb, var(--admin-accent) 38%, transparent);
            color: #fff;
        }

        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--admin-scroll-track);
        }

        ::-webkit-scrollbar-thumb {
            border: 2px solid var(--admin-scroll-track);
            border-radius: 999px;
            background: linear-gradient(180deg, var(--admin-accent-soft), var(--admin-accent));
        }

        [x-cloak] {
            display: none !important;
        }

        [data-admin-theme="midnight"] {
            --admin-bg: #070b13;
            --admin-bg-soft: #0d1422;
            --admin-surface: rgba(15, 23, 42, .86);
            --admin-surface-strong: rgba(3, 7, 18, .92);
            --admin-border: rgba(255, 255, 255, .09);
            --admin-accent: #dc2626;
            --admin-accent-soft: #fb7185;
            --admin-scroll-track: #05070d;
        }

        [data-admin-theme="platinum"] {
            --admin-bg: #061113;
            --admin-bg-soft: #0f262b;
            --admin-surface: rgba(13, 31, 36, .88);
            --admin-surface-strong: rgba(5, 14, 17, .94);
            --admin-border: rgba(153, 246, 228, .13);
            --admin-accent: #0f766e;
            --admin-accent-soft: #2dd4bf;
            --admin-scroll-track: #061112;
        }

        [data-admin-theme="amethyst"] {
            --admin-bg: #090713;
            --admin-bg-soft: #1b1230;
            --admin-surface: rgba(26, 20, 47, .88);
            --admin-surface-strong: rgba(12, 10, 24, .94);
            --admin-border: rgba(216, 180, 254, .13);
            --admin-accent: #9333ea;
            --admin-accent-soft: #c084fc;
            --admin-scroll-track: #090713;
        }

        .admin-shell {
            background:
                linear-gradient(135deg, var(--admin-bg), var(--admin-bg-soft) 48%, var(--admin-bg));
        }

        .admin-sidebar,
        .admin-panel,
        .admin-card {
            border-color: var(--admin-border);
            background-color: var(--admin-surface);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .admin-strong {
            background-color: var(--admin-surface-strong);
        }

        .admin-primary {
            background-color: var(--admin-accent);
        }

        .admin-primary:hover {
            filter: brightness(1.08);
        }

        .admin-sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: color-mix(in srgb, var(--admin-accent) 74%, transparent) transparent;
        }

        .admin-sidebar-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .admin-sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .admin-sidebar-scroll::-webkit-scrollbar-thumb {
            border: 0;
            background: color-mix(in srgb, var(--admin-accent) 72%, transparent);
        }

        .admin-nav-item {
            position: relative;
            isolation: isolate;
        }

        .admin-nav-item::before {
            position: absolute;
            inset: 6px;
            z-index: -1;
            border-radius: 12px;
            background: color-mix(in srgb, var(--admin-accent) 18%, transparent);
            opacity: 0;
            transform: scale(.94);
            transition: opacity 180ms ease, transform 180ms ease;
            content: "";
        }

        .admin-nav-item:hover::before,
        .admin-nav-active::before {
            opacity: 1;
            transform: scale(1);
        }

        .admin-nav-icon {
            color: var(--admin-accent-soft);
            transition: transform 180ms ease, color 180ms ease;
        }

        .admin-nav-item:hover .admin-nav-icon,
        .admin-nav-active .admin-nav-icon {
            color: #fff;
            transform: translateY(-1px) scale(1.06);
        }

        .admin-active-rail {
            background: linear-gradient(180deg, var(--admin-accent-soft), var(--admin-accent));
            box-shadow: 0 0 18px color-mix(in srgb, var(--admin-accent) 55%, transparent);
        }
    </style>
</head>

@php
    $adminNav = [
        ['id' => 'overview', 'label' => 'Overview', 'icon' => '<path d="M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z" />'],
        ['id' => 'movies', 'label' => 'Movies', 'icon' => '<path d="M4 7h16M7 3l2 4m6-4 2 4M5 7v12h14V7H5Zm4 4 5 3-5 3v-6Z" />'],
        ['id' => 'settings', 'label' => 'Website', 'icon' => '<path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Zm0 0v4M4.5 12h-2m19 0h-2M6.7 6.7 5.3 5.3m13.4 13.4-1.4-1.4m0-10.6 1.4-1.4M5.3 18.7l1.4-1.4" />'],
        ['id' => 'seo', 'label' => 'SEO', 'icon' => '<path d="M4 5h16M4 12h10M4 19h7m7-6 2 2 3-5" />'],
        ['id' => 'shows', 'label' => 'Shows', 'icon' => '<path d="M7 3v3m10-3v3M4 8h16M5 5h14v16H5V5Zm4 7h3v3H9v-3Z" />'],
        ['id' => 'bookings', 'label' => 'Bookings', 'icon' => '<path d="M5 4h14v16H5V4Zm3 4h8M8 12h8M8 16h5" />'],
        ['id' => 'coupons', 'label' => 'Coupons', 'icon' => '<path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8Zm6 1h.01M14 15h.01M15 9l-6 6" />'],
        ['id' => 'users', 'label' => 'Users', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m9-10a4 4 0 1 0-8 0 4 4 0 0 0 8 0Zm8 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />'],
        ['id' => 'reviews', 'label' => 'Reviews', 'icon' => '<path d="m12 3 2.7 5.47 6.03.88-4.37 4.26 1.03 6.01L12 16.78 6.61 19.62l1.03-6.01-4.37-4.26 6.03-.88L12 3Z" />'],
        ['id' => 'messages', 'label' => 'Messages', 'icon' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z" />'],
        ['id' => 'profile', 'label' => 'Profile', 'icon' => '<path d="M20 21a8 8 0 1 0-16 0m12-11a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" />'],
    ];
@endphp

<body class="min-h-screen bg-gray-950 text-gray-100 antialiased selection:bg-red-500/30"
    x-data="{
        adminTheme: localStorage.getItem('bookmymovie-admin-theme') || 'midnight',
        activeSection: (window.location.hash || '#overview').replace('#', ''),
        init() {
            localStorage.setItem('bookmymovie-admin-theme', this.adminTheme);
            window.addEventListener('hashchange', () => {
                this.activeSection = (window.location.hash || '#overview').replace('#', '');
            });

            this.$nextTick(() => {
                const sections = Array.from(document.querySelectorAll('main [id]'))
                    .filter((section) => ['overview','movies','settings','seo','shows','bookings','coupons','users','reviews','messages','profile'].includes(section.id));

                const observer = new IntersectionObserver((entries) => {
                    const visible = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

                    if (visible) this.activeSection = visible.target.id;
                }, { rootMargin: '-18% 0px -68% 0px', threshold: [0.1, 0.25, 0.5] });

                sections.forEach((section) => observer.observe(section));
            });
        }
    }"
    x-bind:data-admin-theme="adminTheme"
    x-effect="localStorage.setItem('bookmymovie-admin-theme', adminTheme)">
    <div class="admin-shell min-h-screen transition-colors duration-300 lg:grid lg:grid-cols-[288px_1fr]">
        <aside class="admin-sidebar z-20 flex max-h-[100svh] flex-col border-b border-white/10 p-4 shadow-2xl lg:sticky lg:top-0 lg:h-screen lg:border-b-0 lg:border-r lg:p-5">
            <div class="shrink-0">
                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('home') }}" class="group flex min-w-0 items-center gap-3 rounded-xl outline-none focus:ring-2 focus:ring-white/20">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-white/5 shadow-lg transition group-hover:-translate-y-0.5 group-hover:border-white/20">
                            <img src="{{ asset('images/header-logo-3.png') }}" alt="BookMyMovie" class="h-full w-full object-cover">
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-base font-black text-white">{{ $siteSettings['site_name'] ?? 'BookMyMovie' }}</span>
                            <span class="block text-xs font-bold text-gray-500">Admin workspace</span>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" class="lg:hidden">
                        @csrf
                        <button class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-black text-white transition hover:border-red-400/50 hover:bg-red-500/10 active:scale-95">
                            Logout
                        </button>
                    </form>
                </div>

                <div class="admin-card mt-4 rounded-xl border p-4 shadow-xl shadow-black/20">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[.18em] text-[var(--admin-accent-soft)]">Premium Admin</p>
                            <p class="mt-2 text-sm font-bold text-white">Secure control panel</p>
                        </div>
                        <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-300">Live</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg border border-white/10 bg-black/15 p-3">
                            <p class="font-bold text-gray-400">Session</p>
                            <p class="mt-1 font-black text-white">{{ config('session.admin_lifetime', 60) }} min</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-black/15 p-3">
                            <p class="font-bold text-gray-400">Domain</p>
                            <p class="mt-1 truncate font-black text-white">ahmershah.dev</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4" x-data="{
                    open: false,
                    themes: [
                        { value: 'midnight', label: 'Midnight', dot: 'bg-red-500' },
                        { value: 'platinum', label: 'Platinum', dot: 'bg-teal-400' },
                        { value: 'amethyst', label: 'Amethyst', dot: 'bg-purple-400' }
                    ]
                }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <label class="block text-[10px] font-black uppercase tracking-[.18em] text-gray-500">Theme</label>
                    <div class="relative mt-2">
                        <button type="button" @click="open = !open"
                            class="flex h-11 w-full items-center justify-between rounded-xl border border-white/10 bg-black/20 px-3 text-sm font-bold text-white shadow-sm outline-none transition hover:border-white/20 hover:bg-white/5 focus:ring-2 focus:ring-white/20"
                            :aria-expanded="open">
                            <span class="flex items-center gap-3">
                                <span class="h-2.5 w-2.5 rounded-full" :class="themes.find((theme) => theme.value === adminTheme)?.dot || 'bg-gray-400'"></span>
                                <span x-text="themes.find((theme) => theme.value === adminTheme)?.label || 'Theme'"></span>
                            </span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180 text-white' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                        <div x-cloak x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                            class="admin-strong absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-white/10 p-1 shadow-2xl shadow-black/50">
                            <template x-for="theme in themes" :key="theme.value">
                                <button type="button" @click="adminTheme = theme.value; open = false"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-bold transition"
                                    :class="adminTheme === theme.value ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'">
                                    <span class="h-2.5 w-2.5 rounded-full" :class="theme.dot"></span>
                                    <span x-text="theme.label"></span>
                                    <svg x-show="adminTheme === theme.value" class="ml-auto h-4 w-4 text-[var(--admin-accent-soft)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <nav class="admin-sidebar-scroll mt-4 flex min-h-0 gap-2 overflow-x-auto pb-2 lg:flex-1 lg:flex-col lg:overflow-x-hidden lg:overflow-y-auto lg:pr-1">
                @foreach($adminNav as $item)
                    <a href="{{ route('admin.dashboard') }}#{{ $item['id'] }}"
                        @click="activeSection = '{{ $item['id'] }}'"
                        class="admin-nav-item group flex shrink-0 items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-sm font-bold outline-none transition duration-200 hover:border-white/10 hover:text-white focus:ring-2 focus:ring-white/20 lg:w-full"
                        :class="activeSection === '{{ $item['id'] }}' ? 'admin-nav-active border-white/10 bg-white/5 text-white shadow-lg shadow-black/20' : 'text-gray-400'">
                        <span class="admin-active-rail absolute left-0 hidden h-7 w-1 rounded-r-full opacity-0 transition lg:block" :class="activeSection === '{{ $item['id'] }}' ? 'opacity-100' : 'group-hover:opacity-60'"></span>
                        <span class="admin-nav-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-black/15">
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                {!! $item['icon'] !!}
                            </svg>
                        </span>
                        <span class="whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="shrink-0 border-t border-white/10 pt-4">
                <a href="{{ route('home') }}" class="flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-black text-white transition hover:border-white/20 hover:bg-white/10 active:scale-95">
                    <svg class="h-4 w-4 text-[var(--admin-accent-soft)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6v6m0-6L10 14" />
                    </svg>
                    Public site
                </a>
                <form method="POST" action="{{ route('admin.logout') }}" class="mt-2 hidden lg:block">
                    @csrf
                    <button class="admin-primary flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-black/20 transition active:scale-95">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m12 0-4-4m4 4-4 4m5-11h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-3" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="relative min-w-0 p-4 sm:p-6 lg:p-8 xl:p-10">
            @if(session('status'))
                <div x-data="{ show: true }" x-show="show" x-transition.duration.200ms
                    class="mb-6 flex items-center justify-between gap-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-4 shadow-2xl shadow-black/20 backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                            </svg>
                        </span>
                        <p class="text-sm font-bold text-emerald-100">{{ session('status') }}</p>
                    </div>
                    <button type="button" @click="show = false" class="rounded-lg p-1 text-emerald-200/70 transition hover:bg-white/10 hover:text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</body>

</html>
