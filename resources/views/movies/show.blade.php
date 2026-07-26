@extends('layouts.app')

@php
    $posterUrl = $movie->publicMediaUrl($movie->poster_image) ?: asset('images/logo.png');
    $backdropUrl = $movie->publicMediaUrl($movie->hero_image ?: $movie->banner_image ?: $movie->poster_image) ?: asset('images/logo.png');
@endphp

@section('title', ($movie->meta_title ?: $movie->title . ' Tickets') . ' | BookMyMovie')
@section('meta_description', $movie->meta_description ?: \Illuminate\Support\Str::limit($movie->description ?: 'Book showtimes and seats for ' . $movie->title . ' with BookMyMovie.', 160, ''))
@section('canonical', rtrim($siteSettings['canonical_base_url'] ?? 'https://bookmymovie.ahmershah.dev', '/') . route('movies.show', $movie->slug, false))
@section('og_type', 'video.movie')
@section('json_ld', json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Movie',
    'name' => $movie->title,
    'description' => $movie->meta_description ?: $movie->description,
    'image' => $posterUrl,
    'datePublished' => optional($movie->release_date)->toDateString(),
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => $movie->displayRating(),
        'reviewCount' => max(1, $movie->displayReviewCount()),
        'bestRating' => 5,
        'worstRating' => 1,
    ],
    'offers' => [
        '@type' => 'Offer',
        'price' => $movie->cardPrice(),
        'priceCurrency' => 'PKR',
        'availability' => $shows->isNotEmpty() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
        'url' => rtrim($siteSettings['canonical_base_url'] ?? 'https://bookmymovie.ahmershah.dev', '/') . route('movies.show', $movie->slug, false),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))

