@props(['movies' => []])

<header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-gray-950/90 backdrop-blur-xl">
    <nav class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8" aria-label="Primary">
        <a href="{{ route('home') }}"
            class="flex shrink-0 items-center gap-3 rounded-md focus:outline-none focus:ring-2 focus:ring-red-400">
            <img src="{{ asset('images/header-logo-3.png') }}" alt="BookMyMovie logo"
                class="h-11 w-11 rounded-md object-cover ring-1 ring-red-500/30">
            <span class="hidden text-lg font-black tracking-normal sm:inline">
                <span class="brand-gradient">BookMyMovie</span>
            </span>
        </a>

        <div class="hidden flex-1 justify-center lg:flex">
            <x-search-bar :movies="$movies" />
        </div>

        <div class="ml-auto flex items-center gap-2">
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @keydown.escape.window="open = false"
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-bold text-white transition hover:border-red-400/60 hover:bg-red-950/30 focus:outline-none focus:ring-2 focus:ring-red-400"
                    :aria-expanded="open.toString()">
                    Explore
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-cloak x-show="open" x-transition @click.outside="open = false"
                    class="absolute right-0 mt-3 w-80 rounded-lg border border-white/10 bg-gray-900 p-3 shadow-2xl shadow-black/60">
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <a href="{{ route('movies.index') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">Movies</a>
                        <a href="{{ route('movies.compare') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">Compare</a>
                        <a href="{{ route('about') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">About</a>
                        <a href="{{ route('contact') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">Contact</a>
                        <a href="{{ route('faq') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">FAQ</a>
                        <a href="{{ route('eticket.info') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">E-Ticket</a>
                        <a href="{{ route('terms') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">Terms</a>
                        <a href="{{ route('privacy') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">Privacy</a>
                        <a href="{{ route('refund') }}"
                            class="rounded-md p-3 font-semibold text-gray-200 hover:bg-red-950/40 hover:text-white">Refund</a>
                    </div>
                </div>
            </div>

            <x-icon-button id="wishlist-icon" label="Wishlist" href="{{ route('user.wishlist') }}"
                counter="wishlistCount">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    aria-hidden="true">
                    <path
                        d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z" />
                </svg>
            </x-icon-button>
            <x-icon-button id="cart-icon" label="Cart" href="{{ route('user.cart') }}" counter="cartCount">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    aria-hidden="true">
                    <path d="M6 6h15l-1.5 9h-12z" />
                    <path d="M6 6L5 3H2" />
                    <circle cx="9" cy="20" r="1.5" />
                    <circle cx="18" cy="20" r="1.5" />
                </svg>
            </x-icon-button>

            <a href="{{ route('user.login') }}"
                class="hidden rounded-full bg-red-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-red-950/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400 sm:inline-flex">Login</a>
        </div>
    </nav>

    <div class="border-t border-white/10 px-4 py-3 lg:hidden">
        <x-search-bar :movies="$movies" />
    </div>
</header>