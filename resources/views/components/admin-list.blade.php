@props(['items' => [], 'action' => 'Manage'])

<div class="mt-6 divide-y divide-white/10 overflow-hidden rounded-lg border border-white/10 bg-gray-950">
    @forelse($items as $item)
        <div class="grid gap-4 p-4 sm:grid-cols-[1fr_auto] sm:items-center">
            <p class="font-bold text-white">{{ $item }}</p>
            <button type="button"
                class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-black text-white hover:border-red-400/60 hover:bg-red-950/40">{{ $action }}</button>
        </div>
    @empty
        <p class="p-4 text-sm text-gray-400">No records found.</p>
    @endforelse
</div>
