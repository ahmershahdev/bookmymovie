@extends('layouts.app')

@section('title', 'Contact & Cinema Network | BookMyMovie')

@push('styles')
    <style nonce="{{ $cspNonce ?? '' }}">
        /* Continuous glow animation for key map pins */
        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                opacity: 0.8;
            }

            50% {
                transform: scale(1.5);
                opacity: 0;
            }

            100% {
                transform: scale(0.95);
                opacity: 0;
            }
        }

        .map-pulse {
            animation: pulse-ring 2.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
            transform-origin: center;
        }
    </style>
@endpush

@section('content')
    <section class="relative overflow-hidden bg-gray-950 px-4 pb-20 pt-28 sm:px-6 lg:px-8">
        {{-- Background Glow & Grid Overlay --}}
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-red-950/20 via-gray-950 to-gray-950">
        </div>
        <div
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#1f29370f_1px,transparent_1px),linear-gradient(to_bottom,#1f29370f_1px,transparent_1px)] bg-[size:4rem_4rem]">
        </div>

        <div class="relative mx-auto max-w-7xl space-y-12">

            {{-- 1. FULL WIDTH SPIDER NETWORK MAP (TOP HERO SECTION) --}}
            <div class="overflow-hidden rounded-3xl border border-white/10 bg-gray-900 shadow-2xl">
                <div
                    class="flex flex-col items-start justify-between gap-4 border-b border-white/10 bg-black/40 px-6 py-5 sm:flex-row sm:items-center sm:px-8">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-red-400">
                            <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span> {{ $siteSettings['site_name'] ?? 'BookMyMovie' }} Network
                        </div>
                        <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">{{ $siteSettings['service_area'] ?? 'Pakistan' }} Multiplex & Operational Hubs
                        </h2>
                        <p class="text-xs text-gray-400">Spider-web telemetry interconnecting active theater hubs across
                            Pakistan.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-emerald-400">
                            {{ $siteSettings['service_area'] ?? 'Pakistan' }}
                        </span>
                        <span
                            class="rounded-full border border-red-500/20 bg-red-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-red-400">
                            Live Network
                        </span>
                    </div>
                </div>

                {{-- Map & Legend Container --}}
                <div
                    class="relative flex min-h-[500px] w-full items-center justify-center bg-gradient-to-br from-gray-950 via-black to-gray-950 p-6 sm:p-10">

                    <svg viewBox="0 0 1000 700"
                        class="h-full max-h-[600px] w-full drop-shadow-[0_20px_25px_rgba(0,0,0,0.9)]" role="img"
                        aria-label="Detailed Spider Web Map of Pakistan Cinema Network">
                        <defs>
                            <linearGradient id="mapGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#1f2937" />
                                <stop offset="50%" stop-color="#111827" />
                                <stop offset="100%" stop-color="#030712" />
                            </linearGradient>

                            <filter id="glow" x="-30%" y="-30%" width="160%" height="160%">
                                <feGaussianBlur stdDeviation="5" result="blur" />
                                <feComposite in="SourceGraphic" in2="blur" operator="over" />
                            </filter>

                            <filter id="coreGlow" x="-50%" y="-50%" width="200%" height="200%">
                                <feGaussianBlur stdDeviation="8" result="blur" />
                                <feComposite in="SourceGraphic" in2="blur" operator="over" />
                            </filter>
                        </defs>

                        {{-- Detailed Vector Outline of Pakistan --}}
                        <path d="M 520 40 
                           L 580 55 L 640 85 L 680 120 L 710 155 L 670 190 L 640 210 
                           L 660 250 L 630 300 L 600 320 L 590 370 L 540 430 L 490 510 
                           L 430 580 L 370 630 L 340 645 L 290 620 L 230 610 L 170 590 
                           L 120 570 L 140 510 L 130 460 L 160 410 L 180 360 L 210 320 
                           L 270 290 L 320 270 L 370 220 L 410 160 L 460 110 Z" fill="#000000" opacity="0.6"
                            transform="translate(15, 20)" />

                        <path d="M 520 40 
                           L 580 55 L 640 85 L 680 120 L 710 155 L 670 190 L 640 210 
                           L 660 250 L 630 300 L 600 320 L 590 370 L 540 430 L 490 510 
                           L 430 580 L 370 630 L 340 645 L 290 620 L 230 610 L 170 590 
                           L 120 570 L 140 510 L 130 460 L 160 410 L 180 360 L 210 320 
                           L 270 290 L 320 270 L 370 220 L 410 160 L 460 110 Z" fill="url(#mapGradient)" stroke="#ef4444"
                            stroke-width="2" stroke-linejoin="round" />

                        {{-- SPIDER WEB NETWORK CONNECTIONS (Centering on Islamabad Hub: 520, 210) --}}
                        <g stroke="rgba(239, 68, 68, 0.35)" stroke-width="1.5" stroke-dasharray="4,4">
                            {{-- Radial Spokes from Islamabad --}}
                            <line x1="520" y1="210" x2="350" y2="610" /> {{-- ISB -> Karachi --}}
                            <line x1="520" y1="210" x2="580" y2="290" /> {{-- ISB -> Lahore --}}
                            <line x1="520" y1="210" x2="495" y2="225" /> {{-- ISB -> Rawalpindi --}}
                            <line x1="520" y1="210" x2="430" y2="180" /> {{-- ISB -> Peshawar --}}
                            <line x1="520" y1="210" x2="250" y2="450" /> {{-- ISB -> Quetta --}}
                            <line x1="520" y1="210" x2="470" y2="380" /> {{-- ISB -> Multan --}}
                            <line x1="520" y1="210" x2="520" y2="310" /> {{-- ISB -> Faisalabad --}}
                            <line x1="520" y1="210" x2="560" y2="245" /> {{-- ISB -> Gujranwala --}}
                            <line x1="520" y1="210" x2="585" y2="225" /> {{-- ISB -> Sialkot --}}
                            <line x1="520" y1="210" x2="380" y2="580" /> {{-- ISB -> Hyderabad --}}

                            {{-- Concentric Interconnecting Web Arcs --}}
                            <path d="M 430 180 Q 470 190 495 225 Q 540 220 585 225 Q 575 260 580 290" fill="none"
                                stroke="rgba(239,68,68,0.2)" stroke-width="1" />
                            <path d="M 250 450 Q 360 410 470 380 Q 500 345 520 310 Q 550 300 580 290" fill="none"
                                stroke="rgba(239,68,68,0.2)" stroke-width="1" />
                            <path d="M 350 610 Q 365 595 380 580 Q 425 480 470 380" fill="none" stroke="rgba(239,68,68,0.2)"
                                stroke-width="1" />
                        </g>

                        {{-- CITY LOCATIONS DATA --}}
                        @php
                            $networkCities = [
                                ['x' => 520, 'y' => 210, 'city' => 'Islamabad', 'code' => 'ISB (HQ)', 'isHub' => true],
                                ['x' => 495, 'y' => 225, 'city' => 'Rawalpindi', 'code' => 'RWP', 'isHub' => false],
                                ['x' => 350, 'y' => 610, 'city' => 'Karachi', 'code' => 'KHI Megaplex', 'isHub' => true],
                                ['x' => 580, 'y' => 290, 'city' => 'Lahore', 'code' => 'LHE IMAX', 'isHub' => true],
                                ['x' => 430, 'y' => 180, 'city' => 'Peshawar', 'code' => 'PEW', 'isHub' => false],
                                ['x' => 250, 'y' => 450, 'city' => 'Quetta', 'code' => 'UET', 'isHub' => false],
                                ['x' => 470, 'y' => 380, 'city' => 'Multan', 'code' => 'MUX', 'isHub' => false],
                                ['x' => 520, 'y' => 310, 'city' => 'Faisalabad', 'code' => 'LYP', 'isHub' => false],
                                ['x' => 560, 'y' => 245, 'city' => 'Gujranwala', 'code' => 'GJR', 'isHub' => false],
                                ['x' => 585, 'y' => 225, 'city' => 'Sialkot', 'code' => 'SKT', 'isHub' => false],
                                ['x' => 380, 'y' => 580, 'city' => 'Hyderabad', 'code' => 'HDD', 'isHub' => false],
                            ];
                          @endphp

                        {{-- Render Network Pins & Labels --}}
                        @foreach($networkCities as $pin)
                            <g class="cursor-pointer group">
                                {{-- Pulse Glow Effect --}}
                                <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" r="{{ $pin['isHub'] ? '18' : '12' }}"
                                    class="map-pulse"
                                    fill="{{ $pin['isHub'] ? 'rgba(239,68,68,0.5)' : 'rgba(239,68,68,0.3)' }}" />

                                {{-- Inner Pin Point --}}
                                <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" r="{{ $pin['isHub'] ? '7' : '4.5' }}"
                                    fill="{{ $pin['isHub'] ? '#ffffff' : '#fca5a5' }}" stroke="#ef4444"
                                    stroke-width="{{ $pin['isHub'] ? '3' : '2' }}" filter="url(#glow)" />

                                {{-- Label Tag --}}
                                <g transform="translate({{ $pin['x'] + 12 }}, {{ $pin['y'] + 4 }})">
                                    <rect x="-4" y="-12" width="{{ strlen($pin['city'] . ' ' . $pin['code']) * 7 + 10 }}"
                                        height="18" rx="4" fill="rgba(3, 7, 18, 0.85)" stroke="rgba(255,255,255,0.1)" />
                                    <text x="0" y="0" fill="#ffffff" font-size="10" font-weight="800"
                                        class="tracking-wider uppercase">
                                        {{ $pin['city'] }}
                                        <tspan fill="#ef4444">({{ $pin['code'] }})</tspan>
                                    </text>
                                </g>
                            </g>
                        @endforeach
                    </svg>

                    {{-- Floating Map Legend Overlay --}}
                    <div
                        class="absolute bottom-6 left-6 hidden rounded-xl border border-white/10 bg-black/70 p-4 backdrop-blur-md sm:block">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-gold">Network Legend</h4>
                        <div class="mt-2 space-y-1.5 text-[11px] text-gray-300">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-white ring-2 ring-red-500"></span> Central Command
                                Hub (Islamabad)
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-red-400"></span> Major Multiplex Cluster
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-0.5 w-4 bg-red-500/50"></span> Active Telemetry Connection
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. LOWER SECTION: CONTACT CARDS & FORM GRID --}}
            <div class="grid items-start gap-12 lg:grid-cols-[1fr_1fr]">

                {{-- Left Column: Info & Routing Rules --}}
                <div class="space-y-8">
                    <div>
                        <h1 class="text-3xl font-black leading-tight text-white sm:text-4xl">
                            {{ $siteSettings['contact_heading'] ?? 'Talk to BookMyMovie support.' }}
                        </h1>
                        <p class="mt-3 text-sm leading-7 text-gray-300">
                            {{ $siteSettings['contact_intro'] ?? 'Send booking inquiries, cinema partnership requests, or account assistance. Provide your city, booking number, and showtime so support can resolve your query efficiently.' }}
                        </p>
                    </div>

                    {{-- Contact Info Cards Grid --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- Email Support Card --}}
                        <article
                            class="flex flex-col justify-between overflow-hidden rounded-xl border border-white/10 bg-gray-900/80 p-5 shadow-lg backdrop-blur-md transition hover:border-red-500/30">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-red-400">Official
                                    Desk</span>
                                <h3 class="mt-1 text-sm font-black text-white">Email Contact</h3>
                                <a href="mailto:{{ $siteSettings['support_email'] ?? 'support@bookmymovie.test' }}"
                                    class="mt-1 block text-sm font-bold text-red-300 hover:text-white break-all transition">
                                    {{ $siteSettings['support_email'] ?? 'support@bookmymovie.test' }}
                                </a>
                            </div>
                            <p class="mt-3 text-xs leading-5 text-gray-400">Best for booking, payment, and account support.
                            </p>
                        </article>

                        {{-- Portfolio Card --}}
                        <article
                            class="flex flex-col justify-between overflow-hidden rounded-xl border border-white/10 bg-gray-900/80 p-5 shadow-lg backdrop-blur-md transition hover:border-red-500/30">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gold">Developer
                                        Hub</span>
                                    <a href="https://ahmershah.dev" target="_blank" rel="noopener noreferrer"
                                        class="text-sm font-bold text-gold hover:underline">
                                        <svg class="h-4 w-4 text-gray-400 hover:text-gold" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                                <h3 class="mt-1 text-sm font-black text-white">Owner Website</h3>
                                <a href="https://ahmershah.dev" target="_blank" rel="noopener noreferrer"
                                    class="mt-1 inline-flex items-center gap-1.5 text-sm font-bold text-gold hover:underline break-all">
                                    <span>ahmershah.dev</span>
                                </a>
                            </div>
                            <p class="mt-3 text-xs leading-5 text-gray-400">Official website for project information and
                                updates.</p>
                        </article>

                        {{-- Service Coverage Card --}}
                        <article
                            class="flex flex-col justify-between overflow-hidden rounded-xl border border-white/10 bg-gray-900/80 p-5 shadow-lg backdrop-blur-md">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-red-400">Territory</span>
                                <h3 class="mt-1 text-lg font-black text-white">{{ $siteSettings['service_area'] ?? 'Pakistan' }}</h3>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-gray-400">Covering key theatrical multiplex networks
                                nationwide.</p>
                        </article>

                        {{-- Response SLA Card --}}
                        <article
                            class="flex flex-col justify-between overflow-hidden rounded-xl border border-white/10 bg-gray-900/80 p-5 shadow-lg backdrop-blur-md">
                            <div>
                                <span
                                    class="text-[10px] font-black uppercase tracking-widest text-red-400">Turnaround</span>
                                <h3 class="mt-1 text-lg font-black text-white">{{ $siteSettings['response_sla'] ?? 'Under 24 hours' }}</h3>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-gray-400">Academic portfolio inquiry routing SLAs.</p>
                        </article>
                    </div>

                    {{-- Hub Guidelines Card --}}
                    <div class="rounded-2xl border border-white/10 bg-gray-900/90 p-6 shadow-xl">
                        <h3 class="text-xs font-black uppercase tracking-widest text-gold">Hub Routing Guidelines</h3>
                        <div class="mt-4 space-y-3">
                            <div
                                class="rounded-xl border border-white/5 bg-white/[0.02] p-3 text-xs leading-5 text-gray-300">
                                <span class="font-bold text-white">Specify City & Theater:</span> Mention your nearest
                                multiplex for location-bound showtime queries.
                            </div>
                            <div
                                class="rounded-xl border border-white/5 bg-white/[0.02] p-3 text-xs leading-5 text-gray-300">
                                <span class="font-bold text-white">Order Identifiers:</span> Include ticket transaction IDs
                                for fast processing.
                            </div>
                            <div
                                class="rounded-xl border border-white/5 bg-white/[0.02] p-3 text-xs leading-5 text-gray-300">
                                <span class="font-bold text-white">Deliverability:</span> Avoid disposable email addresses
                                to ensure responses reach your primary inbox.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Direct Message Form --}}
                <div class="rounded-2xl border border-white/10 bg-gray-900 p-6 shadow-2xl sm:p-8">
                    <div class="flex items-center justify-between border-b border-white/10 pb-5">
                        <div>
                            <span class="text-xs font-black uppercase tracking-widest text-red-400">Direct Message</span>
                            <h2 class="mt-1 text-2xl font-black text-white">Get in Touch</h2>
                        </div>
                        <span
                            class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-[10px] font-extrabold uppercase text-emerald-400">
                            Encrypted
                        </span>
                    </div>

                    <form method="POST" action="{{ route('contact') }}" class="mt-6 space-y-5"
                        x-data="{ message: @js(old('message', '')) }">
                        @csrf

                        @if(session('status'))
                            <div
                                class="rounded-xl border border-emerald-500/30 bg-emerald-950/50 p-4 text-xs font-bold text-emerald-200">
                                {{ session('status') }}
                            </div>
                        @endif

                        {{-- Full Name Input (Min: 3, Max: 100) --}}
                        <div>
                            <div
                                class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-gray-300">
                                <label for="name">Full Name</label>
                                <span class="text-[10px] text-gray-500">Min 3 • Max 100 chars</span>
                            </div>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required minlength="3"
                                maxlength="100" placeholder="Syed Ahmer Shah"
                                class="mt-2 w-full rounded-xl border border-white/10 bg-gray-950 px-4 py-3.5 text-sm text-white placeholder:text-gray-600 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition">
                            @error('name')<p class="mt-1.5 text-xs font-semibold text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Email Address Input (Min: 5, Max: 255) --}}
                        <div>
                            <div
                                class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-gray-300">
                                <label for="email">Email Address</label>
                                <span class="text-[10px] text-gray-500">Min 5 • Max 255 chars</span>
                            </div>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required minlength="5"
                                maxlength="255" placeholder="name@example.com"
                                class="mt-2 w-full rounded-xl border border-white/10 bg-gray-950 px-4 py-3.5 text-sm text-white placeholder:text-gray-600 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition">
                            @error('email')<p class="mt-1.5 text-xs font-semibold text-red-400">{{ $message }}</p>@enderror
                        </div>

                        {{-- Message Textarea (Min: 20, Max: 1200) --}}
                        <div>
                            <div
                                class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-gray-300">
                                <label for="message">Message</label>
                                <span class="text-[10px]" :class="message.length < 20 ? 'text-amber-400' : 'text-gray-500'">
                                    <span x-text="message.length"></span>/1200 chars (Min 20)
                                </span>
                            </div>
                            <textarea id="message" name="message" rows="6" required minlength="20" maxlength="1200"
                                x-model="message"
                                placeholder="State your inquiry, booking reference, or feedback details here..."
                                class="mt-2 w-full resize-y rounded-xl border border-white/10 bg-gray-950 px-4 py-3.5 text-sm text-white placeholder:text-gray-600 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition"></textarea>
                            @error('message')<p class="mt-1.5 text-xs font-semibold text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-recaptcha action="contact" />

                        <button type="submit"
                            class="w-full rounded-xl bg-red-600 py-4 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-red-950/50 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Send Message
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>
@endsection
