@props([
    'eyebrow' => null, 
    'title', 
    'description' => null,
    'align' => 'left'
])

<div class="relative max-w-3xl {{ $align === 'center' ? 'mx-auto text-center' : '' }}">
    
    {{-- Eyebrow with Glow & Accent Bar --}}
    @if($eyebrow)
        <div class="inline-flex items-center gap-2.5">
            {{-- Glowing Dot Indicator --}}
            <span class="relative flex h-1.5 w-1.5 items-center justify-center">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-amber-500 shadow-[0_0_6px_#f59e0b]"></span>
            </span>

            {{-- Eyebrow Text --}}
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-amber-500/90">
                {{ $eyebrow }}
            </p>
        </div>
    @endif

    {{-- Main Title --}}
    <h2 class="mt-2.5 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl text-zinc-100">
        <span class="bg-gradient-to-r from-zinc-100 via-zinc-200 to-zinc-400 bg-clip-text text-transparent">
            {{ $title }}
        </span>
    </h2>

    {{-- Description --}}
    @if($description)
        <p class="mt-4 text-base leading-relaxed text-zinc-400 sm:text-lg">
            {{ $description }}
        </p>
    @endif

    {{-- Decorative Subtle Bottom Divider Line --}}
    <div class="mt-6 flex {{ $align === 'center' ? 'justify-center' : 'justify-start' }}">
        <div class="h-[2px] w-12 rounded-full bg-gradient-to-r from-amber-500/80 to-transparent"></div>
    </div>
</div>