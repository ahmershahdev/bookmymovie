@props(['slides' => []])

<section
    x-data="{ active: 0, playing: true, slides: @js($slides), timer: null, start() { this.stop(); this.timer = setInterval(() => { if (this.playing) this.next(); }, 4200); }, stop() { if (this.timer) clearInterval(this.timer); }, next() { this.active = (this.active + 1) % this.slides.length; }, prev() { this.active = (this.active + this.slides.length - 1) % this.slides.length; } }"
    x-init="start()"
    class="relative min-h-[600px] overflow-hidden rounded-lg border border-white/10 bg-gray-900 shadow-2xl shadow-black/50">
    <template x-for="(slide, index) in slides" :key="slide.slug">
        <article x-cloak x-show="active === index" x-transition.opacity class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-br" :class="slide.gradient"></div>
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(248,250,252,.22),transparent_26%),linear-gradient(180deg,transparent,rgba(3,7,18,.94))]">
            </div>
            <img src="{{ asset('images/logo.png') }}" alt=""
                class="absolute right-8 top-8 h-48 w-48 rounded-full object-cover opacity-20 blur-[1px]">
            <div class="relative flex h-full flex-col justify-end p-6 sm:p-8 lg:p-10">
                <p class="text-sm font-black uppercase tracking-[.24em] text-red-300" x-text="slide.status"></p>
                <h2 class="mt-3 text-4xl font-black text-white sm:text-5xl" x-text="slide.title"></h2>
                <p class="mt-4 max-w-lg text-base leading-7 text-gray-200" x-text="slide.tagline"></p>
                <div class="mt-6 flex flex-wrap items-center gap-3 text-sm font-semibold text-gray-200">
                    <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1" x-text="slide.genre"></span>
                    <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1"
                        x-text="slide.language"></span>
                    <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1"
                        x-text="`PKR ${slide.price}`"></span>
                </div>
                <div class="mt-8 flex items-center gap-3">
                    <a :href="'{{ route('movies.show', 'SLUG_TOKEN') }}'.replace('SLUG_TOKEN', slide.slug)"
                        class="rounded-full bg-red-600 px-5 py-3 text-sm font-bold text-white shadow-xl shadow-red-950/40 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">View
                        details</a>
                    <a :href="'{{ route('movies.seats', ['slug' => 'SLUG_TOKEN', 'show' => 1]) }}'.replace('SLUG_TOKEN', slide.slug)"
                        class="rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm font-bold text-white transition hover:border-red-400/60 hover:bg-red-950/30 focus:outline-none focus:ring-2 focus:ring-red-400">Book
                        seats</a>
                </div>
            </div>
        </article>
    </template>

    <div class="absolute left-5 right-5 top-5 flex items-center justify-between">
        <div class="flex gap-2" role="tablist" aria-label="Featured movies">
            <template x-for="(slide, index) in slides" :key="slide.slug">
                <button type="button" @click="active = index" class="h-1.5 w-12 rounded-full transition"
                    :class="active === index ? 'bg-red-500' : 'bg-white/25'"
                    :aria-label="`Show ${slide.title}`"></button>
            </template>
        </div>
        <button type="button" @click="playing = !playing"
            class="rounded-full border border-white/10 bg-black/30 px-4 py-2 text-xs font-black uppercase tracking-[.18em] text-white backdrop-blur transition hover:bg-red-950/40"
            x-text="playing ? 'Pause' : 'Play'"></button>
    </div>

    <button type="button" @click="prev()"
        class="absolute left-5 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-black/35 text-white backdrop-blur transition hover:bg-red-950/50"
        aria-label="Previous slide">
        <span aria-hidden="true">&lt;</span>
    </button>
    <button type="button" @click="next()"
        class="absolute right-5 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-black/35 text-white backdrop-blur transition hover:bg-red-950/50"
        aria-label="Next slide">
        <span aria-hidden="true">&gt;</span>
    </button>
</section>