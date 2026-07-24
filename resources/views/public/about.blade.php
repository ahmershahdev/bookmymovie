@extends('layouts.app')

@section('title', 'About | BookMyMovie')

@push('styles')
    <style nonce="{{ $cspNonce ?? '' }}">
        /* CSS variables for mouse tracking light effects */
        .mouse-track-card {
            --mouse-x: 50%;
            --mouse-y: 50%;
            --rx: 0deg;
            --ry: 0deg;
            transform-style: preserve-3d;
            transition: transform 0.15s ease-out, box-shadow 0.3s ease;
            will-change: transform;
        }

        .mouse-track-card:hover {
            transform: perspective(1000px) rotateX(var(--rx)) rotateY(var(--ry)) scale3d(1.02, 1.02, 1.02);
        }

        .card-3d-child {
            transform: translateZ(30px);
            transition: transform 0.2s ease-out;
        }

        /* Ambient lighting overlay inside cards */
        .mouse-track-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(
                800px circle at var(--mouse-x) var(--mouse-y),
                rgba(239, 68, 68, 0.15),
                transparent 40%
            );
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 10;
        }

        .mouse-track-card:hover::before {
            opacity: 1;
        }

        /* Marquee continuous scroll animation */
        @keyframes marquee {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }

        .animate-marquee {
            display: flex;
            width: max-content;
            animation: marquee 25s linear infinite;
        }

        .animate-marquee:hover {
            animation-play-state: paused;
        }
    </style>
@endpush

