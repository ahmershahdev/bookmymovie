@props(['label', 'value'])

<div
    class="group relative overflow-hidden rounded-lg border border-white/10 bg-gray-950/80 p-5 shadow-lg shadow-black/20 backdrop-blur-md transition-all duration-200 hover:border-red-400/40 hover:bg-gray-900/80">
    <div class="flex items-baseline justify-between gap-2">
        <p class="max-w-full truncate text-2xl font-black tracking-normal text-white sm:text-3xl" title="{{ $value }}">
            {{ $value }}
        </p>

        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-red-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-5m4 5V8m4 8v-3" />
            </svg>
        </span>
    </div>

    <p
        class="mt-2 text-xs font-bold uppercase tracking-[0.16em] text-gray-400 transition-colors duration-200 group-hover:text-red-300">
        {{ $label }}
    </p>
</div>
