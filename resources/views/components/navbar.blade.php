@props(['movies' => []])

<header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-gray-950/85 backdrop-blur-2xl shadow-2xl shadow-black/50">
    <nav class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8" aria-label="Primary">
        
        <!-- Premium Bigger Logo -->
        <a href="{{ route('home') }}"
            class="group flex shrink-0 items-center gap-3.5 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-red-500/80">
            <div class="relative">
                <!-- Ambient Red Glow Effect behind Logo -->
                <div class="absolute -inset-1 rounded-xl bg-gradient-to-r from-red-600 to-red-800 opacity-40 blur-sm group-hover:opacity-80 transition duration-300"></div>
                <img src="{{ asset('images/header-logo-3.png') }}" alt="BookMyMovie logo"
                    class="relative h-14 w-14 rounded-xl object-cover ring-2 ring-red-500/50 shadow-lg shadow-red-950/60 transition group-hover:scale-105">
            </div>
            <span class="hidden text-xl font-black tracking-tight sm:inline">
                <span class="bg-gradient-to-r from-white via-red-200 to-red-500 bg-clip-text text-transparent drop-shadow-sm">{{ $siteSettings['site_name'] ?? 'BookMyMovie' }}</span>
            </span>
        </a>

        <!-- Search Bar (Desktop) -->
        <div class="hidden flex-1 justify-center px-4 lg:flex max-w-2xl mx-auto">
            <x-search-bar :movies="$movies" />
        </div>

        <!-- Right Side Actions -->
        <div class="ml-auto flex items-center gap-3">
            
            <!-- Explore Mega-Dropdown Menu -->
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @keydown.escape.window="open = false"
                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-bold text-white transition-all hover:border-red-500/60 hover:bg-red-950/30 hover:shadow-lg hover:shadow-red-950/40 focus:outline-none focus:ring-2 focus:ring-red-500"
                    :aria-expanded="open.toString()">
                    <span>Explore</span>
                    <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Detailed Structured Dropdown Menu -->
                <div x-cloak x-show="open" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                     @click.outside="open = false"
                    class="absolute right-0 mt-3 w-[480px] rounded-2xl border border-white/10 bg-gray-900/95 p-5 shadow-2xl shadow-black/90 backdrop-blur-2xl ring-1 ring-white/5">
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        
                        <!-- Column 1: Main Features & Support -->
                        <div class="space-y-1">
                            <p class="px-3 text-xs font-black uppercase tracking-wider text-red-400/90 mb-2">Navigation</p>
                            
                            <a href="{{ route('movies.index') }}"
                                class="group flex items-center gap-3 rounded-xl p-2.5 transition hover:bg-white/5 hover:translate-x-1">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-600/10 text-red-500 group-hover:bg-red-600 group-hover:text-white transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-100 group-hover:text-red-400 transition">Movies</div>
                                    <div class="text-[11px] text-gray-400">Browse current shows</div>
                                </div>
                            </a>

                            <a href="{{ route('movies.compare') }}"
                                class="group flex items-center gap-3 rounded-xl p-2.5 transition hover:bg-white/5 hover:translate-x-1">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-600/10 text-red-500 group-hover:bg-red-600 group-hover:text-white transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-100 group-hover:text-red-400 transition">Compare</div>
                                    <div class="text-[11px] text-gray-400">Side-by-side analysis</div>
                                </div>
                            </a>

                            <a href="{{ route('eticket.info') }}"
                                class="group flex items-center gap-3 rounded-xl p-2.5 transition hover:bg-white/5 hover:translate-x-1">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-600/10 text-red-500 group-hover:bg-red-600 group-hover:text-white transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-100 group-hover:text-red-400 transition">E-Ticket</div>
                                    <div class="text-[11px] text-gray-400">Digital pass guide</div>
                                </div>
                            </a>

                            <a href="{{ route('about') }}"
                                class="group flex items-center gap-3 rounded-xl p-2.5 transition hover:bg-white/5 hover:translate-x-1">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/5 text-gray-400 group-hover:bg-red-600 group-hover:text-white transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-100 group-hover:text-red-400 transition">About Us</div>
                                    <div class="text-[11px] text-gray-400">Our cinema story</div>
                                </div>
                            </a>
                        </div>

                        <!-- Column 2: Legal & Support -->
                        <div class="space-y-1">
                            <p class="px-3 text-xs font-black uppercase tracking-wider text-gray-400/90 mb-2">Support & Legal</p>

                            <a href="{{ route('faq') }}"
                                class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-gray-300 hover:bg-white/5 hover:text-white transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                FAQ & Help
                            </a>

                            <a href="{{ route('contact') }}"
                                class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-gray-300 hover:bg-white/5 hover:text-white transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                Contact Support
                            </a>

                            <a href="{{ route('terms') }}"
                                class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-gray-300 hover:bg-white/5 hover:text-white transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-500"></span>
                                Terms of Service
                            </a>

                            <a href="{{ route('privacy') }}"
                                class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-gray-300 hover:bg-white/5 hover:text-white transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-500"></span>
                                Privacy Policy
                            </a>

                            <a href="{{ route('refund') }}"
                                class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-gray-300 hover:bg-white/5 hover:text-white transition">
                                <span class="h-1.5 w-1.5 rounded-full bg-gray-500"></span>
                                Refund Policy
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <x-icon-button id="wishlist-icon" label="Wishlist" href="{{ route('user.wishlist') }}" counter="wishlistCount">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z" />
                </svg>
            </x-icon-button>

            <x-icon-button id="cart-icon" label="Cart" href="{{ route('user.cart') }}" counter="cartCount">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M6 6h15l-1.5 9h-12z" />
                    <path d="M6 6L5 3H2" />
                    <circle cx="9" cy="20" r="1.5" />
                    <circle cx="18" cy="20" r="1.5" />
                </svg>
            </x-icon-button>

            @auth
                <div class="relative" x-data="{ accountOpen: false }">
                    <button type="button" @click="accountOpen = !accountOpen" @keydown.escape.window="accountOpen = false"
                        class="hidden items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-black text-white transition hover:border-red-500/60 hover:bg-red-950/30 focus:outline-none focus:ring-2 focus:ring-red-400 sm:inline-flex">
                        @if(auth()->user()->profile_picture)
                            <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}" alt=""
                                class="h-7 w-7 rounded-full object-cover ring-1 ring-red-400/40">
                        @else
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-600 text-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        @endif
                        Account
                    </button>

                    <div x-cloak x-show="accountOpen" @click.outside="accountOpen = false"
                        x-transition
                        class="absolute right-0 mt-3 w-56 rounded-xl border border-white/10 bg-gray-900/95 p-2 shadow-2xl shadow-black/80 backdrop-blur-xl">
                        <a href="{{ route('user.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-bold text-gray-200 hover:bg-white/5 hover:text-white">Account page</a>
                        <a href="{{ route('user.profile') }}" class="block rounded-lg px-3 py-2 text-sm font-bold text-gray-200 hover:bg-white/5 hover:text-white">Profile</a>
                        <a href="{{ route('user.bookings') }}" class="block rounded-lg px-3 py-2 text-sm font-bold text-gray-200 hover:bg-white/5 hover:text-white">Order history</a>
                        <form method="POST" action="{{ route('user.logout') }}" class="mt-1 border-t border-white/10 pt-1">
                            @csrf
                            <button class="w-full rounded-lg px-3 py-2 text-left text-sm font-bold text-red-300 hover:bg-red-950/40 hover:text-red-100">Logout</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('user.login') }}"
                    class="hidden rounded-full bg-gradient-to-r from-red-600 to-red-500 px-5 py-2 text-sm font-black text-white shadow-lg shadow-red-950/50 transition hover:from-red-500 hover:to-red-400 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-400 sm:inline-flex">
                    Login
                </a>
            @endauth
        </div>
    </nav>

    <!-- Mobile Search Bar Container -->
    <div class="border-t border-white/10 px-4 py-3 lg:hidden">
        <x-search-bar :movies="$movies" />
    </div>
</header>
