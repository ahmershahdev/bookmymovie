@extends('layouts.app')

@section('title', 'Compare Movies | BookMyMovie')

@push('styles')
    <style nonce="{{ $cspNonce ?? '' }}">
        .mouse-track-card {
            --mouse-x: 50%;
            --mouse-y: 50%;
            --rx: 0deg;
            --ry: 0deg;
            transform-style: preserve-3d;
            transition: transform 0.15s ease-out, box-shadow 0.3s ease;
            will-change: transform;
        }

        .mouse-track-card:hover {
            transform: perspective(1000px) rotateX(var(--rx)) rotateY(var(--ry)) scale3d(1.01, 1.01, 1.01);
        }

        .card-3d-child {
            transform: translateZ(25px);
            transition: transform 0.2s ease-out;
        }

        .mouse-track-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(
                600px circle at var(--mouse-x) var(--mouse-y),
                rgba(239, 68, 68, 0.12),
                transparent 45%
            );
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 10;
        }

        .mouse-track-card:hover::before {
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    <section class="relative min-h-screen overflow-hidden bg-gray-950 px-4 pb-20 pt-32 sm:px-6 lg:px-8">
        {{-- Background Accents --}}
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-red-950/20 via-gray-950 to-gray-950"></div>
        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#1f29370f_1px,transparent_1px),linear-gradient(to_bottom,#1f29370f_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>

        <div class="relative mx-auto max-w-7xl">
            {{-- Header --}}
            <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-red-400 backdrop-blur-md">
                        <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                        Decision Engine
                    </div>
                    <h1 class="mt-4 text-3xl font-black text-white sm:text-4xl lg:text-5xl">
                        Compare <span class="bg-gradient-to-r from-white via-gray-200 to-red-400 bg-clip-text text-transparent">Movies Side-by-Side</span>
                    </h1>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-gray-400">
                        Evaluate runtimes, ratings, certificates, and ticket pricing to make an informed choice before picking your seats.
                    </p>
                </div>

                <a href="{{ route('movies.index') }}" 
                   class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-5 py-3 text-xs font-bold text-white backdrop-blur-sm transition hover:border-red-500/40 hover:bg-red-950/30">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span>Browse More Movies</span>
                </a>
            </div>

            @if(isset($movies) && $movies->count() > 0)
                {{-- Side-by-Side Modern Grid Matrix (Desktop / Tablet) --}}
                <div class="mt-12 hidden md:grid grid-cols-1 gap-6 lg:grid-cols-{{ min(count($movies), 4) }}">
                    @foreach($movies as $movie)
                        @php
                            $rating = (float) $movie->average_rating;
                            $hours = intdiv($movie->duration_minutes, 60);
                            $mins = $movie->duration_minutes % 60;
                            $price = $movie->cardPrice();
                        @endphp
                        <article class="mouse-track-card relative flex flex-col justify-between overflow-hidden rounded-2xl border border-white/10 bg-gray-900/80 p-5 shadow-xl backdrop-blur-md">
                            <div class="card-3d-child flex flex-col h-full">
                                {{-- Movie Poster Header --}}
                                <div class="relative aspect-[2/3] w-full overflow-hidden rounded-xl bg-gray-950 border border-white/10">
                                    <img src="{{ $movie->poster_image ? asset(ltrim($movie->poster_image, '/')) : asset('images/logo.png') }}" 
                                         alt="{{ $movie->title }}" 
                                         class="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                                         loading="lazy">

                                    {{-- Certificate Badge --}}
                                    <span class="absolute top-3 left-3 rounded-lg border border-white/20 bg-black/60 px-2.5 py-1 text-[10px] font-black uppercase text-white backdrop-blur-md">
                                        {{ $movie->certificate_rating ?? 'NR' }}
                                    </span>

                                    {{-- Status Badge --}}
                                    <span class="absolute top-3 right-3 rounded-lg bg-red-600/90 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white backdrop-blur-md shadow-lg shadow-red-950/50">
                                        {{ str($movie->status)->replace('_', ' ')->headline() }}
                                    </span>
                                </div>

                                {{-- Title & Category --}}
                                <div class="mt-4 border-b border-white/10 pb-4">
                                    <h2 class="text-lg font-black text-white line-clamp-1" title="{{ $movie->title }}">
                                        {{ $movie->title }}
                                    </h2>
                                    <p class="mt-1 text-xs text-gray-400 line-clamp-1">
                                        {{ $movie->genres->pluck('name')->join(' / ') ?: 'Cinema' }}
                                    </p>
                                </div>

                                {{-- Specs Comparison List --}}
                                <div class="my-4 flex-1 space-y-4">
                                    {{-- Rating Spec --}}
                                    <div class="rounded-xl border border-white/5 bg-black/30 p-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Rating</span>
                                        <div class="mt-1 flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 text-amber-400">
                                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                <span class="text-sm font-black text-white">{{ number_format($rating, 1) }}</span>
                                                <span class="text-[10px] text-gray-500">/ 5</span>
                                            </div>
                                            @if($rating >= 4.5)
                                                <span class="rounded bg-emerald-500/10 px-2 py-0.5 text-[10px] font-extrabold text-emerald-400 border border-emerald-500/20">Highly Rated</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Duration Spec --}}
                                    <div class="rounded-xl border border-white/5 bg-black/30 p-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Runtime</span>
                                        <div class="mt-1 flex items-center justify-between">
                                            <div class="flex items-center gap-1.5 text-gray-300">
                                                <svg class="h-4 w-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span class="text-sm font-bold text-white">{{ $hours }}h {{ $mins }}m</span>
                                            </div>
                                            <span class="text-[10px] text-gray-500">{{ $movie->duration_minutes }} mins</span>
                                        </div>
                                    </div>

                                    {{-- Price Spec --}}
                                    <div class="rounded-xl border border-white/5 bg-black/30 p-3">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Starting Price</span>
                                        <div class="mt-1 flex items-center justify-between">
                                            <span class="text-base font-black text-red-400">PKR {{ number_format($price) }}</span>
                                            <span class="text-[10px] text-gray-500">Per Ticket</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Action Button --}}
                                <a href="{{ route('movies.show', $movie->slug) }}" 
                                   class="mt-auto flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 py-3 text-xs font-black text-white shadow-lg shadow-red-950/40 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
                                    <span>View Details & Seats</span>
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Responsive Mobile Table Fallback --}}
                <div class="mt-8 block md:hidden overflow-hidden rounded-2xl border border-white/10 bg-gray-900 shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-300">
                            <thead class="bg-gray-950 text-[10px] font-black uppercase tracking-widest text-red-300 border-b border-white/10">
                                <tr>
                                    <th class="p-4">Movie</th>
                                    <th class="p-4">Rating</th>
                                    <th class="p-4">Runtime</th>
                                    <th class="p-4">Price</th>
                                    <th class="p-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach($movies as $movie)
                                    <tr class="hover:bg-white/5 transition">
                                        <td class="p-4 font-bold text-white">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ $movie->poster_image ? asset(ltrim($movie->poster_image, '/')) : asset('images/logo.png') }}" 
                                                     class="h-10 w-7 rounded object-cover border border-white/10" 
                                                     alt="{{ $movie->title }}">
                                                <div>
                                                    <p class="font-black text-white text-xs">{{ $movie->title }}</p>
                                                    <p class="text-[10px] text-gray-500">{{ $movie->certificate_rating ?? 'NR' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 font-extrabold text-amber-400">
                                            Star {{ number_format((float) $movie->average_rating, 1) }}
                                        </td>
                                        <td class="p-4 text-xs">
                                            {{ intdiv($movie->duration_minutes, 60) }}h {{ $movie->duration_minutes % 60 }}m
                                        </td>
                                        <td class="p-4 font-black text-red-400 text-xs">
                                            PKR {{ number_format($movie->cardPrice()) }}
                                        </td>
                                        <td class="p-4 text-right">
                                            <a href="{{ route('movies.show', $movie->slug) }}" 
                                               class="inline-flex rounded-lg bg-red-600/80 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-red-500">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                {{-- Empty State --}}
                <div class="mt-12 rounded-2xl border border-white/10 bg-gray-900/60 p-12 text-center backdrop-blur-md">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border border-red-500/20 bg-red-500/10 text-red-400">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    </div>
                    <h3 class="mt-4 text-lg font-black text-white">No Movies Selected for Comparison</h3>
                    <p class="mt-2 text-xs text-gray-400">Select movies from the catalog to compare their ratings, runtime, and prices side-by-side.</p>
                    <a href="{{ route('movies.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-3 text-xs font-black text-white hover:bg-red-500">
                        Explore Movies Catalog
                    </a>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', () => {
            const trackCards = document.querySelectorAll('.mouse-track-card');

            trackCards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);

                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / centerY) * -8;
                    const rotateY = ((x - centerX) / centerX) * 8;

                    card.style.setProperty('--rx', `${rotateX.toFixed(2)}deg`);
                    card.style.setProperty('--ry', `${rotateY.toFixed(2)}deg`);
                });

                card.addEventListener('mouseleave', () => {
                    card.style.setProperty('--rx', '0deg');
                    card.style.setProperty('--ry', '0deg');
                });
            });
        });
    </script>
@endpush
