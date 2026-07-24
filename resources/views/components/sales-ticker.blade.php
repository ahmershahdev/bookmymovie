@props(['messages' => []])

<section class="ticker-shell mt-[73px] overflow-hidden border-b border-red-500/20 bg-red-950/30 py-3 lg:mt-[69px]"
    aria-label="BookMyMovie announcements">
    <div class="ticker-track flex w-max items-center gap-8 text-sm font-bold uppercase tracking-[.18em] text-red-100">
        @foreach(array_merge($messages, $messages) as $message)
            <span class="flex items-center gap-3">
                <span class="h-2 w-2 rounded-full bg-gold"></span>
                {{ $message }}
            </span>
        @endforeach
    </div>
</section>