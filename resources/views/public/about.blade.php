@extends('layouts.app')

@section('title', 'About | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-32 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid items-center gap-10 lg:grid-cols-[1fr_.85fr]">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">About BookMyMovie</p>
                    <h1 class="mt-4 max-w-4xl text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">
                        A premium movie booking experience created for fast, clear, and confident cinema planning.
                    </h1>
                    <p class="mt-6 max-w-3xl text-base leading-8 text-gray-300">
                        BookMyMovie is a cinema discovery and ticket booking platform created by
                        <span class="font-black text-white">Syed Ahmer Shah</span> for the
                        <span class="font-black text-white">Aptech DISM Project</span> solely. The project focuses on
                        practical full-stack web development: public movie discovery, seat selection, account workflows,
                        carts, wishlists, checkout, bookings, reviews, and admin management in one responsive Laravel app.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('movies.index') }}"
                            class="rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-red-950/40 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Explore movies
                        </a>
                        <a href="{{ route('user.register') }}"
                            class="rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-black text-white transition hover:border-red-400 hover:bg-red-950/30 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Create account
                        </a>
                    </div>
                </div>

                <div class="premium-tilt rounded-lg border border-white/10 bg-gray-900/80 p-6">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach([
                            ['label' => 'Movie discovery', 'value' => 'Fast'],
                            ['label' => 'Seat selection', 'value' => 'Visual'],
                            ['label' => 'Checkout flow', 'value' => 'Simple'],
                            ['label' => 'Project scope', 'value' => 'DISM'],
                        ] as $metric)
                            <div class="rounded-lg border border-white/10 bg-black/30 p-5">
                                <p class="text-2xl font-black text-white">{{ $metric['value'] }}</p>
                                <p class="mt-1 text-xs font-bold uppercase text-gray-500">{{ $metric['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 rounded-lg border border-red-500/20 bg-red-950/20 p-5">
                        <p class="text-sm font-black uppercase text-red-200">Project note</p>
                        <p class="mt-2 text-sm leading-6 text-gray-300">
                            Built as an academic portfolio project, BookMyMovie demonstrates how a cinema product can
                            connect users, movie listings, booking logic, account tools, and admin operations.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['value' => '8+', 'label' => 'Core modules', 'copy' => 'Movies, shows, seats, bookings, carts, wishlist, reviews, admin.'],
                    ['value' => '3', 'label' => 'Account flows', 'copy' => 'Login, signup, and password reset with CSRF protection.'],
                    ['value' => '100%', 'label' => 'Responsive UI', 'copy' => 'Designed for mobile, tablet, laptop, and desktop screens.'],
                    ['value' => '1', 'label' => 'Creator', 'copy' => 'Created solely by Syed Ahmer Shah for Aptech DISM.'],
                ] as $card)
                    <article class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-5">
                        <p class="text-3xl font-black text-white">{{ $card['value'] }}</p>
                        <h2 class="mt-3 text-sm font-black uppercase text-red-200">{{ $card['label'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-400">{{ $card['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-y border-white/10 bg-black py-5">
        <div class="infinite-shell overflow-hidden">
            <div class="infinite-track flex gap-4 px-4">
                @foreach(array_merge(
                    ['Seat booking', 'Wishlist', 'Movie search', 'Secure auth', 'COD checkout', 'Booking tracking', 'Admin panel', 'Reviews'],
                    ['Seat booking', 'Wishlist', 'Movie search', 'Secure auth', 'COD checkout', 'Booking tracking', 'Admin panel', 'Reviews']
                ) as $item)
                    <span
                        class="inline-flex min-w-48 items-center justify-center rounded-full border border-red-500/20 bg-red-950/20 px-6 py-3 text-sm font-black uppercase text-red-100">
                        {{ $item }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-gray-950 px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[.85fr_1.15fr]">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">What it does</p>
                    <h2 class="mt-3 text-3xl font-black text-white">What is BookMyMovie?</h2>
                    <p class="mt-5 text-sm leading-7 text-gray-300">
                        BookMyMovie is a web-based movie ticket booking system. It helps a visitor browse available
                        movies, inspect details, compare options, choose seats, keep favorite movies in a wishlist, add
                        tickets to cart, checkout, and track bookings from a personal account dashboard.
                    </p>
                    <p class="mt-4 text-sm leading-7 text-gray-300">
                        The app is also built to show admin-side thinking. A cinema platform needs content control,
                        booking oversight, user management, payment records, contact messages, coupons, FAQs, theaters,
                        screens, seats, and show timing data. BookMyMovie organizes those pieces into a project that
                        feels like a real online cinema workflow.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['title' => 'Movie discovery', 'copy' => 'Browse movies with posters, genres, ratings, and detail pages that help users choose quickly.'],
                        ['title' => 'Seat selection', 'copy' => 'Visual seat picking helps users understand availability and ticket categories before checkout.'],
                        ['title' => 'Account dashboard', 'copy' => 'Users can review bookings, wishlist items, cart activity, and profile details from one place.'],
                        ['title' => 'Admin control', 'copy' => 'Admin pages support management of content, bookings, users, theaters, shows, and business data.'],
                    ] as $feature)
                        <article class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-5">
                            <h3 class="text-lg font-black text-white">{{ $feature['title'] }}</h3>
                            <p class="mt-3 text-sm leading-6 text-gray-400">{{ $feature['copy'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-black px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">Project analytics</p>
                    <h2 class="mt-3 text-3xl font-black text-white">How the product experience is balanced</h2>
                </div>
                <p class="max-w-xl text-sm leading-6 text-gray-400">
                    These charts communicate the project priorities: useful customer journeys, clean admin coverage, and
                    a booking flow that is easy to understand.
                </p>
            </div>

            <div class="mt-8 grid gap-5 lg:grid-cols-2">
                <div class="rounded-lg border border-white/10 bg-gray-950 p-5">
                    <h3 class="text-sm font-black uppercase text-gray-300">Feature coverage</h3>
                    <div class="mt-5 h-72">
                        <canvas id="featureChart" aria-label="Feature coverage chart"></canvas>
                    </div>
                </div>
                <div class="rounded-lg border border-white/10 bg-gray-950 p-5">
                    <h3 class="text-sm font-black uppercase text-gray-300">User journey focus</h3>
                    <div class="mt-5 h-72">
                        <canvas id="journeyChart" aria-label="User journey focus chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-gray-950 px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[.75fr_1.25fr]">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">Why choose us</p>
                    <h2 class="mt-3 text-3xl font-black text-white">Built for clarity, speed, and real project value.</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                        'Clean cinema-focused UI that keeps movie posters, show details, and booking actions easy to scan.',
                        'Dedicated user flows for login, signup, forgot password, account, cart, wishlist, and checkout.',
                        'A practical Laravel structure that demonstrates routing, controllers, models, Blade components, and validation.',
                        'Responsive dark premium design with CSRF-protected forms and clear user feedback states.',
                        'Admin and public sides designed together so the project feels complete rather than only decorative.',
                        'Created solely by Syed Ahmer Shah as an Aptech DISM Project to demonstrate applied web development skills.',
                    ] as $reason)
                        <div class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-5 text-sm leading-7 text-gray-300">
                            {{ $reason }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#e5e7eb',
                            font: {
                                weight: '700',
                            },
                        },
                    },
                },
                scales: {
                    r: {
                        grid: { color: 'rgba(255,255,255,.12)' },
                        angleLines: { color: 'rgba(255,255,255,.12)' },
                        pointLabels: { color: '#d1d5db', font: { weight: '700' } },
                        ticks: { display: false },
                    },
                    x: {
                        grid: { color: 'rgba(255,255,255,.06)' },
                        ticks: { color: '#9ca3af' },
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,.08)' },
                        ticks: { color: '#9ca3af' },
                    },
                },
            };

            const featureCanvas = document.getElementById('featureChart');
            const journeyCanvas = document.getElementById('journeyChart');

            if (featureCanvas) {
                new Chart(featureCanvas, {
                    type: 'radar',
                    data: {
                        labels: ['Movies', 'Seats', 'Cart', 'Wishlist', 'Account', 'Admin'],
                        datasets: [{
                            label: 'Coverage',
                            data: [92, 88, 82, 78, 86, 84],
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(220, 38, 38, .22)',
                            pointBackgroundColor: '#fca5a5',
                        }],
                    },
                    options: baseOptions,
                });
            }

            if (journeyCanvas) {
                new Chart(journeyCanvas, {
                    type: 'bar',
                    data: {
                        labels: ['Discover', 'Compare', 'Book', 'Checkout', 'Track'],
                        datasets: [{
                            label: 'Journey focus',
                            data: [90, 72, 96, 84, 78],
                            backgroundColor: ['#ef4444', '#f97316', '#d4af37', '#22c55e', '#38bdf8'],
                            borderRadius: 8,
                        }],
                    },
                    options: {
                        ...baseOptions,
                        scales: {
                            x: baseOptions.scales.x,
                            y: {
                                ...baseOptions.scales.y,
                                beginAtZero: true,
                                max: 100,
                            },
                        },
                    },
                });
            }
        });
    </script>
@endpush
