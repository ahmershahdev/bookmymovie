@extends('layouts.app')

@section('title', 'Wishlist | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <x-section-heading eyebrow="Wishlist" title="Saved movies"
                description="Movies saved to your account." />
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($wishlistMovies as $movie)
                    <x-movie-card :movie="$movie" />
                @empty
                    <p class="rounded-lg border border-white/10 bg-gray-900 p-6 text-gray-300">Your wishlist is empty.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
