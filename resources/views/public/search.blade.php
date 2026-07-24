@extends('layouts.app')

@section('title', isset($query) && $query ? "Search: '$query' | BookMyMovie" : 'Search Movies | BookMyMovie')

@section('content')
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <!-- Background Ambient Glow -->
        <div class="relative overflow-hidden pb-16 pt-32 sm:pb-24 sm:pt-40">
            <div class="pointer-events-none absolute -top-40 left-1/2 -z-10 -translate-x-1/2 blur-3xl" aria-hidden="true">
                <div class="h-[320px] w-[700px] bg-gradient-to-tr from-red-600/20 via-red-900/10 to-transparent opacity-60 sm:w-[900px]"></div>
            </div>

            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="text-center">
                    <x-section-heading 
                        eyebrow="Search" 
                        title="Find your next cinema experience"
                        description="Search across movies, genres, or preferred languages." 
                    />
                </div>

                <!-- Search Input Bar -->
                <div class="mx-auto mt-8 max-w-2xl">
                    <x-search-bar :movies="$navMovies" size="large" />
                </div>

                <!-- Results Section -->
                <div class="mt-12">
                    @if(!empty($query))
                        <div class="mb-6 flex items-center justify-between border-b border-white/10 pb-4">
                            <h2 class="text-sm font-medium tracking-wide text-gray-400">
                                Results for <span class="font-semibold text-white">"{{ $query }}"</span>
                            </h2>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-gray-300">
                                {{ count($results) }} {{ Str::plural('movie', count($results)) }}
                            </span>
                        </div>
                    @endif

                    <!-- Movies Grid -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        @forelse($results as $movie)
                            <a href="{{ route('movies.show', $movie['slug']) }}"
                                class="group relative flex items-center gap-4 overflow-hidden rounded-xl border border-white/10 bg-gray-900/60 p-4 transition-all duration-300 hover:-translate-y-1 hover:border-red-500/50 hover:bg-gray-900 hover:shadow-xl hover:shadow-red-500/10">

                                {{-- Poster / Thumbnail Fallback --}}
                                <div class="relative h-24 w-16 flex-shrink-0 overflow-hidden rounded-lg bg-gray-800/80 border border-white/5">
                                    @if(!empty($movie['poster']))
                                        <img src="{{ $movie['poster'] }}" alt="{{ $movie['title'] }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-gray-600">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Details --}}
                                <div class="flex flex-1 flex-col justify-between py-1">
                                    <div>
                                        <h3 class="text-base font-bold text-white transition-colors group-hover:text-red-400">
                                            {{ $movie['title'] }}
                                        </h3>

                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @if(!empty($movie['genre']))
                                                <span class="inline-flex items-center rounded-md border border-red-500/20 bg-red-500/10 px-2 py-0.5 text-xs font-medium text-red-400">
                                                    {{ $movie['genre'] }}
                                                </span>
                                            @endif

                                            @if(!empty($movie['language']))
                                                <span class="inline-flex items-center rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-xs font-medium text-gray-300">
                                                    {{ $movie['language'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-3 flex items-center gap-1 text-xs font-semibold text-red-500 opacity-90">
                                        <span>View details</span>
                                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <!-- Empty State -->
                            <div class="col-span-full rounded-2xl border border-white/10 bg-gray-900/40 p-12 text-center backdrop-blur-sm">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-base font-semibold text-white">No results found</h3>
                                <p class="mt-1 text-sm text-gray-400">
                                    We couldn't find any movies matching <span class="font-medium text-gray-200">"{{ $query ?? '' }}"</span>.
                                </p>
                                <p class="mt-2 text-xs text-gray-500">
                                    Try checking for typos or searching with different keywords like genre or language.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection