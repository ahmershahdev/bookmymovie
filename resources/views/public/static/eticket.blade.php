@extends('layouts.app')

@section('title', 'E-Ticket Info | BookMyMovie')

@section('content')
    <style nonce="{{ $cspNonce ?? '' }}">
        /* 3D Perspective Container */
        .perspective-1000 {
            perspective: 1000px;
        }

        /* 3D Real-Time Cursor Spotlight Card */
        .card-3d {
            position: relative;
            transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1), border-color 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
            will-change: transform;
        }

        .card-3d::before {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            background: radial-gradient(500px circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
                    rgba(239, 68, 68, 0.22),
                    rgba(245, 158, 11, 0.06) 40%,
                    transparent 80%);
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
            z-index: 1;
        }

        .card-3d:hover::before {
            opacity: 1;
        }

        .card-3d-content {
            transform: translateZ(20px);
            transform-style: preserve-3d;
        }

        /* Scroll Reveal Animations */
        .reveal-scroll {
            opacity: 0;
            transform: translateY(30px) scale(0.98);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }

        .reveal-scroll.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        /* Static Mesh Grid Overlay */
        .bg-mesh-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>

    <!-- Premium Static Cinema Background -->
    <div class="relative min-h-screen bg-gray-950 text-white overflow-hidden selection:bg-red-600 selection:text-white">
        <!-- Static Vignette & Ambient Radial Lighting -->
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-red-950/20 via-gray-950 to-black"></div>
        <div class="pointer-events-none absolute inset-0 bg-mesh-pattern opacity-40"></div>
        <div class="pointer-events-none absolute -top-40 right-1/4 h-[500px] w-[500px] rounded-full bg-red-600/10 blur-[150px]"></div>
        <div class="pointer-events-none absolute top-1/2 -left-40 h-[600px] w-[600px] rounded-full bg-amber-500/5 blur-[170px]"></div>

        <section class="relative z-10 px-4 pb-20 pt-32 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">

                <!-- Hero Grid Section -->
                <div class="grid gap-12 lg:grid-cols-12 lg:items-center">
                    <!-- Left Intro Copy -->
                    <div class="lg:col-span-7 reveal-scroll">
                        <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-4 py-1.5 backdrop-blur-md">
                            <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <p class="text-xs font-black uppercase tracking-[0.25em] text-amber-400">Official Ticket Guide</p>
                        </div>

                        <h1 class="mt-6 text-4xl font-black leading-[1.1] tracking-tight text-white sm:text-6xl">
                            Seamless <span class="bg-gradient-to-r from-red-500 via-amber-400 to-red-400 bg-clip-text text-transparent">E-Ticket</span> Access & Delivery
                        </h1>

                        <p class="mt-6 text-base leading-8 text-gray-300 sm:text-lg">
                            BookMyMovie keeps your cinema journey effortless: complete checkout, store your digital booking details, and collect your physical ticket counter-side with zero hassle.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-4">
                            <a href="{{ route('user.bookings') }}"
                                class="group relative inline-flex items-center justify-center overflow-hidden rounded-full bg-gradient-to-r from-red-600 to-red-700 px-8 py-4 text-sm font-black text-white shadow-xl shadow-red-950/60 transition-all duration-300 hover:scale-105 hover:from-red-500 hover:to-red-600 focus:outline-none focus:ring-2 focus:ring-red-400">
                                <span class="relative z-10 flex items-center gap-2">
                                    View Bookings
                                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </span>
                            </a>

                            <a href="{{ route('faq') }}"
                                class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-8 py-4 text-sm font-black text-white backdrop-blur-md transition-all duration-300 hover:border-amber-400/50 hover:bg-white/10 hover:shadow-lg hover:shadow-amber-500/10 focus:outline-none focus:ring-2 focus:ring-amber-400">
                                Read FAQ
                            </a>
                        </div>
                    </div>

                    <!-- Right Realistic 3D Cinema E-Ticket Pass -->
                    <div class="lg:col-span-5 perspective-1000 reveal-scroll">
                        <div class="text-center mb-3">
                            <span class="text-[11px] font-bold uppercase tracking-widest text-gray-400 flex items-center justify-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                                </svg>
                                Interactive 3D Ticket Pass
                            </span>
                        </div>

                        <!-- Realistic Cinema Pass Card -->
                        <div id="interactiveTicket" class="card-3d relative select-none rounded-2xl border border-amber-500/30 bg-gradient-to-b from-gray-900 via-gray-900/90 to-gray-950 p-6 shadow-2xl shadow-red-950/30 backdrop-blur-2xl transition-all duration-500">
                            <div class="card-3d-content">

                                <!-- Metallic Gold Header Ribbon -->
                                <div class="flex items-center justify-between border-b border-amber-500/20 pb-4">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-2.5 w-2.5 rounded-full bg-amber-400 shadow-[0_0_10px_#fbbf24]"></span>
                                        <span class="text-[11px] font-black uppercase tracking-[0.2em] text-amber-300">BookMyMovie VIP Pass</span>
                                    </div>
                                    <div class="inline-flex items-center gap-1 rounded-full border border-red-500/30 bg-red-500/10 px-2.5 py-0.5 text-[10px] font-bold tracking-wide text-red-400">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        COD PENDING
                                    </div>
                                </div>

                                <!-- Ticket Main Details -->
                                <div class="my-5 grid grid-cols-2 gap-x-4 gap-y-3">
                                    <div class="col-span-2">
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-amber-500/80">Feature Film</p>
                                        <p class="text-xl font-black tracking-tight text-white">GLADIATOR II</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Screen / Hall</p>
                                        <p class="text-xs font-bold text-gray-200">AUDI 03 • DOLBY ATMOS</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Seats Reserved</p>
                                        <p class="text-xs font-black text-amber-400">VIP • J14, J15</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Show Date & Time</p>
                                        <p class="text-xs font-bold text-gray-200">28 OCT 2026 • 08:30 PM</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Booking Ref</p>
                                        <p class="font-mono text-xs font-black text-white">#BMM-9842-X</p>
                                    </div>
                                </div>

                                <!-- Divider Line -->
                                <div class="relative my-4 flex items-center">
                                    <div class="-ml-9 h-6 w-6 rounded-full border-r border-white/10 bg-gray-950"></div>
                                    <div class="w-full border-b-2 border-dashed border-amber-500/30"></div>
                                    <div class="-mr-9 h-6 w-6 rounded-full border-l border-white/10 bg-gray-950"></div>
                                </div>

                                <!-- Ticket Stub Section -->
                                <div class="flex items-center justify-between pt-2">
                                    <div class="space-y-1">
                                        <p class="text-xs font-extrabold text-gray-200">Counter Collection</p>
                                        <p class="text-[10px] font-medium text-gray-400">Present code at box office</p>
                                    </div>

                                    <!-- Vector Barcode Graphics -->
                                    <div class="flex flex-col items-end gap-1">
                                        <svg class="h-7 w-28 text-white fill-current opacity-90" viewBox="0 0 118 24">
                                            <rect x="0" width="3" height="24"/>
                                            <rect x="5" width="1" height="24"/>
                                            <rect x="8" width="2" height="24"/>
                                            <rect x="12" width="4" height="24"/>
                                            <rect x="18" width="1" height="24"/>
                                            <rect x="21" width="3" height="24"/>
                                            <rect x="26" width="2" height="24"/>
                                            <rect x="30" width="1" height="24"/>
                                            <rect x="33" width="4" height="24"/>
                                            <rect x="39" width="2" height="24"/>
                                            <rect x="43" width="1" height="24"/>
                                            <rect x="46" width="3" height="24"/>
                                            <rect x="51" width="2" height="24"/>
                                            <rect x="55" width="4" height="24"/>
                                            <rect x="61" width="1" height="24"/>
                                            <rect x="64" width="2" height="24"/>
                                            <rect x="68" width="3" height="24"/>
                                            <rect x="73" width="1" height="24"/>
                                            <rect x="76" width="4" height="24"/>
                                            <rect x="82" width="2" height="24"/>
                                            <rect x="86" width="1" height="24"/>
                                            <rect x="89" width="3" height="24"/>
                                            <rect x="94" width="2" height="24"/>
                                            <rect x="98" width="4" height="24"/>
                                            <rect x="104" width="1" height="24"/>
                                            <rect x="107" width="3" height="24"/>
                                            <rect x="112" width="1" height="24"/>
                                            <rect x="115" width="3" height="24"/>
                                        </svg>
                                        <span class="font-mono text-[9px] tracking-widest text-gray-400">*98428901*</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3D Metrics Grid -->
                <div class="mt-16 grid gap-6 sm:grid-cols-3">
                    @foreach([
                            [
                                'value' => 'Instant',
                                'label' => 'Booking number right after checkout',
                                'icon' => '<svg class="h-6 w-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>'
                            ],
                            [
                                'value' => 'COD',
                                'label' => 'Pay cash directly at the cinema counter',
                                'icon' => '<svg class="h-6 w-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'
                            ],
                            [
                                'value' => 'QR-Ready',
                                'label' => 'Digital format optimized for fast verification',
                                'icon' => '<svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>'
                            ],
                        ] as $metric)
                            <article class="card-3d reveal-scroll group rounded-2xl border border-white/10 bg-gray-900/60 p-6 backdrop-blur-md shadow-xl hover:border-red-500/40">
                                <div class="card-3d-content flex items-start justify-between">
                                    <div>
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/5 border border-white/10 group-hover:scale-110 transition-transform">
                                            {!! $metric['icon'] !!}
                                        </div>
                                        <p class="mt-4 text-3xl font-black text-white group-hover:text-red-400 transition-colors">{{ $metric['value'] }}</p>
                                        <p class="mt-2 text-xs font-bold uppercase tracking-wider text-gray-400 leading-relaxed">{{ $metric['label'] }}</p>
                                    </div>
                                </div>
                            </article>
                    @endforeach
                </div>

                <!-- Step-by-Step Flow -->
                <div class="mt-20">
                    <div class="text-center reveal-scroll">
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-red-500">How It Works</p>
                        <h2 class="mt-2 text-3xl font-black text-white sm:text-4xl">4 Simple Steps to Your Movie Seat</h2>
                    </div>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach([
                                ['step' => '01', 'title' => 'Choose Seats', 'copy' => 'Select showtime, seat categories, and adult or kid ticket types from the seat map.'],
                                ['step' => '02', 'title' => 'Checkout', 'copy' => 'Confirm your selection and place a cash-on-delivery booking effortlessly.'],
                                ['step' => '03', 'title' => 'Save Booking', 'copy' => 'Your account securely stores your booking number, show details, and status.'],
                                ['step' => '04', 'title' => 'Collect Ticket', 'copy' => 'Show your booking code at the counter, pay cash, and grab your physical ticket.'],
                            ] as $item)
                                <article class="card-3d reveal-scroll group rounded-2xl border border-white/10 bg-gray-900/50 p-6 backdrop-blur-md shadow-lg transition-all hover:border-amber-500/40">
                                    <div class="card-3d-content">
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/10 px-3.5 py-1.5 text-xs font-black text-amber-400 shadow-inner">
                                                {{ $item['step'] }}
                                            </span>
                                            <div class="h-2 w-2 rounded-full bg-white/20 group-hover:bg-amber-400 transition-colors"></div>
                                        </div>
                                        <h3 class="mt-6 text-xl font-black text-white group-hover:text-amber-300 transition-colors">{{ $item['title'] }}</h3>
                                        <p class="mt-3 text-sm leading-6 text-gray-400">{{ $item['copy'] }}</p>
                                    </div>
                                </article>
                        @endforeach
                    </div>
                </div>

                <!-- Table Section -->
                <div class="mt-20 reveal-scroll">
                    <div class="mb-6">
                        <h2 class="text-2xl font-black text-white">Ticket Field Breakdown</h2>
                        <p class="text-sm text-gray-400">Everything you need to know about your digital booking receipt</p>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-gray-900/60 backdrop-blur-xl shadow-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[780px] text-left text-sm text-gray-300">
                                <thead class="bg-gray-950/80 text-xs uppercase tracking-widest text-red-400 border-b border-white/10">
                                    <tr>
                                        <th class="p-5">Ticket Detail</th>
                                        <th class="p-5">Where It Appears</th>
                                        <th class="p-5">Why It Matters</th>
                                        <th class="p-5">Customer Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach([
                                            ['Booking number', 'Booking confirmation & account history', 'Primary identification for counter collection & support', 'Keep ready before reaching the cinema'],
                                            ['Movie & showtime', 'Ticket detail page', 'Confirms exact theater hall and screening time', 'Arrive at least 15 mins before showtime'],
                                            ['Seats & category', 'Booking detail seat breakdown', 'Shows specific seat numbers and age classifications', 'Verify count matches your group size'],
                                            ['Payment status', 'Booking and tracking dashboards', 'Indicates whether COD payment is pending or completed', 'Prepare cash before reaching the counter'],
                                            ['Ticket code', 'Individual reserved seat cards', 'Uniquely identifies each seat within a group booking', 'Use for individual seat inquiry support'],
                                        ] as $row)
                                            <tr class="transition-colors hover:bg-white/[0.04]">
                                                <td class="p-5 font-black text-white flex items-center gap-2">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                    {{ $row[0] }}
                                                </td>
                                                <td class="p-5 text-gray-300">{{ $row[1] }}</td>
                                                <td class="p-5 text-gray-400">{{ $row[2] }}</td>
                                                <td class="p-5 font-semibold text-amber-300/90">{{ $row[3] }}</td>
                                            </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Action Guide Cards -->
                <div class="mt-20 grid gap-6 lg:grid-cols-3">
                    @foreach([
                            [
                                'title' => 'Before You Go',
                                'icon' => '<svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                                'items' => ['Open your account bookings page.', 'Check movie, theater, date, and show time.', 'Confirm all seat labels and ticket types.', 'Carry exact cash amount for COD payment.']
                            ],
                            [
                                'title' => 'At The Counter',
                                'icon' => '<svg class="h-6 w-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z"/></svg>',
                                'items' => ['Show your booking number or ticket detail page.', 'Pay the pending COD amount.', 'Collect your printed cinema tickets.', 'Keep ticket stub until show finishes.']
                            ],
                            [
                                'title' => 'Need Help?',
                                'icon' => '<svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                                'items' => ['Include booking number in support inquiries.', 'Mention movie name and cinema location.', 'Report duplicate bookings as early as possible.', 'Contact support prior to movie start time.']
                            ],
                        ] as $group)
                            <article class="card-3d reveal-scroll rounded-2xl border border-white/10 bg-gray-900/50 p-6 backdrop-blur-md shadow-xl">
                                <div class="card-3d-content">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/5 border border-white/10">
                                            {!! $group['icon'] !!}
                                        </div>
                                        <h2 class="text-xl font-black text-white">{{ $group['title'] }}</h2>
                                    </div>
                                    <ul class="mt-6 space-y-3 text-sm leading-6 text-gray-300">
                                        @foreach($group['items'] as $item)
                                            <li class="flex items-start gap-3 rounded-xl border border-white/5 bg-white/[0.02] p-3.5 backdrop-blur-sm transition-colors hover:bg-white/[0.05]">
                                                <svg class="h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>{{ $item }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </article>
                    @endforeach
                </div>

                <!-- Important Notice Banner -->
                <div class="card-3d reveal-scroll mt-16 rounded-2xl border border-red-500/30 bg-gradient-to-r from-red-950/40 via-gray-900/80 to-gray-950 p-8 shadow-2xl backdrop-blur-xl">
                    <div class="card-3d-content flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-red-600/20 text-red-400 border border-red-500/30">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-black uppercase tracking-widest text-red-400">Important Reminders</p>
                                <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-300">
                                    Online checkout locks your seat reservation. Physical ticket handover occurs exclusively at the cinema counter once cash payment is verified.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('contact') }}"
                            class="inline-flex whitespace-nowrap items-center justify-center rounded-full border border-white/10 bg-white/10 px-6 py-3.5 text-sm font-black text-white backdrop-blur-md transition-all hover:border-red-400 hover:bg-red-600 hover:shadow-lg hover:shadow-red-600/30 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Contact Support
                        </a>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <!-- Dynamic 3D Cursor Engine Script -->
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', () => {

            // 1. Smooth Realtime 3D Cursor Spotlight Tracking
            const cards = document.querySelectorAll('.card-3d');

            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;

                    const rotateX = ((y - centerY) / centerY) * -9; // Max tilt degrees
                    const rotateY = ((x - centerX) / centerX) * 9;

                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.015, 1.015, 1.015)`;
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)`;
                });
            });

            // 2. Scroll Reveal Observer
            const observerOptions = {
                threshold: 0.12,
                rootMargin: '0px 0px -40px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.reveal-scroll').forEach(el => observer.observe(el));
        });
    </script>
@endsection
