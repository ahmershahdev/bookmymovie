@extends('layouts.app')

@section('title', $movie->title . ' | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[360px_1fr]">
            <div
                class="rounded-lg border border-white/10 bg-gradient-to-br from-red-950 via-gray-950 to-black p-6 shadow-2xl shadow-black/40">
                <img src="{{ $movie->poster_image ? asset(ltrim($movie->poster_image, '/')) : asset('images/logo.png') }}"
                    alt="{{ $movie->title }} poster" class="mx-auto h-64 w-64 rounded-lg object-cover opacity-80">
            </div>
            <div>
                <p class="text-sm font-black uppercase tracking-[.22em] text-red-400">{{ str($movie->status)->replace('_', ' ')->headline() }}</p>
                <h1 class="mt-3 text-5xl font-black text-white">{{ $movie->title }}</h1>
                <p class="mt-5 max-w-3xl text-gray-300">{{ $movie->description ?: 'Premium cinema experience.' }}</p>
                <div class="mt-6 flex flex-wrap gap-3 text-sm font-bold text-gray-200">
                    <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">{{ $movie->language }}</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">{{ intdiv($movie->duration_minutes, 60) }}h {{ $movie->duration_minutes % 60 }}m</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">{{ $movie->certificate_rating }}</span>
                    <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2">{{ $movie->genres->pluck('name')->join(' / ') }}</span>
                    <x-star-rating :rating="$movie->average_rating ?: 4" />
                </div>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if($shows->isNotEmpty())
                        <a href="{{ route('movies.seats', ['slug' => $movie->slug, 'show' => $shows->first()->show_id]) }}"
                            class="rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white hover:bg-red-500">Book
                            seats</a>
                    @endif
                    <form method="POST" action="{{ route('user.wishlist') }}">
                        @csrf
                        <input type="hidden" name="movie_id" value="{{ $movie->id }}">
                        <button class="rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-black text-white hover:bg-red-950/40">Wishlist</button>
                    </form>
                    <a href="{{ route('movies.compare') }}"
                        class="rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-black text-white hover:bg-red-950/40">Compare</a>
                </div>
                <div class="mt-10 grid gap-4 md:grid-cols-3">
                    @forelse($shows as $show)
                        <a href="{{ route('movies.seats', ['slug' => $movie->slug, 'show' => $show->show_id]) }}"
                            class="rounded-lg border border-red-500/20 bg-red-950/20 p-4 font-bold text-white hover:border-red-400">
                            {{ \Illuminate\Support\Carbon::parse($show->show_date)->format('D, M j') }}
                            {{ \Illuminate\Support\Carbon::parse($show->show_time)->format('h:i A') }}
                            <span class="block text-xs text-gray-300">{{ $show->theater_name }} - {{ $show->screen_name }} - {{ $show->available_seats }} seats</span>
                        </a>
                    @empty
                        <p class="rounded-lg border border-white/10 bg-gray-900 p-5 text-gray-300">No upcoming shows available.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
