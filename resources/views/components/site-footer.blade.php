<footer class="border-t border-white/10 bg-black">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-[1.2fr_2fr]">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="BookMyMovie logo"
                        class="h-14 w-14 rounded-md object-cover ring-1 ring-red-500/30">
                    <span class="text-2xl font-black"><span class="brand-gradient">BookMyMovie</span></span>
                </a>
                <p class="mt-5 max-w-md text-sm leading-6 text-gray-400">Premium movie discovery and seat booking for
                    Pakistani cinema audiences, starting with Karachi and Lahore.</p>
                <div class="mt-6 grid gap-3 text-sm text-gray-300">
                    <a href="https://ahmershah.dev" target="_blank" rel="noopener noreferrer"
                        class="group inline-flex w-max items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-2 font-bold text-gray-200 transition hover:border-red-400/60 hover:bg-red-950/30 hover:text-white">
                        <svg class="h-4 w-4 text-red-300 transition group-hover:text-red-200" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M3 12h18" />
                            <path d="M12 3c2.2 2.45 3.3 5.45 3.3 9S14.2 18.55 12 21" />
                            <path d="M12 3c-2.2 2.45-3.3 5.45-3.3 9S9.8 18.55 12 21" />
                        </svg>
                        Website
                    </a>
                    <a href="https://www.linkedin.com/in/syedahmershah" target="_blank" rel="noopener noreferrer"
                        class="group inline-flex w-max items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-2 font-bold text-gray-200 transition hover:border-red-400/60 hover:bg-red-950/30 hover:text-white">
                        <svg class="h-4 w-4 text-red-300 transition group-hover:text-red-200" viewBox="0 0 24 24"
                            fill="currentColor" aria-hidden="true">
                            <path d="M4.98 3.5C4.98 4.88 3.86 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.4 8h4.2v14H.4V8zM8 8h4.02v1.92h.06c.56-1.06 1.94-2.18 3.99-2.18 4.27 0 5.06 2.81 5.06 6.46V22h-4.2v-6.92c0-1.65-.03-3.78-2.3-3.78-2.31 0-2.66 1.8-2.66 3.66V22H8V8z" />
                        </svg>
                        LinkedIn
                    </a>
                    <a href="https://github.com/ahmershahdev" target="_blank" rel="noopener noreferrer"
                        class="group inline-flex w-max items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-2 font-bold text-gray-200 transition hover:border-red-400/60 hover:bg-red-950/30 hover:text-white">
                        <svg class="h-4 w-4 text-red-300 transition group-hover:text-red-200" viewBox="0 0 24 24"
                            fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M12 .5C5.65.5.5 5.65.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56v-2.15c-3.2.7-3.88-1.37-3.88-1.37-.52-1.33-1.28-1.68-1.28-1.68-1.05-.72.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.76 2.7 1.25 3.36.96.1-.75.4-1.25.73-1.54-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.29 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .98-.31 3.18 1.18A10.9 10.9 0 0112 6.02c.98 0 1.96.13 2.88.39 2.2-1.49 3.17-1.18 3.17-1.18.64 1.59.24 2.76.12 3.05.74.8 1.19 1.83 1.19 3.09 0 4.42-2.69 5.39-5.25 5.68.41.36.78 1.06.78 2.14v3.16c0 .31.21.68.8.56A11.51 11.51 0 0023.5 12C23.5 5.65 18.35.5 12 .5z"
                                clip-rule="evenodd" />
                        </svg>
                        GitHub
                    </a>
                </div>
            </div>

            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[.2em] text-white">Book</h3>
                    <ul class="mt-4 space-y-3 text-sm text-gray-400">
                        <li><a href="{{ route('movies.index') }}" class="hover:text-red-300">All movies</a></li>
                        <li><a href="{{ route('movies.compare') }}" class="hover:text-red-300">Compare tickets</a></li>
                        <li><a href="{{ route('search') }}" class="hover:text-red-300">Search</a></li>
                        <li><a href="{{ route('eticket.info') }}" class="hover:text-red-300">E-ticket info</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[.2em] text-white">Account</h3>
                    <ul class="mt-4 space-y-3 text-sm text-gray-400">
                        <li><a href="{{ route('user.login') }}" class="hover:text-red-300">Login</a></li>
                        <li><a href="{{ route('user.register') }}" class="hover:text-red-300">Register</a></li>
                        <li><a href="{{ route('user.wishlist') }}" class="hover:text-red-300">Wishlist</a></li>
                        <li><a href="{{ route('user.cart') }}" class="hover:text-red-300">Cart</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[.2em] text-white">Company</h3>
                    <ul class="mt-4 space-y-3 text-sm text-gray-400">
                        <li><a href="{{ route('about') }}" class="hover:text-red-300">About</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-red-300">Contact</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:text-red-300">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[.2em] text-white">Legal</h3>
                    <ul class="mt-4 space-y-3 text-sm text-gray-400">
                        <li><a href="{{ route('terms') }}" class="hover:text-red-300">Terms</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-red-300">Privacy</a></li>
                        <li><a href="{{ route('refund') }}" class="hover:text-red-300">Refund policy</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-10 border-t border-white/10 pt-6 text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} BookMyMovie. Created by Syed Ahmer Shah for Aptech DISM Project.</p>
        </div>
    </div>
</footer>
