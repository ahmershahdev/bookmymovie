@extends('layouts.app')

@section('title', 'Terms of Service | BookMyMovie')

@section('content')
    <section class="relative min-h-screen overflow-hidden bg-gray-950 px-4 pb-20 pt-32 sm:px-6 lg:px-8 text-gray-100">
        <!-- Ambient 3D Background Lighting Blobs -->
        <div class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-[600px] w-[600px] -translate-x-1/2 rounded-full bg-red-600/15 blur-[140px]"></div>
        <div class="pointer-events-none absolute top-1/3 -left-40 -z-10 h-[500px] w-[500px] rounded-full bg-amber-500/10 blur-[140px]"></div>
        <div class="pointer-events-none absolute bottom-10 right-0 -z-10 h-[500px] w-[500px] rounded-full bg-red-900/15 blur-[140px]"></div>

        <div class="mx-auto max-w-7xl">
            <!-- Header Grid Section -->
            <div class="grid gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
                <!-- Hero Left Column -->
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3.5 py-1.5 backdrop-blur-md shadow-lg shadow-amber-500/5">
                        <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-amber-400">Legal Center</p>
                    </div>

                    <h1 class="text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl lg:leading-tight">
                        Terms of <span class="bg-gradient-to-r from-red-500 via-amber-400 to-red-400 bg-clip-text text-transparent">Service</span>
                    </h1>

                    <p class="text-base leading-relaxed text-gray-300 sm:text-lg">
                        These terms describe responsible use of BookMyMovie, including account access, seat selection,
                        COD checkout, ticket bookings, community reviews, support messages, and administrative governance.
                    </p>

                    <!-- Effective Date Card with 3D Depth -->
                    <div class="tilt-card relative overflow-hidden rounded-2xl border border-red-500/30 bg-gradient-to-br from-red-950/40 via-gray-900/60 to-gray-950/80 p-6 backdrop-blur-xl shadow-2xl shadow-red-950/30">
                        <div class="tilt-card-glare"></div>
                        <div class="tilt-card-content flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 shadow-inner">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-red-300/80">Agreement Scope</p>
                                <p class="text-xl font-black text-white">User & Platform Governance</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3D Feature Principles Grid -->
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                            [
                                'title' => 'Use real details',
                                'copy' => 'Bookings and support messages require a reachable email and accurate customer information.',
                                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
                            ],
                            [
                                'title' => 'Respect seat holds',
                                'copy' => 'Seat selections may expire or release automatically until checkout is completely finalized.',
                                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
                            ],
                            [
                                'title' => 'Follow theater rules',
                                'copy' => 'Counter collection, entry timing, age ratings, and cancellations depend on the selected cinema.',
                                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
                            ],
                            [
                                'title' => 'Keep accounts secure',
                                'copy' => 'Users are strictly responsible for maintaining credential secrecy and monitoring account activity.',
                                'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'
                            ]
                        ] as $card)
                            <article class="tilt-card group relative overflow-hidden rounded-2xl border border-white/10 bg-gray-900/60 p-6 backdrop-blur-xl shadow-xl transition-all duration-300 hover:border-red-500/40 hover:shadow-2xl hover:shadow-red-950/40">
                                <div class="tilt-card-glare"></div>
                                <div class="tilt-card-content space-y-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-amber-400 group-hover:border-red-500/40 group-hover:bg-red-500/10 group-hover:text-red-400 transition-colors">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                                        </svg>
                                    </div>
                                    <h2 class="text-lg font-bold text-white group-hover:text-amber-400 transition-colors">{{ $card['title'] }}</h2>
                                    <p class="text-sm leading-relaxed text-gray-400">{{ $card['copy'] }}</p>
                                </div>
                            </article>
                    @endforeach
                </div>
            </div>

            <!-- Sleek Glassmorphism Conduct Matrix Table -->
            <div class="mt-16 overflow-hidden rounded-2xl border border-white/10 bg-gray-900/40 backdrop-blur-xl shadow-2xl shadow-black/80">
                <div class="border-b border-white/10 bg-gray-950/60 px-6 py-4 flex items-center justify-between">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        Acceptable Conduct Matrix
                    </h2>
                    <span class="text-xs text-gray-400 font-mono">Platform Integrity Policy</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[780px] text-left text-sm text-gray-300">
                        <thead class="border-b border-white/10 bg-gray-950/80 text-xs font-bold uppercase tracking-widest text-red-400">
                            <tr>
                                <th class="px-6 py-4">Area</th>
                                <th class="px-6 py-4">Allowed Use</th>
                                <th class="px-6 py-4">Prohibited Use</th>
                                <th class="px-6 py-4">Why It Matters</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach([
                                    [
                                        'area' => 'Accounts',
                                        'allowed' => 'Register with accurate personal details',
                                        'prohibited' => 'Fake identities or disposable email domains',
                                        'impact' => 'Keeps support, recovery, and ticket resets reliable'
                                    ],
                                    [
                                        'area' => 'Bookings',
                                        'allowed' => 'Reserve seats for real cinema visits',
                                        'prohibited' => 'Automated hoarding or holding seats without intent',
                                        'impact' => 'Ensures fair seat availability for all patrons'
                                    ],
                                    [
                                        'area' => 'Reviews',
                                        'allowed' => 'Share genuine, constructive movie feedback',
                                        'prohibited' => 'Spam, toxic abuse, spoilers, or misleading ratings',
                                        'impact' => 'Maintains clean, trustworthy community reviews'
                                    ],
                                    [
                                        'area' => 'Contact Form',
                                        'allowed' => 'Ask concise, relevant support questions',
                                        'prohibited' => 'Harassment, spam, bot scripts, or promotional links',
                                        'impact' => 'Protects support response speed and ticket quality'
                                    ],
                                    [
                                        'area' => 'Admin Data',
                                        'allowed' => 'Authorized administrative operations only',
                                        'prohibited' => 'Unauthorized scraping, endpoint tampering, or exploitation',
                                        'impact' => 'Secures user records and overall database stability'
                                    ]
                                ] as $row)
                                    <tr class="group transition-colors duration-200 hover:bg-white/[0.04]">
                                        <td class="px-6 py-4 font-bold text-white group-hover:text-amber-400 transition-colors">
                                            <span class="inline-block rounded-md border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-mono">
                                                {{ $row['area'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-emerald-300/90 flex items-center gap-2">
                                            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>{{ $row['allowed'] }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-red-300/90">
                                            <div class="flex items-center gap-2">
                                                <svg class="h-4 w-4 shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span>{{ $row['prohibited'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-400 text-xs">
                                            {{ $row['impact'] }}
                                        </td>
                                    </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Policy Columns -->
            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                @foreach([
                        [
                            'title' => 'Booking Terms',
                            'badge' => 'Reservations',
                            'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
                            'items' => [
                                'A booking is created only after checkout is completed.',
                                'Seat availability can change or expire before checkout.',
                                'COD collection follows official cinema counter rules.',
                                'Booking reference numbers must be retained for support.'
                            ]
                        ],
                        [
                            'title' => 'Account Terms',
                            'badge' => 'Membership',
                            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                            'items' => [
                                'You must register using an accessible email address.',
                                'You are solely responsible for all account actions.',
                                'Blocked accounts lose access to booking tools immediately.',
                                'Password resets require access to the registered email.'
                            ]
                        ],
                        [
                            'title' => 'Project Scope',
                            'badge' => 'Framework',
                            'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                            'items' => [
                                'BookMyMovie is an academic Laravel cinema project.',
                                'Policy text supports project demonstration and UX depth.',
                                'Final legal review is recommended before commercial production.',
                                'Feature availability relies on system configuration.'
                            ]
                        ]
                    ] as $group)
                        <article class="tilt-card group relative overflow-hidden rounded-2xl border border-white/10 bg-gray-900/60 p-6 backdrop-blur-xl shadow-xl transition-all duration-300 hover:border-red-500/40 hover:shadow-2xl">
                            <div class="tilt-card-glare"></div>
                            <div class="tilt-card-content space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-amber-400 group-hover:border-red-500/40 group-hover:bg-red-500/10 group-hover:text-red-400 transition-colors">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $group['icon'] }}" />
                                            </svg>
                                        </div>
                                        <h2 class="text-xl font-bold text-white group-hover:text-amber-400 transition-colors">{{ $group['title'] }}</h2>
                                    </div>
                                    <span class="rounded-full border border-red-500/30 bg-red-500/10 px-2.5 py-0.5 text-xs font-semibold text-red-300">
                                        {{ $group['badge'] }}
                                    </span>
                                </div>

                                <ul class="space-y-2.5 text-sm leading-relaxed text-gray-300">
                                    @foreach($group['items'] as $item)
                                        <li class="flex items-start gap-3 rounded-xl border border-white/5 bg-white/[0.02] p-3 transition-colors hover:border-white/15 hover:bg-white/[0.05]">
                                            <svg class="mt-1 h-4 w-4 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- 3D Cursor Tracking Script & Dynamic CSS Styles -->
    <style nonce="{{ $cspNonce ?? '' }}">
        .tilt-card {
            transform-style: preserve-3d;
            perspective: 1000px;
            will-change: transform;
            transition: transform 0.15s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .tilt-card-content {
            transform: translateZ(24px);
        }

        .tilt-card-glare {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(
                600px circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
                rgba(255, 255, 255, 0.08),
                transparent 40%
            );
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .tilt-card:hover .tilt-card-glare {
            opacity: 1;
        }
    </style>

    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.tilt-card');

            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;

                    // Calculate rotation angles (max ~12 deg for tactile 3D movement)
                    const rotateX = ((y - centerY) / centerY) * -12;
                    const rotateY = ((x - centerX) / centerX) * 12;

                    card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) scale3d(1.02, 1.02, 1.02)`;
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
                });
            });
        });
    </script>
@endsection
