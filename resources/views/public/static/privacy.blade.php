@extends('layouts.app')

@section('title', 'Privacy Policy | BookMyMovie')

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

                    <h1 class="text-4xl font-black tracking-tight text-white sm:text-6xl lg:leading-tight">
                        Privacy <span class="bg-gradient-to-r from-red-500 via-amber-400 to-red-400 bg-clip-text text-transparent">Policy</span>
                    </h1>

                    <p class="text-base leading-relaxed text-gray-300 sm:text-lg">
                        This policy explains how BookMyMovie handles account, booking, contact, wishlist, review, and
                        checkout information inside this Laravel cinema booking project.
                    </p>

                    <!-- Last Updated Card with 3D Depth -->
                    <div class="tilt-card relative overflow-hidden rounded-2xl border border-red-500/30 bg-gradient-to-br from-red-950/40 via-gray-900/60 to-gray-950/80 p-6 backdrop-blur-xl shadow-2xl shadow-red-950/30">
                        <div class="tilt-card-glare"></div>
                        <div class="tilt-card-content flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-red-500/30 bg-red-500/10 text-red-400 shadow-inner">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-red-300/80">Last Updated</p>
                                <p class="text-2xl font-black text-white">July 24, 2026</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3D Feature Cards Grid -->
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                            ['title' => 'Data minimization', 'copy' => 'Forms collect only the details needed for accounts, booking, and support.', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                            ['title' => 'No disposable emails', 'copy' => 'Temporary email domains are blocked to keep resets and support replies reliable.', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                            ['title' => 'Captcha checks', 'copy' => 'Google reCAPTCHA v2 and v3 can protect public forms when keys are configured.', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                            ['title' => 'User control', 'copy' => 'Users can manage profile, wishlist, cart, and booking activity from the account area.', 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4']
                        ] as $card)
                            <article class="tilt-card group relative overflow-hidden rounded-2xl border border-white/10 bg-gray-900/60 p-6 backdrop-blur-xl shadow-xl transition-all duration-300 hover:border-red-500/40 hover:shadow-2xl hover:shadow-red-950/40">
                                <div class="tilt-card-glare"></div>
                                <div class="tilt-card-content space-y-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-amber-400 group-hover:border-red-500/40 group-hover:bg-red-500/10 group-hover:text-red-400 transition-colors">
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

            <!-- Sleek Glassmorphism Table -->
            <div class="mt-16 overflow-hidden rounded-2xl border border-white/10 bg-gray-900/40 backdrop-blur-xl shadow-2xl shadow-black/80">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[780px] text-left text-sm text-gray-300">
                        <thead class="border-b border-white/10 bg-gray-950/80 text-xs font-bold uppercase tracking-widest text-red-400">
                            <tr>
                                <th class="px-6 py-4">Data Type</th>
                                <th class="px-6 py-4">Purpose</th>
                                <th class="px-6 py-4">Example Fields</th>
                                <th class="px-6 py-4">Retention Approach</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach([
                                    ['Account', 'Login, dashboard, booking history', 'Name, email, phone, password hash', 'Kept while the account exists'],
                                    ['Booking', 'Seat reservation and ticket tracking', 'Movie, show, seats, booking number', 'Kept for records and project reports'],
                                    ['Support', 'Answer contact form messages', 'Name, email, message body', 'Kept until resolved or cleaned by admin'],
                                    ['Security', 'Spam and abuse prevention', 'Captcha token result, IP during verification', 'Used at submission time']
                                ] as $row)
                                    <tr class="group transition-colors duration-200 hover:bg-white/[0.04]">
                                        <td class="px-6 py-4 font-bold text-white group-hover:text-amber-400 transition-colors">
                                            <span class="inline-block rounded-md border border-white/10 bg-white/5 px-2.5 py-1 text-xs">
                                                {{ $row[0] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">{{ $row[1] }}</td>
                                        <td class="px-6 py-4 text-gray-400 font-mono text-xs">{{ $row[2] }}</td>
                                        <td class="px-6 py-4 text-gray-400">{{ $row[3] }}</td>
                                    </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Policy Columns -->
            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                @foreach([
                        ['title' => 'How information is used', 'badge' => 'Usage', 'items' => ['Create and authenticate user accounts.', 'Maintain carts, wishlists, reviews, and bookings.', 'Send support responses and reset-password links.', 'Improve project usability and admin workflows.']],
                        ['title' => 'How information is protected', 'badge' => 'Security', 'items' => ['Passwords are stored using Laravel hashing.', 'Forms use CSRF protection and validation rules.', 'Captcha can be enabled through environment keys.', 'Admin workflows separate operational records from public pages.']],
                        ['title' => 'Your responsibilities', 'badge' => 'Guidelines', 'items' => ['Use a real email address you can access.', 'Keep your password private and unique.', 'Do not submit fake, abusive, or misleading contact messages.', 'Contact support with enough booking detail to verify requests.']]
                    ] as $group)
                        <article class="tilt-card group relative overflow-hidden rounded-2xl border border-white/10 bg-gray-900/60 p-6 backdrop-blur-xl shadow-xl transition-all duration-300 hover:border-red-500/40 hover:shadow-2xl">
                            <div class="tilt-card-glare"></div>
                            <div class="tilt-card-content space-y-4">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-bold text-white group-hover:text-amber-400 transition-colors">{{ $group['title'] }}</h2>
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

                    // Calculate rotation angles (max ~12 deg for a subtle tactile 3D feel)
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
