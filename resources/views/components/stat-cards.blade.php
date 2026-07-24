@props(['label', 'value'])

<div
    class="group relative overflow-hidden rounded-2xl border border-zinc-800/80 bg-zinc-950/80 p-5 shadow-[0_10px_30px_rgba(0,0,0,0.5)] backdrop-blur-md transition-all duration-300 hover:border-amber-500/40 hover:shadow-[0_10px_30px_rgba(245,158,11,0.1)]">

    {{-- Subtle Top Ambient Radial Glow on Hover --}}
    <div
        class="pointer-events-none absolute -top-12 left-1/2 -translate-x-1/2 h-24 w-32 rounded-full bg-amber-500/10 blur-2xl transition-opacity duration-300 opacity-0 group-hover:opacity-100">
    </div>

    {{-- Value (Large Highlight Number) --}}
    <div class="flex items-baseline justify-between gap-2">
        <p class="text-3xl font-extrabold tracking-tight text-zinc-100 sm:text-4xl">
            <span class="bg-gradient-to-br from-zinc-100 via-zinc-200 to-zinc-400 bg-clip-text text-transparent">
                {{ $value }}
            </span>
        </p>

        {{-- Interactive Glowing Dot Accent --}}
        <span class="relative flex h-2 w-2 items-center justify-center">
            <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-0 transition-opacity group-hover:opacity-75"></span>
            <span
                class="relative inline-flex h-1.5 w-1.5 rounded-full bg-zinc-700 transition-colors duration-300 group-hover:bg-amber-500"></span>
        </span>
    </div>

    {{-- Label --}}
    <p
        class="mt-2 text-xs font-bold uppercase tracking-[0.2em] text-zinc-400 transition-colors duration-300 group-hover:text-amber-400/90">
        {{ $label }}
    </p>

    {{-- Bottom Accent Border Highlight --}}
    <div
        class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-amber-500/50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100">
    </div>
</div>