@section('content')
    <div class="bg-gray-950 text-gray-100 min-h-screen" x-data="{ activeTab: 'showtimes', trailerOpen: false }">

        <!-- Hero / Backdrop Banner Section -->
        <div class="relative w-full h-[460px] md:h-[520px] overflow-hidden">
            <!-- Backdrop Image & Overlays -->
            <div class="absolute inset-0">
                <img src="{{ $backdropUrl }}"
                    alt="{{ $movie->title }} backdrop"
                    class="w-full h-full object-cover object-center filter blur-sm scale-105 opacity-30">
                <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/80 to-transparent"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/60 to-transparent"></div>
            </div>

            <!-- Hero Content Container -->
            <div class="relative max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8 flex items-end pb-10">
                <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] lg:grid-cols-[250px_1fr] gap-8 items-end w-full">

                    <!-- Poster Card (Aspect 9:16) -->
                    <div
                        class="hidden md:block relative group rounded-2xl overflow-hidden border border-white/10 shadow-2xl shadow-red-950/30 aspect-[9/16] bg-gray-900 w-full max-w-[250px]">
                        <img src="{{ $posterUrl }}"
                            alt="{{ $movie->title }} poster"
                            class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">

                        @if($movie->trailer_url)
                            <button @click="trailerOpen = true"
                                class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <span
                                    class="p-3.5 rounded-full bg-red-600/90 text-white shadow-lg backdrop-blur-sm transform group-hover:scale-110 transition-transform">
                                    <svg class="w-7 h-7 fill-current" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </span>
                            </button>
                        @endif
                    </div>

                    <!-- Main Info Header -->
                    <div class="space-y-4">
                        <!-- Status Badge -->
                        <div class="flex items-center gap-3 flex-wrap">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-red-500/10 text-red-400 border border-red-500/20">
                                {{ str($movie->status)->replace('_', ' ')->headline() }}
                            </span>
                            @if($movie->release_date)
                                <span class="text-xs text-gray-400 font-medium">
                                    Released {{ \Illuminate\Support\Carbon::parse($movie->release_date)->format('M d, Y') }}
                                </span>
                            @endif
                        </div>

                        <!-- Title -->
                        <h1 class="text-3xl md:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight">
                            {{ $movie->title }}
                        </h1>

                        <!-- Key Metadata Pills (Borders removed from pills) -->
                        <div
                            class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs sm:text-sm font-semibold text-gray-300">
                            <span class="px-3 py-1 rounded-md bg-white/10 backdrop-blur-md">
                                {{ $movie->certificate_rating ?: 'NR' }}
                            </span>
                            <span class="px-3 py-1 rounded-md bg-white/10 backdrop-blur-md">
                                {{ intdiv($movie->duration_minutes, 60) }}h {{ $movie->duration_minutes % 60 }}m
                            </span>
                            <span class="px-3 py-1 rounded-md bg-white/10 backdrop-blur-md">
                                {{ $movie->language }}
                            </span>
                            <span class="px-3 py-1 rounded-md bg-white/10 backdrop-blur-md">
                                {{ $movie->genres->pluck('name')->join(' • ') }}
                            </span>

                            <div class="flex items-center px-3 py-1 rounded-md bg-amber-500/10 text-amber-400">
                                <x-star-rating :rating="$movie->displayRating()" />
                            </div>
                        </div>

                        <!-- Action CTA Bar -->
                        <div class="pt-4 flex flex-wrap items-center gap-3">
                            @if($shows->isNotEmpty())
                                <a href="#showtimes-section" @click="activeTab = 'showtimes'"
                                    class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-red-600/30 hover:bg-red-500 hover:shadow-red-600/50 transition-all duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 001 1.516 2 2 0 010 3.468A2 2 0 003 16v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 00-1-1.516 2 2 0 010-3.468A2 2 0 0021 10V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    Book Tickets
                                </a>
                            @endif

                            <!-- Wishlist Form Button -->
                            <form method="POST" action="{{ route('user.wishlist') }}" class="inline-block"
                                @submit.prevent="flyAndSubmit($event, 'wishlist-icon', 'wishlistCount')">
                                @csrf
                                <input type="hidden" name="movie_id" value="{{ $movie->id }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3.5 text-sm font-bold text-white backdrop-blur-md hover:bg-white/10 hover:border-white/25 transition-all">
                                    <svg class="w-5 h-5 text-red-500 fill-current" viewBox="0 0 24 24">
                                        <path
                                            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                    </svg>
                                    Wishlist
                                </button>
                            </form>

                            <a href="{{ route('movies.compare') }}"
                                @click.prevent="addToCompare({{ Illuminate\Support\Js::from($movie->toCardArray()) }}); window.location.href = '{{ route('movies.compare') }}'"
                                class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3.5 text-sm font-bold text-white backdrop-blur-md hover:bg-white/10 hover:border-white/25 transition-all">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                                </svg>
                                Compare
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Main Content Body -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" id="showtimes-section">

            <!-- Tab Navigation Header -->
            <div class="border-b border-white/10 mb-8">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <button @click="activeTab = 'showtimes'"
                        :class="activeTab === 'showtimes' ? 'border-red-500 text-red-500' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-500'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm tracking-wide transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Available Showtimes
                        <span
                            class="ml-1 px-2 py-0.5 text-xs rounded-full bg-red-950/60 text-red-400 border border-red-800/40">
                            {{ $shows->count() }}
                        </span>
                    </button>

                    <button @click="activeTab = 'overview'"
                        :class="activeTab === 'overview' ? 'border-red-500 text-red-500' : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-500'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm tracking-wide transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Movie Overview
                    </button>
                </nav>
            </div>

            <!-- TAB 1: SHOWTIMES -->
            <div x-show="activeTab === 'showtimes'" class="space-y-6">
                @if($shows->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($shows as $show)
                            <div
                                class="group relative rounded-2xl border border-white/10 bg-gradient-to-b from-gray-900 to-gray-950 p-5 shadow-lg transition-all duration-300 hover:border-red-500/50 hover:shadow-red-950/20">

                                <!-- Header Info: Date & Theater -->
                                <div class="flex items-start justify-between border-b border-white/5 pb-3">
                                    <div>
                                        <h4 class="font-bold text-white text-lg group-hover:text-red-400 transition-colors">
                                            {{ $show->theater_name }}
                                        </h4>
                                        <p class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            {{ $show->screen_name }}
                                        </p>
                                    </div>
                                    <span
                                        class="px-2.5 py-1 rounded-lg bg-white/5 text-xs font-semibold text-gray-300 border border-white/5">
                                        {{ \Illuminate\Support\Carbon::parse($show->show_date)->format('D, M j') }}
                                    </span>
                                </div>

                                <!-- Show Time Details & Availability -->
                                <div class="mt-4 flex items-center justify-between">
                                    <div>
                                        <span class="text-2xl font-black text-white tracking-tight">
                                            {{ \Illuminate\Support\Carbon::parse($show->show_time)->format('h:i') }}
                                        </span>
                                        <span class="text-xs font-bold text-gray-400 uppercase ml-0.5">
                                            {{ \Illuminate\Support\Carbon::parse($show->show_time)->format('A') }}
                                        </span>
                                    </div>

                                    <div class="text-right">
                                        <p class="mb-1 text-xs font-black text-amber-300">
                                            From PKR {{ number_format($movie->cardPrice()) }}
                                        </p>
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md {{ $show->available_seats < 15 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                            <span
                                                class="w-1.5 h-1.5 rounded-full {{ $show->available_seats < 15 ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                                            {{ $show->available_seats }} seats left
                                        </span>
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <a href="{{ route('movies.seats', ['slug' => $movie->slug, 'show' => $show->show_id]) }}"
                                    class="mt-5 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-red-600/90 py-2.5 text-xs font-bold text-white hover:bg-red-500 transition-colors shadow-sm">
                                    Select Seats
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="rounded-2xl border border-dashed border-white/10 bg-gray-900/50 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-base font-bold text-white">No Upcoming Shows Available</h3>
                        <p class="mt-2 text-sm text-gray-400 max-w-md mx-auto">There are currently no showtimes scheduled for
                            this movie. Add it to your wishlist to get notified when tickets open!</p>
                    </div>
                @endif
            </div>

            <!-- TAB 2: OVERVIEW -->
            <div x-show="activeTab === 'overview'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-2xl border border-white/10 bg-gray-900/40 p-6 md:p-8 backdrop-blur-sm">
                        <h3 class="text-xl font-bold text-white mb-4">Synopsis</h3>
                        <p class="text-gray-300 leading-relaxed text-base font-normal">
                            {{ $movie->description ?: 'No detailed synopsis available for this title.' }}
                        </p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-white/10 bg-gray-900/40 p-6 backdrop-blur-sm">
                        <h3 class="text-lg font-bold text-white mb-4 border-b border-white/10 pb-3">Movie Details</h3>
                        <dl class="space-y-4 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Language</dt>
                                <dd class="font-semibold text-gray-200">{{ $movie->language }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Duration</dt>
                                <dd class="font-semibold text-gray-200">{{ intdiv($movie->duration_minutes, 60) }}h
                                    {{ $movie->duration_minutes % 60 }}m</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Rating Certificate</dt>
                                <dd class="font-semibold text-gray-200">{{ $movie->certificate_rating ?: 'NR' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Genre</dt>
                                <dd class="font-semibold text-gray-200">{{ $movie->genres->pluck('name')->join(', ') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Base Price</dt>
                                <dd class="font-semibold text-gray-200">PKR {{ number_format((float) $movie->base_price) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Sale Price</dt>
                                <dd class="font-semibold text-amber-300">PKR {{ number_format($movie->cardPrice()) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="lg:col-span-3 rounded-2xl border border-white/10 bg-gray-900/40 p-6 md:p-8 backdrop-blur-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
                        <h3 class="text-xl font-bold text-white">Database Reviews</h3>
                        <span class="text-sm font-bold text-amber-300">{{ number_format($movie->displayRating(), 1) }} / 5</span>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @forelse($movie->reviews as $review)
                            <article class="rounded-lg border border-white/10 bg-gray-950 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-bold text-white">{{ $review->user?->name ?? 'Movie fan' }}</p>
                                    <span class="text-sm font-black text-amber-300">{{ $review->rating }} stars</span>
                                </div>
                                @if($review->review_text)
                                    <p class="mt-3 text-sm leading-6 text-gray-300">{{ $review->review_text }}</p>
                                @endif
                            </article>
                        @empty
                            <p class="text-sm text-gray-400">No approved reviews are available yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </main>

        <!-- Trailer Video Modal -->
        @if($movie->trailer_url)
            <div x-show="trailerOpen" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
                @keydown.escape.window="trailerOpen = false">
                <div class="relative w-full max-w-4xl bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/10"
                    @click.away="trailerOpen = false">
                    <button @click="trailerOpen = false"
                        class="absolute top-4 right-4 z-10 text-gray-400 hover:text-white bg-black/50 p-2 rounded-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="aspect-video w-full">
                        <iframe class="w-full h-full" src="{{ $movie->trailer_url }}" title="{{ $movie->title }} Trailer"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
