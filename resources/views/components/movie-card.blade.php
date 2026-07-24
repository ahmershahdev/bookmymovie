@props(['movie'])

<article data-fly-source
    class="group overflow-hidden rounded-lg border border-white/10 bg-gray-950 shadow-2xl shadow-black/30 transition hover:-translate-y-1 hover:border-red-500/50">
    <div class="relative aspect-[3/4] overflow-hidden bg-gradient-to-br {{ $movie['gradient'] }}">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,.22),transparent_30%),linear-gradient(180deg,transparent,rgba(3,7,18,.95))]">
        </div>
        <img src="{{ asset('images/logo.png') }}" alt=""
            class="absolute left-1/2 top-10 h-36 w-36 -translate-x-1/2 rounded-full object-cover opacity-25 transition group-hover:scale-105">
        <div class="absolute inset-x-0 bottom-0 p-5">
            <p class="text-xs font-black uppercase tracking-[.18em] text-red-200">{{ $movie['status'] }}</p>
            <h3 class="mt-2 text-2xl font-black leading-tight text-white">{{ $movie['title'] }}</h3>
            <p class="mt-2 text-sm text-gray-300">{{ $movie['genre'] }}</p>
        </div>
    </div>

    <div class="space-y-4 p-4">
        <div class="flex items-center justify-between gap-3 text-sm text-gray-300">
            <span>{{ $movie['language'] }}</span>
            <span>{{ $movie['duration'] }}</span>
            <span>{{ $movie['certificate'] }}</span>
        </div>

        <div class="flex items-center justify-between">
            <x-star-rating :rating="$movie['rating']" />
            <p class="text-sm font-black text-gold">PKR {{ number_format($movie['price']) }}</p>
        </div>

        <div class="grid grid-cols-[1fr_auto_auto] gap-2">
            <a href="{{ route('movies.show', $movie['slug']) }}"
                class="inline-flex items-center justify-center rounded-full bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">Details</a>
            <button type="button" @click="fly($event, 'wishlist-icon', 'wishlistCount')"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-red-200 transition hover:border-red-400/60 hover:bg-red-950/40 focus:outline-none focus:ring-2 focus:ring-red-400"
                aria-label="Add {{ $movie['title'] }} to wishlist">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    aria-hidden="true">
                    <path
                        d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z" />
                </svg>
            </button>
            <button type="button" @click="fly($event, 'cart-icon', 'cartCount')"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-red-200 transition hover:border-red-400/60 hover:bg-red-950/40 focus:outline-none focus:ring-2 focus:ring-red-400"
                aria-label="Add {{ $movie['title'] }} to cart">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    aria-hidden="true">
                    <path d="M6 6h15l-1.5 9h-12z" />
                    <path d="M6 6L5 3H2" />
                    <circle cx="9" cy="20" r="1.5" />
                    <circle cx="18" cy="20" r="1.5" />
                </svg>
            </button>
        </div>
    </div>
</article>