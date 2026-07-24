@extends('layouts.app')

@section('title', $siteSettings['site_name'] ?? 'BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-32 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            @if(! empty($slides))
                <x-hero-carousel :slides="$slides" />
            @else
                <div class="rounded-lg border border-white/10 bg-gray-900 px-6 py-24 text-center">
                    <p class="text-sm font-black uppercase tracking-[.22em] text-red-400">{{ $siteSettings['site_name'] ?? 'BookMyMovie' }}</p>
                    <h1 class="mt-4 text-5xl font-black text-white">Book movie seats faster</h1>
                    <p class="mx-auto mt-4 max-w-xl text-gray-300">Connect the movie database to show live listings.</p>
                </div>
            @endif

            <div class="mt-8">
                <x-sales-ticker :messages="$tickerMessages" />
            </div>

            @forelse($movieCategories as $category)
                <div class="mt-12" id="{{ $category['slug'] }}">
                    <x-section-heading eyebrow="Friend Category" :title="$category['name']"
                        description="Four database-seeded movies for this friend type, with live ratings and sale pricing." />
                    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($category['movies'] as $movie)
                            <x-movie-card :movie="$movie" />
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="mt-10 rounded-lg border border-white/10 bg-gray-900 p-6 text-gray-300">
                    No movies found. Run the database migrations and seeders to load the cinema catalog.
                </div>
            @endforelse
        </div>
    </section>
@endsection
