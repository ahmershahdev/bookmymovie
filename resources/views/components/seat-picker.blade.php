@props(['seats' => collect()])

<div class="rounded-lg border border-white/10 bg-gray-900 p-6 shadow-2xl shadow-black/30" x-data="{ selected: [] }">
    <div
        class="mx-auto mb-8 h-3 max-w-sm rounded-full bg-gradient-to-r from-transparent via-red-500 to-transparent shadow-lg shadow-red-950/60">
    </div>
    <p class="mb-6 text-center text-xs font-black uppercase tracking-[.24em] text-gray-400">Screen</p>

    <template x-for="seat in selected" :key="seat">
        <input type="hidden" name="seats[]" :value="seat">
    </template>

    <div class="grid grid-cols-8 gap-2">
        @foreach($seats as $seat)
            @php
                $type = strtolower($seat->category_name);
                $class = $type === 'gold'
                    ? 'border-gold/50 bg-gold/20 text-gold'
                    : ($type === 'platinum' ? 'border-platinum/50 bg-platinum/20 text-platinum' : 'border-boxseat/50 bg-boxseat/25 text-violet-200');
                $disabled = $seat->seat_status !== 'available';
            @endphp
            <button type="button"
                @unless($disabled)
                    @click="selected.includes({{ $seat->seat_id }}) ? selected = selected.filter(id => id !== {{ $seat->seat_id }}) : selected.push({{ $seat->seat_id }})"
                @endunless
                @disabled($disabled)
                class="aspect-square rounded-md border text-xs font-black transition {{ $disabled ? 'cursor-not-allowed border-gray-800 bg-gray-800 text-gray-500 line-through' : $class.' hover:scale-105' }}"
                :class="selected.includes({{ $seat->seat_id }}) ? 'ring-2 ring-red-500 bg-red-600 text-white border-red-400' : ''"
                title="{{ $seat->category_name }} - PKR {{ number_format((float) $seat->price) }} - {{ $seat->seat_status }}"
                aria-label="Seat {{ $seat->row_label }}{{ $seat->seat_number }}">
                {{ $seat->row_label }}{{ $seat->seat_number }}
            </button>
        @endforeach
    </div>

    <div class="mt-6 flex flex-wrap justify-center gap-3 text-xs font-bold text-gray-300">
        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-gold/70"></span>Gold</span>
        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-platinum/70"></span>Platinum</span>
        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-boxseat/70"></span>Box</span>
        <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-gray-700"></span>Booked</span>
    </div>
    @error('seats')<p class="mt-4 text-center text-sm text-red-300">{{ $message }}</p>@enderror
</div>
