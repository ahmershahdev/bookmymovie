@extends('layouts.app')

@section('title', 'Refund Policy | BookMyMovie')

@section('content')
    <section class="relative min-h-screen overflow-hidden bg-gray-950 px-4 pb-20 pt-32 sm:px-6 lg:px-8 text-gray-100">
        <!-- Ambient 3D Background Lighting Blobs -->
        <div class="pointer-events-none absolute -top-40 left-1/2 -z-10 h-[600px] w-[600px] -translate-x-1/2 rounded-full bg-red-600/15 blur-[140px]"></div>
        <div class="pointer-events-none absolute top-1/3 -right-40 -z-10 h-[500px] w-[500px] rounded-full bg-amber-500/10 blur-[140px]"></div>
        <div class="pointer-events-none absolute bottom-10 -left-20 -z-10 h-[500px] w-[500px] rounded-full bg-red-900/15 blur-[140px]"></div>

        <div class="mx-auto max-w-7xl">
            <!-- Header Grid Section -->
            <div class="grid gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
                <!-- Hero Left Column -->
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3.5 py-1.5 backdrop-blur-md shadow-lg shadow-amber-500/5">
                        <span class="h-2 w-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-amber-400">Policy Center</p>
                    </div>

                    <h1 class="text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl lg:leading-tight">
                        Refund & <span class="bg-gradient-to-r from-red-500 via-amber-400 to-red-400 bg-clip-text text-transparent">Cancellation</span>
                    </h1>

                    <p class="text-base leading-relaxed text-gray-300 sm:text-lg">
                        BookMyMovie supports clear cancellation guidance for COD bookings, expired carts, unavailable
                        seats, duplicate submissions, and theater-side schedule changes.
                    </p>
                </div>

                <!-- 3D Stat Highlight Cards Grid -->
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach([
                            [
                                'value' => 'COD',
                                'label' => 'Primary Payment Mode',
                                'sub' => 'Pay at counter option',
                                'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'
                            ],
                            [
                                'value' => 'Before Cutoff',
                                'label' => 'Cancellation Window',
                                'sub' => 'Prior to show start',
                                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
                            ],
                            [
                                'value' => 'Booking #',
                                'label' => 'Support Reference',
                                'sub' => 'Required for reviews',
                                'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'
                            ]
                        ] as $metric)
                            <article class="tilt-card group relative overflow-hidden rounded-2xl border border-white/10 bg-gray-900/60 p-6 backdrop-blur-xl shadow-xl transition-all duration-300 hover:border-red-500/40 hover:shadow-2xl hover:shadow-red-950/40">
                                <div class="tilt-card-glare"></div>
                                <div class="tilt-card-content flex flex-col justify-between h-full space-y-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 group-hover:scale-110 transition-transform">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $metric['icon'] }}" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-black text-white group-hover:text-amber-400 transition-colors">{{ $metric['value'] }}</p>
                                        <p class="mt-1 text-xs font-bold uppercase tracking-wider text-red-300/90">{{ $metric['label'] }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $metric['sub'] }}</p>
                                    </div>
                                </div>
                            </article>
                    @endforeach
                </div>
            </div>

            <!-- Sleek Interactive Scenario Table -->
            <div class="mt-16 overflow-hidden rounded-2xl border border-white/10 bg-gray-900/40 backdrop-blur-xl shadow-2xl shadow-black/80">
                <div class="border-b border-white/10 bg-gray-950/60 px-6 py-4 flex items-center justify-between">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        Refund & Cancellation Matrix
                    </h2>
                    <span class="text-xs text-gray-400 font-mono">Case-by-Case Policy Guidelines</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[780px] text-left text-sm text-gray-300">
                        <thead class="border-b border-white/10 bg-gray-950/80 text-xs font-bold uppercase tracking-widest text-red-400">
                            <tr>
                                <th class="px-6 py-4">Scenario</th>
                                <th class="px-6 py-4">Eligibility</th>
                                <th class="px-6 py-4">Customer Action</th>
                                <th class="px-6 py-4">Expected Result</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach([
                                    [
                                        'scenario' => 'Cancel before show cutoff',
                                        'eligibility' => 'Conditional',
                                        'badge' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
                                        'eligibility_text' => 'May be accepted based on theater rules',
                                        'action' => 'Send booking number and show time',
                                        'result' => 'Booking can be cancelled by support/admin'
                                    ],
                                    [
                                        'scenario' => 'Show already started',
                                        'eligibility' => 'Ineligible',
                                        'badge' => 'border-red-500/30 bg-red-500/10 text-red-300',
                                        'eligibility_text' => 'Usually not eligible',
                                        'action' => 'Contact cinema counter for exception',
                                        'result' => 'Refund may be declined'
                                    ],
                                    [
                                        'scenario' => 'Expired cart',
                                        'eligibility' => 'Automatic',
                                        'badge' => 'border-gray-500/30 bg-gray-500/10 text-gray-300',
                                        'eligibility_text' => 'No refund needed',
                                        'action' => 'Start a fresh seat selection',
                                        'result' => 'Seats become available again'
                                    ],
                                    [
                                        'scenario' => 'Duplicate booking request',
                                        'eligibility' => 'Under Review',
                                        'badge' => 'border-purple-500/30 bg-purple-500/10 text-purple-300',
                                        'eligibility_text' => 'Reviewed case by case',
                                        'action' => 'Share both booking numbers',
                                        'result' => 'One request may be cancelled if valid'
                                    ],
                                    [
                                        'scenario' => 'Theater schedule change',
                                        'eligibility' => 'Eligible',
                                        'badge' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                                        'eligibility_text' => 'Usually eligible for help',
                                        'action' => 'Mention city, movie, and show',
                                        'result' => 'Support can advise reschedule or cancellation'
                                    ]
                                ] as $row)
                                    <tr class="group transition-colors duration-200 hover:bg-white/[0.04]">
                                        <td class="px-6 py-4 font-bold text-white group-hover:text-amber-400 transition-colors">
                                            {{ $row['scenario'] }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <span class="inline-block rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $row['badge'] }}">
                                                    {{ $row['eligibility'] }}
                                                </span>
                                                <p class="text-xs text-gray-400">{{ $row['eligibility_text'] }}</p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-300 font-mono text-xs">
                                            {{ $row['action'] }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-400">
                                            {{ $row['result'] }}
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
                            'title' => 'Before requesting',
                            'badge' => 'Checklist',
                            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                            'items' => [
                                'Check the show date and time.',
                                'Confirm your city and theater.',
                                'Keep your booking number ready.',
                                'Read any cinema counter instructions.'
                            ]
                        ],
                        [
                            'title' => 'Not usually refundable',
                            'badge' => 'Exclusions',
                            'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                            'items' => [
                                'Missed shows after start time.',
                                'Wrong email entered by the customer.',
                                'Requests without a booking reference.',
                                'Policy exceptions denied by theater rules.'
                            ]
                        ],
                        [
                            'title' => 'Support review',
                            'badge' => 'Workflow',
                            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                            'items' => [
                                'Support checks booking status.',
                                'Admin may confirm seat and show records.',
                                'Eligible requests are marked for cancellation.',
                                'Customer receives the final support response.'
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

                    // Calculate rotation angles (max ~12 deg for a tactile 3D feel)
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
