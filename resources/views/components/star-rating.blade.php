@props(['rating' => 0])

<div class="inline-flex items-center gap-1 rounded-full border border-gold/20 bg-gold/10 px-3 py-1 text-sm font-black text-gold"
    aria-label="Rating {{ $rating }} out of 5">
    <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20" aria-hidden="true">
        <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.78l-5.2 2.74.99-5.8-4.21-4.1 5.82-.85L10 1.5z" />
    </svg>
    {{ number_format($rating, 1) }}
</div>