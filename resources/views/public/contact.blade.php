@extends('layouts.app')

@section('title', 'Contact | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid items-start gap-8 lg:grid-cols-[.9fr_1.1fr]">
                <div>
                    <p class="text-sm font-black uppercase tracking-[.22em] text-gold">Pakistan support desk</p>
                    <h1 class="mt-4 max-w-3xl text-4xl font-black leading-tight text-white sm:text-5xl">
                        Talk to BookMyMovie support with clear details and faster follow-up.
                    </h1>
                    <p class="mt-5 max-w-2xl text-sm leading-7 text-gray-300">
                        Send booking questions, cinema partnership requests, feedback, or account help. Include your city,
                        booking number, and show date when relevant so the team can route the message correctly.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach([
                            ['label' => 'Email', 'value' => 'support@ahmershah.dev', 'copy' => 'Best for booking, account, and refund questions.'],
                            ['label' => 'Coverage', 'value' => 'Pakistan', 'copy' => 'Built around major cinema cities and local support needs.'],
                            ['label' => 'Response goal', 'value' => '24 hours', 'copy' => 'Academic project inbox response timing may vary.'],
                            ['label' => 'Message limit', 'value' => '1,200 chars', 'copy' => 'Short, complete requests are easier to review.'],
                        ] as $card)
                            <article class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-5">
                                <p class="text-xs font-black uppercase tracking-[.18em] text-red-300">{{ $card['label'] }}</p>
                                <h2 class="mt-3 text-2xl font-black text-white">{{ $card['value'] }}</h2>
                                <p class="mt-2 text-sm leading-6 text-gray-400">{{ $card['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>

                    <div class="premium-tilt mt-6 overflow-hidden rounded-lg border border-white/10 bg-gray-900">
                        <div class="border-b border-white/10 px-5 py-4">
                            <h2 class="text-lg font-black text-white">Pakistan service map</h2>
                            <p class="mt-1 text-sm text-gray-400">Primary project coverage zones and common cinema hubs.</p>
                        </div>
                        <div class="grid gap-0 lg:grid-cols-[1fr_.85fr]">
                            <div class="relative min-h-80 bg-gradient-to-br from-gray-950 via-gray-900 to-black p-4">
                                <svg viewBox="0 0 420 480" class="h-full min-h-80 w-full drop-shadow-2xl" role="img"
                                    aria-label="Stylized map of Pakistan with support coverage markers">
                                    <path
                                        d="M217 32l44 33 19 48 43 26-20 52 34 45-18 57 28 49-42 26-15 61-60-15-45 28-54-13-33-42-54-12 18-55-29-48 45-34-2-58 45-31 18-64z"
                                        fill="#101827" stroke="#ef4444" stroke-width="4" />
                                    <path
                                        d="M211 72l32 26 15 43 34 18-18 41 29 37-14 46 25 42-34 20-13 48-42-11-38 22-43-11-25-32-43-10 15-43-24-36 34-28-2-47 34-24 14-51z"
                                        fill="rgba(220,38,38,.14)" stroke="rgba(212,175,55,.55)" stroke-width="2" />
                                    @foreach([
                                        ['x' => 184, 'y' => 330, 'city' => 'Karachi'],
                                        ['x' => 214, 'y' => 226, 'city' => 'Lahore'],
                                        ['x' => 204, 'y' => 202, 'city' => 'Islamabad'],
                                        ['x' => 183, 'y' => 248, 'city' => 'Multan'],
                                        ['x' => 146, 'y' => 191, 'city' => 'Peshawar'],
                                        ['x' => 118, 'y' => 304, 'city' => 'Quetta'],
                                    ] as $pin)
                                        <g>
                                            <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" r="14"
                                                fill="rgba(239,68,68,.24)" />
                                            <circle cx="{{ $pin['x'] }}" cy="{{ $pin['y'] }}" r="6" fill="#fca5a5" />
                                            <text x="{{ $pin['x'] + 12 }}" y="{{ $pin['y'] + 4 }}" fill="#f8fafc"
                                                font-size="15" font-weight="800">{{ $pin['city'] }}</text>
                                        </g>
                                    @endforeach
                                </svg>
                            </div>
                            <div class="border-t border-white/10 p-5 lg:border-l lg:border-t-0">
                                <h3 class="text-sm font-black uppercase tracking-[.18em] text-gold">Routing tips</h3>
                                <ul class="mt-4 space-y-3 text-sm leading-6 text-gray-300">
                                    <li class="rounded-md border border-white/10 bg-white/[.03] p-3">Mention your city for cinema-specific support.</li>
                                    <li class="rounded-md border border-white/10 bg-white/[.03] p-3">Use your booking number for ticket or refund questions.</li>
                                    <li class="rounded-md border border-white/10 bg-white/[.03] p-3">Avoid temporary email services so replies are deliverable.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('contact') }}"
                    class="premium-tilt rounded-lg border border-white/10 bg-gray-900 p-6 shadow-2xl shadow-black/30"
                    x-data="{ message: @js(old('message', '')) }">
                    @csrf

                    @if(session('status'))
                        <div class="mb-5 rounded-md border border-green-500/30 bg-green-950 px-4 py-3 text-sm font-bold text-green-100">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-[.2em] text-red-300">Contact form</p>
                            <h2 class="mt-2 text-2xl font-black text-white">Send a detailed message</h2>
                        </div>
                        <span class="rounded-full border border-white/10 bg-white/[.04] px-3 py-1 text-xs font-black text-gray-300">
                            Secure
                        </span>
                    </div>

                    <div class="mt-6 grid gap-5">
                        <label class="block">
                            <span class="text-sm font-bold text-gray-300">Full name</span>
                            <input name="name" value="{{ old('name') }}" required minlength="3" maxlength="100"
                                placeholder="Syed Ahmer Shah"
                                class="mt-2 w-full rounded-md border-white/10 bg-gray-950 px-4 py-3 text-white placeholder:text-gray-500 focus:border-red-400 focus:ring-red-400">
                        </label>
                        @error('name')<p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>@enderror

                        <label class="block">
                            <span class="text-sm font-bold text-gray-300">Email address</span>
                            <input name="email" type="email" value="{{ old('email') }}" required minlength="6"
                                maxlength="150" placeholder="name@example.com"
                                class="mt-2 w-full rounded-md border-white/10 bg-gray-950 px-4 py-3 text-white placeholder:text-gray-500 focus:border-red-400 focus:ring-red-400">
                        </label>
                        @error('email')<p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>@enderror

                        <label class="block">
                            <span class="flex items-center justify-between gap-3 text-sm font-bold text-gray-300">
                                <span>Message</span>
                                <span class="text-xs text-gray-500"><span x-text="message.length"></span>/1200</span>
                            </span>
                            <textarea name="message" rows="8" required minlength="20" maxlength="1200" x-model="message"
                                placeholder="Example: I need help with booking BMM-2026-0001 for Karachi, Saturday 8 PM show. Please include the issue, city, date, and preferred contact time."
                                class="mt-2 w-full resize-y rounded-md border-white/10 bg-gray-950 px-4 py-3 text-white placeholder:text-gray-500 focus:border-red-400 focus:ring-red-400"></textarea>
                        </label>
                        @error('message')<p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>@enderror

                        <x-recaptcha action="contact" />

                        <button
                            class="rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-red-950/30 transition hover:-translate-y-0.5 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
                            Send message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
