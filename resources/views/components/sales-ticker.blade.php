@props([
    'messages' => [
        'VIP CINEMA PASS: Enjoy 20% off premiere night tickets with code MOVIENIGHT',
        'IMAX EXCLUSIVE: Christopher Nolan’s latest visual masterpiece is selling fast',
        'GOURMET EXPERIENCE: Free popcorn size upgrade on all online bookings today',
        'RED CARPET LOUNGE: Unlimited luxury snacks & reclining seats now available'
    ]
])

{{-- Custom Style block for seamless infinite scrolling & glow effect --}}
@once
<style nonce="{{ $cspNonce ?? '' }}">
    @keyframes ticker-slide {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    .animate-ticker {
        display: flex;
        width: max-content;
        animation: ticker-slide 35s linear infinite;
    }
    .animate-ticker:hover {
        animation-play-state: paused;
    }
</style>
@endonce

<section class="relative z-20 mt-[73px] overflow-hidden border-y border-amber-500/20 bg-gradient-to-r from-zinc-950 via-zinc-900 to-zinc-950 py-3.5 backdrop-blur-md shadow-[0_4px_20px_rgba(0,0,0,0.5)] lg:mt-[69px]"
         aria-label="BookMyMovie Exclusive Announcements">

    {{-- Left & Right Gradient Fades for a Premium Vignette --}}
    <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-24 bg-gradient-to-r from-zinc-950 to-transparent"></div>
    <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-24 bg-gradient-to-l from-zinc-950 to-transparent"></div>

    {{-- Continuous Scrolling Track --}}
    <div class="animate-ticker">
        {{-- Loop twice to ensure continuous smooth infinite scrolling --}}
        @foreach(array_merge($messages, $messages) as $message)
            <div class="inline-flex items-center gap-6 px-6 text-xs sm:text-sm font-semibold tracking-widest text-zinc-200 transition-colors duration-300 hover:text-amber-400">
                
                {{-- Glowing Gold Dot Divider --}}
                <span class="relative flex h-2 w-2 items-center justify-center">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500 shadow-[0_0_8px_#f59e0b]"></span>
                </span>

                {{-- Message Content --}}
                <span class="whitespace-nowrap font-medium uppercase tracking-[0.15em]">
                    {{ $message }}
                </span>
            </div>
        @endforeach
    </div>
</section>