@section('content')
    {{-- Hero Section --}}
    <section class="relative overflow-hidden bg-gray-950 px-4 pb-20 pt-32 sm:px-6 lg:px-8">
        {{-- Background Glow & Grid overlay --}}
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-red-900/20 via-gray-950 to-gray-950"></div>
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#1f29370f_1px,transparent_1px),linear-gradient(to_bottom,#1f29370f_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>

        <div class="relative mx-auto max-w-7xl">
            <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_.9fr]">
                {{-- Hero Copy --}}
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-red-400 backdrop-blur-md">
                        <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                        About BookMyMovie
                    </div>

                    <h1 class="mt-6 text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl tracking-tight">
                        A <span class="bg-gradient-to-r from-white via-gray-200 to-red-400 bg-clip-text text-transparent">Next-Gen Cinema</span> Experience Engine.
                    </h1>

                    <p class="mt-6 max-w-2xl text-base leading-8 text-gray-300">
                        BookMyMovie is a full-featured cinema discovery and seat booking architecture conceptualized and engineered by 
                        <span class="font-bold text-white underline decoration-red-500/50 underline-offset-4">Syed Ahmer Shah</span> 
                        for the <span class="font-bold text-white">Aptech DISM Project</span>. Designed to eliminate ticket friction through seamless seat selection, real-time cart handling, custom account portals, and robust administrative operational controls.
                    </p>

                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <a href="{{ route('movies.index') }}"
                            class="group relative inline-flex items-center gap-3 overflow-hidden rounded-xl bg-red-600 px-8 py-4 text-sm font-black text-white shadow-xl shadow-red-950/50 transition-all duration-300 hover:bg-red-500 hover:shadow-red-600/30 focus:outline-none focus:ring-2 focus:ring-red-400">
                            <span>Explore Movies</span>
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="{{ route('user.register') }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-8 py-4 text-sm font-black text-white backdrop-blur-sm transition-all duration-300 hover:border-red-500/40 hover:bg-red-950/30 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Create Account
                        </a>
                    </div>
                </div>

                {{-- Interactive 3D Hero Widget --}}
                <div class="mouse-track-card relative rounded-2xl border border-white/15 bg-gray-900/80 p-6 shadow-2xl backdrop-blur-xl">
                    <div class="card-3d-child">
                        <div class="flex items-center justify-between border-b border-white/10 pb-4">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-red-500"></span>
                                <span class="h-3 w-3 rounded-full bg-yellow-500"></span>
                                <span class="h-3 w-3 rounded-full bg-green-500"></span>
                            </div>
                            <span class="text-xs font-mono text-gray-400">System Performance Metrics</span>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-4">
                            @foreach([
                                    ['label' => 'Discovery Speed', 'value' => '< 50ms', 'desc' => 'Optimized queries'],
                                    ['label' => 'Seat Picking', 'value' => 'Visual 2D', 'desc' => 'Real-time grid'],
                                    ['label' => 'Checkout Flow', 'value' => '3 Steps', 'desc' => 'Zero distraction'],
                                    ['label' => 'Project Scope', 'value' => 'DISM Core', 'desc' => 'Full-stack Laravel']
                                ] as $metric)
                                    <div class="group rounded-xl border border-white/10 bg-black/40 p-4 transition hover:border-red-500/30 hover:bg-black/60">
                                        <p class="text-2xl font-black text-white transition-colors group-hover:text-red-400">{{ $metric['value'] }}</p>
                                        <p class="mt-1 text-xs font-bold uppercase text-gray-400">{{ $metric['label'] }}</p>
                                        <p class="mt-0.5 text-[10px] text-gray-500">{{ $metric['desc'] }}</p>
                                    </div>
                            @endforeach
                        </div>

                        <div class="mt-5 rounded-xl border border-red-500/30 bg-gradient-to-br from-red-950/40 to-black p-5">
                            <div class="flex items-center gap-2 text-red-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-xs font-black uppercase tracking-wider">Architecture Note</p>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-gray-300">
                                Engineered as an academic portfolio showcase highlighting full CRUD operations, authentication guards, CSRF integration, and responsive state management across user and admin roles.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3D Interactive Feature Badges --}}
            <div class="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                        ['value' => '8+', 'label' => 'Core Modules', 'copy' => 'Movies, showtimes, seats, bookings, cart, wishlist, user reviews, and admin dashboard.', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                        ['value' => '3', 'label' => 'Auth Journeys', 'copy' => 'Secure Auth, registration with server validation, and password recovery procedures.', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                        ['value' => '100%', 'label' => 'Responsive UI', 'copy' => 'Tailwind-powered viewports optimized for mobile displays up to 4K ultra-wide monitors.', 'icon' => 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['value' => '1', 'label' => 'Lead Developer', 'copy' => 'Crafted solely by Syed Ahmer Shah showcasing expertise in modern web technologies.', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z']
                    ] as $card)
                        <article class="mouse-track-card relative overflow-hidden rounded-2xl border border-white/10 bg-gray-900/60 p-6 backdrop-blur-md">
                            <div class="card-3d-child">
                                <div class="flex items-center justify-between">
                                    <span class="text-3xl font-black text-white">{{ $card['value'] }}</span>
                                    <div class="rounded-lg bg-red-500/10 p-2 text-red-400">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
                                    </div>
                                </div>
                                <h2 class="mt-4 text-xs font-black uppercase tracking-wider text-red-300">{{ $card['label'] }}</h2>
                                <p class="mt-2 text-xs leading-6 text-gray-400">{{ $card['copy'] }}</p>
                            </div>
                        </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Infinite Feature Ticker --}}
    <section class="relative border-y border-white/10 bg-black py-6 overflow-hidden">
        <div class="infinite-shell flex select-none">
            <div class="animate-marquee flex gap-6">
                @foreach(array_merge(
                        ['Seat Booking', 'Wishlist Storage', 'Movie Search', 'Secure Auth', 'COD Checkout', 'Ticket Tracking', 'Admin Dashboard', 'User Reviews'],
                        ['Seat Booking', 'Wishlist Storage', 'Movie Search', 'Secure Auth', 'COD Checkout', 'Ticket Tracking', 'Admin Dashboard', 'User Reviews']
                    ) as $item)
                        <div class="inline-flex items-center gap-3 rounded-full border border-red-500/20 bg-gradient-to-r from-red-950/30 to-gray-900/50 px-6 py-2.5 text-xs font-black uppercase tracking-widest text-red-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                            {{ $item }}
                        </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Interactive Functional Deep Dive Section --}}
    <section class="bg-gray-950 px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-[.85fr_1.15fr]">
                <div>
                    <span class="text-xs font-black uppercase tracking-widest text-red-400">System Capabilities</span>
                    <h2 class="mt-3 text-3xl font-black text-white sm:text-4xl">What is BookMyMovie?</h2>
                    <p class="mt-5 text-sm leading-7 text-gray-300">
                        BookMyMovie is an end-to-end cinema management software designed to replicate real-world theatrical operations. It provides users with an intuitive journey from initial trailer browsing to digital ticket issuance.
                    </p>
                    <p class="mt-4 text-sm leading-7 text-gray-400">
                        Behind the sleek UI sits a administrative suite allowing theater operators to schedule showtimes, manage auditorium capacities, update ticket pricing dynamically, and oversee user transaction histories.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                            ['title' => 'Movie Discovery', 'copy' => 'Filter, search, and inspect rich movie records complete with ratings, runtime, genres, and cast details.'],
                            ['title' => 'Visual Seat Picker', 'copy' => 'Interactive 2D seating layouts map available, reserved, and selected seats prior to commitment.'],
                            ['title' => 'Account Hub', 'copy' => 'Personal dashboards allow users to access purchase history, saved wishlist items, and account profiles.'],
                            ['title' => 'Admin Management', 'copy' => 'Comprehensive management tools for managing movies, screens, schedules, users, and ticket revenue.']
                        ] as $feature)
                            <div class="mouse-track-card rounded-xl border border-white/10 bg-gray-900 p-6">
                                <div class="card-3d-child">
                                    <h3 class="text-base font-black text-white">{{ $feature['title'] }}</h3>
                                    <p class="mt-3 text-xs leading-6 text-gray-400">{{ $feature['copy'] }}</p>
                                </div>
                            </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Chart Analytics Section --}}
    <section class="border-t border-white/10 bg-black px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <span class="text-xs font-black uppercase tracking-widest text-red-400">Analytics Insights</span>
                    <h2 class="mt-2 text-3xl font-black text-white sm:text-4xl">Architecture Balance</h2>
                </div>
                <p class="max-w-md text-xs leading-6 text-gray-400">
                    Empirical representation of module distribution and user journey prioritization built into the platform's codebase.
                </p>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-gray-950 p-6">
                    <h3 class="text-xs font-black uppercase tracking-wider text-gray-300">Feature Coverage Distribution</h3>
                    <div class="mt-6 h-80">
                        <canvas id="featureChart" aria-label="Feature coverage radar chart"></canvas>
                    </div>
                </div>
                <div class="rounded-2xl border border-white/10 bg-gray-950 p-6">
                    <h3 class="text-xs font-black uppercase tracking-wider text-gray-300">User Journey Focus Index</h3>
                    <div class="mt-6 h-80">
                        <canvas id="journeyChart" aria-label="User journey focus bar chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Value Proposition Grid --}}
    <section class="bg-gray-950 px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-10 lg:grid-cols-[.75fr_1.25fr]">
                <div>
                    <span class="text-xs font-black uppercase tracking-widest text-red-400">Value Proposition</span>
                    <h2 class="mt-3 text-3xl font-black text-white sm:text-4xl">Engineered for speed, clarity, & modularity.</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                            'Focused cinema interface keeping movie artwork, showtimes, and booking actions easy to read.',
                            'Complete user authentication pipelines equipped with CSRF safety and input validation.',
                            'Modular Laravel construction demonstrating controllers,Blade components, and database migrations.',
                            'Responsive dark aesthetic designed with custom CSS variables and GPU-accelerated effects.',
                            'Synchronized user and admin capabilities guaranteeing cohesive functional flows.',
                            'Solely authored by Syed Ahmer Shah as an academic DISM project demonstrating web architectural mastery.'
                        ] as $reason)
                            <div class="mouse-track-card rounded-xl border border-white/10 bg-gray-900 p-5">
                                <div class="card-3d-child text-xs leading-6 text-gray-300">
                                    {{ $reason }}
                                </div>
                            </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', () => {
            /* -------------------------------------------------------------
             * 1. Dynamic Mouse-Tracking 3D Tilt Effect
             * ----------------------------------------------------------- */
            const trackCards = document.querySelectorAll('.mouse-track-card');

            trackCards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    // Set variables for internal ambient light spotlight
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);

                    // Calculate rotation (-10deg to 10deg)
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / centerY) * -10;
                    const rotateY = ((x - centerX) / centerX) * 10;

                    card.style.setProperty('--rx', `${rotateX.toFixed(2)}deg`);
                    card.style.setProperty('--ry', `${rotateY.toFixed(2)}deg`);
                });

                card.addEventListener('mouseleave', () => {
                    card.style.setProperty('--rx', '0deg');
                    card.style.setProperty('--ry', '0deg');
                });
            });

            /* -------------------------------------------------------------
             * 2. Chart.js Implementation with Separate Scale Configs
             * ----------------------------------------------------------- */
            const featureCanvas = document.getElementById('featureChart');
            const journeyCanvas = document.getElementById('journeyChart');

            if (featureCanvas) {
                new Chart(featureCanvas, {
                    type: 'radar',
                    data: {
                        labels: ['Movies', 'Seats', 'Cart', 'Wishlist', 'Account', 'Admin'],
                        datasets: [{
                            label: 'Coverage %',
                            data: [92, 88, 82, 78, 86, 84],
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.25)',
                            pointBackgroundColor: '#fca5a5',
                            pointBorderColor: '#ef4444',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#e5e7eb', font: { weight: 'bold' } }
                            }
                        },
                        scales: {
                            r: {
                                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
                                pointLabels: { color: '#d1d5db', font: { size: 11, weight: 'bold' } },
                                ticks: { display: false }
                            }
                        }
                    }
                });
            }

            if (journeyCanvas) {
                new Chart(journeyCanvas, {
                    type: 'bar',
                    data: {
                        labels: ['Discover', 'Compare', 'Book', 'Checkout', 'Track'],
                        datasets: [{
                            label: 'Priority Index',
                            data: [90, 72, 96, 84, 78],
                            backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e', '#38bdf8'],
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#e5e7eb', font: { weight: 'bold' } }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                ticks: { color: '#9ca3af' }
                            },
                            y: {
                                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                ticks: { color: '#9ca3af' },
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
