@props(['movie'])

<article data-fly-source
    class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-gray-950/90 shadow-2xl shadow-black/80 backdrop-blur-xl transition-all duration-500 hover:-translate-y-2 hover:border-red-500/50 hover:shadow-[0_20px_50px_rgba(220,38,38,0.25)]">

    <!-- 9:16 Vertical Poster Container -->
    <div
        class="relative aspect-[9/16] w-full overflow-hidden bg-gradient-to-br {{ $movie['gradient'] ?? 'from-gray-900 via-gray-950 to-black' }}">

        <!-- Real Poster Image (If Available) or Fallback Gradient + Logo -->
        @if(!empty($movie['poster']) || !empty($movie['image']))
            <img src="{{ asset($movie['poster'] ?? $movie['image']) }}" alt="{{ $movie['title'] }}"
                class="h-full w-full object-cover object-center transition-transform duration-700 ease-out group-hover:scale-110">
        @else
            <!-- Fallback Ambient Backdrop -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,0.18),transparent_60%)]"></div>
            <img src="{{ asset('images/logo.png') }}" alt=""
                class="absolute left-1/2 top-1/3 h-40 w-40 -translate-x-1/2 -translate-y-1/2 rounded-full object-cover opacity-20 blur-[0.5px] transition-transform duration-700 group-hover:scale-110">
        @endif

        <!-- Cinematic Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/40 to-transparent"></div>

        <!-- Top Floating Status Badge -->
        <div class="absolute left-3 top-3 z-10">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-red-500/30 bg-gray-950/80 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-red-400 backdrop-blur-md shadow-md">
                <span class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                {{ $movie['status'] }}
            </span>
        </div>

        <!-- Certificate / Rating Tag (Top Right) -->
        @if(!empty($movie['certificate']))
            <div class="absolute right-3 top-3 z-10">
                <span
                    class="rounded-lg border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold text-gray-200 backdrop-blur-md shadow-md">
                    {{ $movie['certificate'] }}
                </span>
            </div>
        @endif

        <!-- Poster Overlay Info (Title & Genre) -->
        <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-red-400/90">{{ $movie['genre'] }}</p>
            <h3
                class="mt-1 text-2xl font-black leading-snug tracking-tight text-white drop-shadow-md group-hover:text-red-400 transition-colors duration-300">
                {{ $movie['title'] }}
            </h3>
        </div>
    </div>

    <!-- Card Body & Details -->
    <div class="flex flex-1 flex-col justify-between space-y-4 p-4 sm:p-5">

        <!-- Metadata Pills (Language, Duration) -->
        <div class="flex items-center justify-between text-xs font-semibold text-gray-400 border-b border-white/5 pb-3">
            <span class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                </svg>
                {{ $movie['language'] }}
            </span>
            <span class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $movie['duration'] }}
            </span>
        </div>

        <!-- Rating & Pricing -->
        <div class="flex items-center justify-between gap-2">
            <x-star-rating :rating="$movie['rating']" />
            <span class="text-[11px] font-bold text-gray-400">{{ $movie['reviews'] ?? 0 }} reviews</span>
            <div class="text-right">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400">Sale Price</span>
                @if(!empty($movie['original_price']) && (float) $movie['original_price'] > (float) ($movie['price'] ?? 0))
                    <span class="block text-[11px] font-bold text-gray-500 line-through">PKR {{ number_format($movie['original_price']) }}</span>
                @endif
                <span class="text-base font-black text-amber-400 drop-shadow-sm">PKR {{ number_format($movie['price']) }}</span>
            </div>
        </div>

        <!-- Action Controls with Fly Animations -->
        <div class="grid grid-cols-[1fr_auto_auto] gap-2 pt-1">

            <!-- View Details CTA -->
            <a href="{{ route('movies.show', $movie['slug']) }}"
                class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-red-600 to-red-500 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-red-950/50 transition-all duration-300 hover:from-red-500 hover:to-red-400 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-red-400">
                Details
            </a>

            <!-- Wishlist Fly Button -->
            <form method="POST" action="{{ route('user.wishlist') }}">
                @csrf
                <input type="hidden" name="slug" value="{{ $movie['slug'] }}">
                <button type="submit" @click="fly($event, 'wishlist-icon', 'wishlistCount')"
                class="group/btn inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-gray-300 transition-all duration-300 hover:border-red-500/60 hover:bg-red-950/50 hover:text-red-400 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-400"
                aria-label="Add {{ $movie['title'] }} to wishlist">
                    <svg class="h-5 w-5 transition-transform duration-300 group-hover/btn:scale-110" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="1.8">
                        <path
                            d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z" />
                    </svg>
                </button>
            </form>

            <!-- Book Seats -->
            <a href="{{ !empty($movie['first_show_id']) ? route('movies.seats', ['slug' => $movie['slug'], 'show' => $movie['first_show_id']]) : route('movies.show', $movie['slug']) }}"
                class="group/btn inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-gray-300 transition-all duration-300 hover:border-red-500/60 hover:bg-red-950/50 hover:text-red-400 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-400"
                aria-label="Book seats for {{ $movie['title'] }}">
                <svg class="h-5 w-5 transition-transform duration-300 group-hover/btn:scale-110" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M6 6h15l-1.5 9h-12z" />
                    <path d="M6 6L5 3H2" />
                    <circle cx="9" cy="20" r="1.5" />
                    <circle cx="18" cy="20" r="1.5" />
                </svg>
            </a>

        </div>
    </div>
</article>
