@props(['label', 'value'])

<div class="rounded-lg border border-white/10 bg-white/5 p-4">
    <p class="text-3xl font-black text-white">{{ $value }}</p>
    <p class="mt-1 text-xs font-bold uppercase tracking-[.18em] text-gray-400">{{ $label }}</p>
</div>
