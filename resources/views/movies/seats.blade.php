@extends('layouts.app')

@section('title', 'Select Seats | ' . $movie->title)

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 text-gray-100 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr_360px]">
            {{-- Main Content Column --}}
            <div>
                <x-section-heading 
                    eyebrow="Seat Selection" 
                    :title="$movie->title"
                    description="Choose up to 4 available seats. Held seats expire after 10 minutes, and taken seats are marked as reserved or booked immediately." 
                />

                <form method="POST" action="{{ route('user.cart') }}" class="mt-8 space-y-6"
                    @submit.prevent="flyAndSubmit($event, 'cart-icon', 'cartCount')">
                    @csrf
                    <input type="hidden" name="show_id" value="{{ $showModel->id }}">

                    {{-- Form-level Errors --}}
                    @error('cart')
                        <p class="rounded-xl border border-red-500/30 bg-red-950/60 p-4 text-sm font-bold text-red-100 backdrop-blur-md">
                            {{ $message }}
                        </p>
                    @enderror
                    @error('show_id')
                        <p class="rounded-xl border border-red-500/30 bg-red-950/60 p-4 text-sm font-bold text-red-100 backdrop-blur-md">
                            {{ $message }}
                        </p>
                    @enderror

                    {{-- Premium Glassmorphism Ticket Type Dropdown --}}
                    <div class="relative rounded-2xl border border-white/10 bg-white/[0.03] p-5 shadow-2xl backdrop-blur-xl transition-all duration-300 hover:border-white/20"
                        x-data="{ open: false, selected: '{{ old('ticket_type', 'adult') }}' }">

                        {{-- Hidden input for Form Submission --}}
                        <input type="hidden" name="ticket_type" :value="selected">

                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">
                            Ticket Type
                        </label>

                        <!-- Trigger Button -->
                        <button type="button" 
                            @click="open = !open" 
                            @click.away="open = false"
                            class="mt-3 flex w-full items-center justify-between rounded-xl border border-white/10 bg-gray-950/80 px-4 py-3.5 text-left text-white shadow-inner backdrop-blur-md transition-all duration-200 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                            aria-haspopup="listbox"
                            :aria-expanded="open">

                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-500/30 bg-red-500/10 text-red-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 001 1h1a2 2 0 002-2V7a2 2 0 00-2-2H5zm0 10a2 2 0 00-2 2v3a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H5z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-white" x-text="selected === 'adult' ? 'Adult Ticket' : 'Kid Ticket'"></span>
                            </div>

                            <svg class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180 text-red-400': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Floating Glass Menu -->
                        <div x-show="open" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                            class="absolute left-0 right-0 z-50 mt-2 overflow-hidden rounded-xl border border-white/10 bg-gray-950/95 p-1.5 shadow-2xl backdrop-blur-2xl"
                            style="display: none;">

                            <!-- Adult Option -->
                            <button type="button" 
                                @click="selected = 'adult'; open = false" 
                                class="flex w-full items-center justify-between rounded-lg px-3.5 py-3 text-left transition-all duration-200 hover:bg-white/10"
                                :class="{ 'bg-red-500/15 text-red-400 border border-red-500/30': selected === 'adult' }">
                                <div>
                                    <p class="text-sm font-bold text-white">Adult Ticket</p>
                                    <p class="text-xs text-gray-400">Standard entry pricing</p>
                                </div>
                            </button>

                            <!-- Kid Option -->
                            <button type="button" 
                                @click="selected = 'kid'; open = false" 
                                class="mt-1 flex w-full items-center justify-between rounded-lg px-3.5 py-3 text-left transition-all duration-200 hover:bg-white/10"
                                :class="{ 'bg-red-500/15 text-red-400 border border-red-500/30': selected === 'kid' }">
                                <div>
                                    <p class="text-sm font-bold text-white">Kid Ticket</p>
                                    <p class="text-xs text-gray-400">Discounted rate for children</p>
                                </div>
                            </button>
                        </div>

                        @error('ticket_type')
                            <p class="mt-2 text-xs font-medium text-red-400 flex items-center gap-1.5">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Seat Picker --}}
                    <x-seat-picker :seats="$seats" />

                    {{-- Actions --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" data-fly-source
                            class="rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400 transition-all duration-200 shadow-lg shadow-red-600/30">
                            Add selected seats to cart
                        </button>
                        <a href="{{ route('movies.show', $movie->slug) }}"
                            class="rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-black text-white transition-all duration-200 hover:border-red-400/60 hover:bg-white/10">
                            Back to showtimes
                        </a>
                    </div>
                </form>
            </div>

            {{-- Sidebar Column --}}
            <aside class="h-max rounded-2xl border border-white/10 bg-gray-900/80 p-6 shadow-2xl backdrop-blur-md">
                <p class="text-xs font-black uppercase tracking-[.22em] text-red-400">Show Details</p>
                <h2 class="mt-3 text-2xl font-black text-white">{{ $showDetails->theater_name }}</h2>

                <dl class="mt-5 space-y-4 text-sm border-b border-white/10 pb-6">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Screen</dt>
                        <dd class="font-bold text-white">{{ $showDetails->screen_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Date</dt>
                        <dd class="font-bold text-white">{{ \Illuminate\Support\Carbon::parse($showDetails->show_date)->format('D, M j, Y') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Time</dt>
                        <dd class="font-bold text-white">{{ \Illuminate\Support\Carbon::parse($showDetails->show_time)->format('h:i A') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Seats left</dt>
                        <dd class="font-bold text-emerald-400">{{ $showDetails->available_seats }}</dd>
                    </div>
                </dl>

                {{-- Tiered Pricing --}}
                <div class="mt-6">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-red-400">Tiered pricing</p>
                    <div class="mt-3 space-y-3">
                        @foreach($pricingTiers as $tier)
                            <div class="rounded-xl border border-white/10 bg-gray-950/60 p-3.5 backdrop-blur-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-white text-sm">
                                            Rows {{ $tier['rows'] }} - {{ $tier['category'] }}
                                            @if(!empty($tier['is_front']))
                                                <span class="text-amber-400 text-xs font-normal ml-1">(front premium)</span>
                                            @endif
                                        </p>

                                        <p class="mt-1 text-xs text-gray-400">
                                            Adult: <span class="text-white font-semibold">PKR {{ number_format($tier['sale_price'] ?? $tier['price']) }}</span>
                                            @if(!empty($tier['sale_price']))
                                                <span class="line-through text-gray-500 ml-1">PKR {{ number_format($tier['price']) }}</span>
                                            @endif
                                        </p>

                                        @if(!empty($tier['kids_price']) || !empty($tier['kids_sale_price']))
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                Kid: <span class="text-white font-semibold">PKR {{ number_format($tier['kids_sale_price'] ?? $tier['kids_price']) }}</span>
                                                @if(!empty($tier['kids_sale_price']))
                                                    <span class="line-through text-gray-500 ml-1">PKR {{ number_format($tier['kids_price']) }}</span>
                                                @endif
                                            </p>
                                        @endif

                                        @if(!empty($tier['benefits']))
                                            <p class="mt-2 text-xs text-gray-300 bg-white/5 p-2 rounded-lg border border-white/5">{{ $tier['benefits'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection