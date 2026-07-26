@props(['slides' => []])

@if(empty($slides))
    <section class="relative min-h-[420px] overflow-hidden rounded-lg border border-white/10 bg-gray-950">
        <img src="{{ asset('images/logo.png') }}" alt="BookMyMovie"
            class="absolute inset-0 h-full w-full object-cover opacity-20">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-gray-950/80 to-gray-950/30"></div>
        <div class="relative flex min-h-[420px] flex-col justify-end p-8 sm:p-12">
            <p class="text-xs font-black uppercase tracking-[0.3em] text-red-400">BookMyMovie</p>
            <h2 class="mt-3 text-4xl font-black text-white sm:text-5xl">Movies are being prepared</h2>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-gray-300">Add movies from the admin dashboard to populate the carousel.</p>
        </div>
    </section>
@else
<section 
    x-data="{ 
        active: 0, 
        playing: true, 
        slides: @js($slides), 
        timer: null,
        tiltX: 0,
        tiltY: 0,
        touchStartX: 0,
        
        start() { 
            this.stop(); 
            this.timer = setInterval(() => { if (this.playing) this.next(); }, 5000); 
        }, 
        stop() { 
            if (this.timer) clearInterval(this.timer); 
        }, 
        next() { 
            if (!this.slides.length) return;
            this.active = (this.active + 1) % this.slides.length; 
        }, 
        prev() { 
            if (!this.slides.length) return;
            this.active = (this.active + this.slides.length - 1) % this.slides.length; 
        },
        handleMouseMove(e) {
            const rect = $el.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            this.tiltX = -y * 12; // Rotate X up/down
            this.tiltY = x * 12;  // Rotate Y left/right
        },
        resetTilt() {
            this.tiltX = 0;
            this.tiltY = 0;
        }
    }"
    x-init="start()"
    @mousemove="handleMouseMove($event)"
    @mouseleave="resetTilt()"
    @touchstart="touchStartX = $event.touches[0].clientX"
    @touchend="if ($event.changedTouches[0].clientX - touchStartX < -50) next(); if ($event.changedTouches[0].clientX - touchStartX > 50) prev();"
    class="relative min-h-[580px] w-full overflow-hidden rounded-lg border border-white/10 bg-gray-950 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.9)] perspective-1200 group">

    <!-- Slide Container with Dynamic 3D Tilt -->
    <div class="relative h-full w-full transform-gpu transition-transform duration-200 ease-out"
         :style="`transform: rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateZ(0);`">

        <template x-for="(slide, index) in slides" :key="slide.slug">
            <!-- 3D Page Turn Slide Wrapper -->
            <article x-cloak 
                     x-show="active === index" 
                     x-transition:enter="transition-all ease-out duration-700"
                     x-transition:enter-start="opacity-0 [transform:rotateY(90deg)_scale(0.95)]"
                     x-transition:enter-end="opacity-100 [transform:rotateY(0deg)_scale(1)]"
                     x-transition:leave="transition-all ease-in duration-500"
                     x-transition:leave-start="opacity-100 [transform:rotateY(0deg)_scale(1)]"
                     x-transition:leave-end="opacity-0 [transform:rotateY(-90deg)_scale(0.95)]"
                     class="absolute inset-0 transform-gpu origin-left">
                
                <!-- Dynamic movie image -->
                <img :src="slide.hero_image_url || slide.banner_url || slide.poster_url || '{{ asset('images/logo.png') }}'"
                     :alt="slide.title"
                     class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-gray-950 via-gray-950/75 to-gray-950/15"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-gray-950 via-transparent to-black/45"></div>
                
                <!-- Cinema Lighting Overlay -->
                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(0,0,0,.25)_0%,rgba(3,7,18,0.95)_88%)]"></div>

                <!-- Slide Content -->
                <div class="relative flex h-full min-h-[580px] flex-col justify-end p-8 sm:p-12 lg:p-16">
                    
                    <!-- Status Badge -->
                    <div class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
                        <p class="text-xs font-black uppercase tracking-[0.3em] text-red-400" x-text="slide.hero_eyebrow || slide.status"></p>
                    </div>

                    <!-- Title -->
                    <h2 class="mt-3 text-4xl font-black text-white sm:text-6xl tracking-tight drop-shadow-md" x-text="slide.title"></h2>
                    
                    <!-- Tagline -->
                    <p class="mt-4 max-w-xl text-base leading-relaxed text-gray-300 font-medium drop-shadow" x-text="slide.tagline"></p>
                    
                    <!-- Metadata Badges -->
                    <div class="mt-6 flex flex-wrap items-center gap-2.5 text-xs font-bold text-gray-200">
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-1.5 backdrop-blur-md" x-text="slide.genre"></span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-4 py-1.5 backdrop-blur-md" x-text="slide.language"></span>
                        <span class="rounded-full border border-red-500/40 bg-red-950/40 px-4 py-1.5 text-red-300 backdrop-blur-md" x-text="`PKR ${slide.price}`"></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 flex items-center gap-4">
                        <a :href="'{{ route('movies.show', 'SLUG_TOKEN') }}'.replace('SLUG_TOKEN', slide.slug)"
                           class="group relative inline-flex items-center justify-center rounded-full bg-gradient-to-r from-red-600 to-red-500 px-7 py-3.5 text-sm font-black text-white shadow-lg shadow-red-600/30 transition-all duration-300 hover:scale-105 hover:from-red-500 hover:to-red-400 focus:outline-none focus:ring-2 focus:ring-red-400">
                           View Details
                        </a>
                        
                        <a :href="slide.first_show_id ? '{{ route('movies.seats', ['slug' => 'SLUG_TOKEN', 'show' => 'SHOW_TOKEN']) }}'.replace('SLUG_TOKEN', slide.slug).replace('SHOW_TOKEN', slide.first_show_id) : '{{ route('movies.show', 'SLUG_TOKEN') }}'.replace('SLUG_TOKEN', slide.slug)"
                           class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/5 px-7 py-3.5 text-sm font-bold text-white backdrop-blur-md transition-all duration-300 hover:border-red-400/60 hover:bg-red-950/30 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-400">
                           Book Seats
                        </a>
                    </div>
                </div>
            </article>
        </template>

    </div>

    <!-- Top Floating Header Bar (Indicators & Play/Pause) -->
    <div class="absolute left-6 right-6 top-6 z-20 flex items-center justify-between">
        
        <!-- Progress Bar Indicators -->
        <div class="flex items-center gap-2" role="tablist" aria-label="Featured movies">
            <template x-for="(slide, index) in slides" :key="slide.slug">
                <button type="button" 
                        @click="active = index" 
                        class="relative h-1.5 rounded-full transition-all duration-500 overflow-hidden"
                        :class="active === index ? 'w-12 bg-red-500 shadow-lg shadow-red-500/50' : 'w-4 bg-white/20 hover:bg-white/40'"
                        :aria-label="`Show ${slide.title}`">
                </button>
            </template>
        </div>

        <!-- Play/Pause Switcher -->
        <button type="button" 
                @click="playing = !playing"
                class="flex items-center gap-2 rounded-full border border-white/10 bg-black/40 px-4 py-1.5 text-[11px] font-black uppercase tracking-widest text-white backdrop-blur-xl transition hover:bg-red-950/50 hover:border-red-500/40">
            <span class="h-1.5 w-1.5 rounded-full" :class="playing ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
            <span x-text="playing ? 'Pause' : 'Play'"></span>
        </button>
    </div>

</section>

<!-- Custom CSS Perspective utility -->
<style nonce="{{ $cspNonce ?? '' }}">
    .perspective-1200 {
        perspective: 1200px;
    }
</style>
@endif
