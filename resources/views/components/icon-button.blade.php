@props(['id', 'label', 'href', 'counter' => null])

<a id="{{ $id }}" href="{{ $href }}" aria-label="{{ $label }}"
    class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/5 text-gray-100 transition hover:border-red-400/60 hover:bg-red-950/40 focus:outline-none focus:ring-2 focus:ring-red-400">
    {{ $slot }}
    @if($counter)
        <span x-show="{{ $counter }} > 0" x-text="{{ $counter }}"
            class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-[10px] font-black text-white"></span>
    @endif
</a>