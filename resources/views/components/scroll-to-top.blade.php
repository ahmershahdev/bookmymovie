<button type="button" x-data="{ spinning: false }" x-cloak x-show="scrolled"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 scale-75"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-4 scale-75" @click="
            spinning = true; 
            scrollTop(); 
            setTimeout(() => spinning = false, 800);
        "
    class="group fixed bottom-6 right-6 z-40 flex h-12 w-12 select-none items-center justify-center rounded-full border border-red-500/30 bg-gradient-to-br from-red-600 to-red-700 text-white shadow-2xl shadow-red-950/80 backdrop-blur-md transition-all duration-300 hover:scale-110 hover:border-red-400 hover:from-red-500 hover:to-red-600 hover:shadow-red-600/30 focus:outline-none focus:ring-2 focus:ring-red-400 active:scale-95"
    aria-label="Scroll to top">

    <!-- Outer Ambient Glow Ring -->
    <span
        class="absolute -inset-0.5 rounded-full bg-red-500/20 blur opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>

    <!-- Film Reel / Rewind Wheel SVG -->
    <svg :class="{ 'animate-[spin_0.4s_linear_infinite_reverse]': spinning }"
        class="relative h-6 w-6 text-gray-100 transition-transform duration-500 ease-out group-hover:-rotate-90 group-hover:scale-110"
        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
        stroke-linejoin="round" aria-hidden="true">
        <!-- Outer Reel Circle -->
        <circle cx="12" cy="12" r="9" />
        <!-- Inner Spoke Reel Circles -->
        <circle cx="12" cy="12" r="2.5" />
        <circle cx="12" cy="7" r="1.5" fill="currentColor" />
        <circle cx="12" cy="17" r="1.5" fill="currentColor" />
        <circle cx="7" cy="12" r="1.5" fill="currentColor" />
        <circle cx="17" cy="12" r="1.5" fill="currentColor" />
        <!-- Up Arrow Center Marker -->
        <path d="M12 10.5L9.5 13h5L12 10.5z" fill="currentColor" stroke="none" />
    </svg>
</button>