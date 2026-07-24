@extends('layouts.app')

@section('title', 'Search | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <x-section-heading eyebrow="Search" title="Find movies fast"
                description="Search by movie, genre, or language." />
            <div class="mt-8"><x-search-bar :movies="$navMovies" size="large" /></div>
            <div class="mt-8 grid gap-4">
                @forelse($results as $movie)
                    <a href="{{ route('movies.show', $movie['slug']) }}"
                        class="rounded-lg border border-white/10 bg-gray-900 p-5 font-bold text-white hover:border-red-500/50">
                        {{ $movie['title'] }}
                        <span class="block pt-1 text-sm font-normal text-gray-400">{{ $movie['genre'] }} - {{ $movie['language'] }}</span>
                    </a>
                @empty
                    <p class="rounded-lg border border-white/10 bg-gray-900 p-5 text-gray-300">No movies found for "{{ $query }}".</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
