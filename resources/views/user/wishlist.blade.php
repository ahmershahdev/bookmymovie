@extends('layouts.app')

@section('title', 'Wishlist | BookMyMovie')
@section('meta_description', 'View saved BookMyMovie titles, remove wishlist movies, or continue to seat selection and booking.')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <x-section-heading eyebrow="Wishlist" title="Saved movies"
                description="Movies saved to your account." />
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($wishlistMovies as $movie)
                    <article class="flex flex-col overflow-hidden rounded-lg border border-white/10 bg-gray-900">
                        <div class="relative aspect-[9/16] overflow-hidden bg-gray-950">
                            @if(!empty($movie['poster_url']))
                                <img src="{{ $movie['poster_url'] }}" alt="{{ $movie['title'] }}"
                                    class="h-full w-full object-cover">
                            @else
                                <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-contain p-10 opacity-40">
                            @endif
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-gray-950 to-transparent p-4">
                                <p class="text-xs font-bold uppercase text-red-300">{{ $movie['genre'] }}</p>
                                <h2 class="mt-1 text-xl font-black text-white">{{ $movie['title'] }}</h2>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-400">
                                <span>{{ $movie['language'] }}</span>
                                <span>{{ $movie['duration'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <x-star-rating :rating="$movie['rating']" />
                                <span class="text-sm font-black text-gold">PKR {{ number_format($movie['price']) }}</span>
                            </div>

                            <div class="mt-auto grid gap-2">
                                <a href="{{ route('movies.show', $movie['slug']) }}"
                                    class="rounded-md bg-red-600 px-4 py-2.5 text-center text-xs font-black uppercase tracking-wide text-white hover:bg-red-500">
                                    Details
                                </a>
                                <a href="{{ !empty($movie['first_show_id']) ? route('movies.seats', ['slug' => $movie['slug'], 'show' => $movie['first_show_id']]) : route('movies.show', $movie['slug']) }}"
                                    class="rounded-md border border-white/10 bg-white/5 px-4 py-2.5 text-center text-xs font-black uppercase tracking-wide text-white hover:border-red-400/60 hover:bg-white/10">
                                    Add to cart
                                </a>
                                <form method="POST" action="{{ route('user.wishlist.remove', $movie['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="w-full rounded-md border border-red-500/30 bg-red-950/30 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-red-100 hover:bg-red-900/50">
                                        Remove from wishlist
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-lg border border-white/10 bg-gray-900 p-6 text-gray-300">Your wishlist is empty.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
