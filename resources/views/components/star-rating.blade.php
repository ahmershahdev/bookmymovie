@props(['rating' => 0])

<div class="group relative inline-flex select-none items-center gap-1.5 overflow-hidden rounded-full border border-amber-400/30 bg-gradient-to-r from-amber-500/15 via-yellow-500/10 to-amber-500/15 px-3 py-1 text-xs font-black tracking-wider text-amber-300 shadow-[0_0_15px_rgba(245,158,11,0.15)] backdrop-blur-md transition-all duration-300 hover:scale-105 hover:border-amber-400/60 hover:shadow-[0_0_20px_rgba(245,158,11,0.3)]"
    aria-label="Rating {{ $rating }} out of 5">

    <!-- Shimmering Light Reflective Streak -->
    <div
        class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent transition-transform duration-1000 group-hover:translate-x-full">
    </div>

    <!-- Shiny Dynamic SVG Star with Drop Shadow -->
    <svg class="h-4 w-4 fill-amber-400 drop-shadow-[0_0_6px_rgba(251,191,36,0.8)] transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
        viewBox="0 0 20 20" aria-hidden="true">
        <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.78l-5.2 2.74.99-5.8-4.21-4.1 5.82-.85L10 1.5z" />
    </svg>

    <!-- Formatted Rating Value -->
    <span
        class="bg-gradient-to-r from-amber-200 via-yellow-300 to-amber-400 bg-clip-text text-transparent drop-shadow-sm">
        {{ number_format($rating, 1) }}
    </span>
</div>