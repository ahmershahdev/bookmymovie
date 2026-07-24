@props(['id', 'label', 'href', 'counter' => null])

<a id="{{ $id }}" href="{{ $href }}" aria-label="{{ $label }}"
    class="group relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/5 text-gray-200 backdrop-blur-md transition-all duration-300 hover:scale-105 hover:border-red-500/60 hover:bg-red-950/40 hover:text-white hover:shadow-lg hover:shadow-red-950/50 focus:outline-none focus:ring-2 focus:ring-red-500">

    <!-- Icon Slot with subtle scale effect on hover -->
    <span class="transition-transform duration-200 group-hover:scale-110">
        {{ $slot }}
    </span>

    <!-- Animated Reactive Counter Badge -->
    @if($counter)
        <span x-cloak x-show="{{ $counter }} > 0" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100"
            x-text="{{ $counter }}"
            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-gradient-to-r from-red-600 to-red-500 px-1.5 text-center text-[10px] font-black text-white shadow-md shadow-red-950/80 ring-2 ring-gray-950">
        </span>
    @endif
</a>