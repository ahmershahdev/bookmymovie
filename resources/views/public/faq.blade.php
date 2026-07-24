@extends('layouts.app')

@section('title', 'Help Center & FAQ | BookMyMovie')

@section('content')
    <section class="relative overflow-hidden bg-gray-950 px-4 pb-20 pt-28 sm:px-6 lg:px-8">
            {{-- Ambient Background Glow --}}
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-red-950/30 via-gray-950 to-gray-950"></div>
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#1f29370f_1px,transparent_1px),linear-gradient(to_bottom,#1f29370f_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>

            <div class="relative mx-auto max-w-7xl space-y-12" x-data="{ searchQuery: '', selectedCategory: 'All', activeFaq: null }">

                {{-- HEADER & HERO SECTION --}}
                <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">

                    {{-- Left Header Column --}}
                    <div class="space-y-6">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/20 bg-amber-500/10 px-3.5 py-1 text-xs font-black uppercase tracking-widest text-amber-400 backdrop-blur-md">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                Customer Support Center
                            </div>
                            <h1 class="mt-4 text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                                Frequently Asked <span class="bg-gradient-to-r from-white via-gray-200 to-red-400 bg-clip-text text-transparent">Questions</span>
                            </h1>
                            <p class="mt-4 text-sm leading-relaxed text-gray-300 sm:text-base">
                                Find immediate assistance regarding ticket reservations, seat selection, snack pre-orders, cinema policies, payment methods, and e-tickets.
                            </p>
                        </div>

                        {{-- Live FAQ Search Input --}}
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                x-model="searchQuery" 
                                placeholder="Search topics (e.g., refund, IMAX, snacks, payment, age rating)..." 
                                class="w-full rounded-2xl border border-white/10 bg-gray-900/90 py-4 pl-11 pr-10 text-sm text-white placeholder-gray-500 shadow-xl backdrop-blur-md transition duration-200 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30"
                            >
                            <button 
                                x-show="searchQuery !== ''" 
                                @click="searchQuery = ''" 
                                type="button" 
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-white"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Quick Highlights --}}
                        <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                            @foreach([
                                    ['icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2 2 2 0 010 4 2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 00-2-2 2 2 0 010-4 2 2 0 002-2V7a2 2 0 00-2-2H5z', 'title' => 'Instant Digital Tickets', 'copy' => 'Receive QR-code passes directly in your account and registered email upon checkout.'],
                                    ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Real-Time Seat Hold', 'copy' => 'Selected seats are temporarily locked for 10 minutes while you finalize payment.'],
                                    ['icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z', 'title' => 'Flexible Collection', 'copy' => 'Pay securely online or choose counter cash collection at participating theaters.']
                                ] as $card)
                                    <article class="group rounded-2xl border border-white/10 bg-gray-900/60 p-5 shadow-lg backdrop-blur-md transition duration-300 hover:border-red-500/40 hover:bg-gray-900/90">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 group-hover:scale-110 transition duration-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h2 class="text-sm font-black text-white sm:text-base">{{ $card['title'] }}</h2>
                                                <p class="mt-1 text-xs leading-relaxed text-gray-400">{{ $card['copy'] }}</p>
                                            </div>
                                        </div>
                                    </article>
                            @endforeach
                        </div>
                    </div>

                    {{-- Right Quick Reference Matrix --}}
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-gray-900/80 shadow-2xl backdrop-blur-md">
                        <div class="border-b border-white/10 bg-black/40 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-black uppercase tracking-wider text-white">Quick Action Checklist</h2>
                                    <p class="text-xs text-gray-400">Essential details to keep handy for faster resolution.</p>
                                </div>
                                <span class="rounded-full bg-red-500/10 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-red-400 border border-red-500/20">
                                    Self-Service
                                </span>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-gray-300">
                                <thead class="border-b border-white/10 bg-gray-950/80 uppercase tracking-widest text-red-400 font-extrabold">
                                    <tr>
                                        <th class="p-4">Issue Category</th>
                                        <th class="p-4">Recommended Action</th>
                                        <th class="p-4">Helpful Details</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach([
                                            ['Booking Status', 'Check "My Bookings" tab', 'Booking Ref #, Showtime', 'bg-emerald-400'],
                                            ['Counter Pickups', 'Keep confirmation QR ready', 'Gov ID, Order QR Code', 'bg-sky-400'],
                                            ['Cancellations', 'Review theater policy', 'Transaction ID, Payment Mode', 'bg-amber-400'],
                                            ['Account Recovery', 'Use password reset option', 'Registered Email Address', 'bg-purple-400']
                                        ] as $row)
                                            <tr class="transition duration-150 hover:bg-white/[0.03]">
                                                <td class="p-4 font-black text-white whitespace-nowrap">
                                                    <div class="flex items-center gap-2">
                                                        <span class="h-1.5 w-1.5 rounded-full {{ $row[3] }}"></span>
                                                        {{ $row[0] }}
                                                    </div>
                                                </td>
                                                <td class="p-4 text-gray-300 font-medium whitespace-nowrap">{{ $row[1] }}</td>
                                                <td class="p-4 text-gray-400 whitespace-nowrap">{{ $row[2] }}</td>
                                            </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                {{-- DYNAMIC DATABASE FAQ SECTION --}}
                @php
                    // Extract unique categories dynamically from the database query
                    $categories = $faqs->pluck('category')->filter()->unique()->values();
                @endphp

                <div class="space-y-6 pt-6">
                    <div class="flex flex-col gap-4 border-b border-white/10 pb-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-black text-white">Help Topics & FAQs</h2>
                            <p class="text-xs text-gray-400 mt-1">Browse topics by category or use the live search bar above.</p>
                        </div>
                        <span class="inline-flex self-start sm:self-center rounded-full bg-white/5 px-3 py-1.5 text-xs font-semibold text-gray-300 border border-white/10">
                            Showing {{ $faqs->count() }} {{ Str::plural('Topic', $faqs->count()) }}
                        </span>
                    </div>

                    {{-- Dynamic Category Pills --}}
                    @if($categories->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            <button 
                                type="button" 
                                @click="selectedCategory = 'All'" 
                                :class="selectedCategory === 'All' ? 'bg-red-600 text-white border-red-500' : 'bg-gray-900/80 text-gray-400 border-white/10 hover:border-white/20 hover:text-white'"
                                class="rounded-xl border px-4 py-2 text-xs font-extrabold uppercase tracking-wider backdrop-blur-md transition duration-200"
                            >
                                All Topics
                            </button>
                            @foreach($categories as $category)
                                <button 
                                    type="button" 
                                    @click="selectedCategory = @js($category)" 
                                    :class="selectedCategory === @js($category) ? 'bg-red-600 text-white border-red-500' : 'bg-gray-900/80 text-gray-400 border-white/10 hover:border-white/20 hover:text-white'"
                                    class="rounded-xl border px-4 py-2 text-xs font-extrabold uppercase tracking-wider backdrop-blur-md transition duration-200"
                                >
                                    {{ $category }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Accordion Grid --}}
                    @if($faqs->isNotEmpty())
                        <div class="grid gap-4 lg:grid-cols-2">
                            @foreach($faqs as $faq)
                                @php
                                    $categoryName = $faq->category ?? 'General';
                                @endphp
                                <article 
                                    x-show="(selectedCategory === 'All' || selectedCategory === @js($categoryName)) && (searchQuery === '' || @js(strtolower($faq->question . ' ' . $faq->answer . ' ' . $categoryName)).includes(searchQuery.toLowerCase()))"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                    class="rounded-2xl border border-white/10 bg-gray-900/80 shadow-lg backdrop-blur-md transition duration-300 hover:border-red-500/30"
                                >
                                    <button 
                                        type="button" 
                                        @click="activeFaq = activeFaq === {{ $faq->id }} ? null : {{ $faq->id }}"
                                        class="flex w-full items-start justify-between gap-4 p-6 text-left focus:outline-none"
                                    >
                                        <div class="space-y-2">
                                            <span class="inline-block rounded-md bg-white/5 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-red-400 border border-white/5">
                                                {{ $categoryName }}
                                            </span>
                                            <h3 class="text-base font-black text-white pr-2 leading-snug">{{ $faq->question }}</h3>
                                        </div>

                                        <div 
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white transition duration-300 mt-1"
                                            :class="{ 'rotate-180 bg-red-600 border-red-500 text-white': activeFaq === {{ $faq->id }} }"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </button>

                                    <div 
                                        x-show="activeFaq === {{ $faq->id }}" 
                                        x-collapse
                                        class="border-t border-white/5 px-6 pb-6 pt-4 text-sm leading-relaxed text-gray-300"
                                    >
                                        {!! nl2br(e($faq->answer)) !!}
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        {{-- Empty State (No Database Records) --}}
                        <div class="rounded-2xl border border-dashed border-white/10 bg-gray-900/40 p-12 text-center backdrop-blur-md">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-800 text-gray-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-base font-black text-white">No FAQ Topics Found</h3>
                            <p class="mt-1 text-xs text-gray-400">Our support topics are currently being updated. Please check back shortly or reach out to us directly.</p>
                        </div>
                    @endif
                </div>

                {{-- STILL NEED HELP CTA --}}
                <div class="flex flex-col items-center justify-between gap-6 rounded-3xl border border-white/10 bg-gradient-to-r from-gray-900 via-gray-900 to-red-950/40 p-8 shadow-2xl sm:flex-row">
                    <div class="space-y-1 text-center sm:text-left">
                        <h3 class="text-xl font-black text-white">Can't find the answer you need?</h3>
                        <p class="text-xs text-gray-400">Our customer support desk is available to help resolve your ticket and booking queries.</p>
                    </div>
                    <a href="{{ route('contact') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-red-600 px-6 py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-red-950/50 transition duration-300 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
                        <span>Contact Support Desk</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>

            </div>
        </section>
@endsection
