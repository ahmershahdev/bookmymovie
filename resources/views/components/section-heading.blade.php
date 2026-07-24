@props(['eyebrow', 'title', 'description' => null])

<div class="max-w-3xl">
    <p class="text-sm font-black uppercase tracking-[.22em] text-red-400">{{ $eyebrow }}</p>
    <h2 class="mt-3 text-3xl font-black tracking-normal text-white sm:text-4xl">{{ $title }}</h2>
    @if($description)
        <p class="mt-4 text-base leading-7 text-gray-300">{{ $description }}</p>
    @endif
</div>