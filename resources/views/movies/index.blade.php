@extends('layouts.app')

@section('title', 'Movies | BookMyMovie')

@section('content')
    <div x-data="{ 
             x: 0, 
             y: 0,
             updateMouse(e) {
                 const rect = $el.getBoundingClientRect();
                 this.x = e.clientX - rect.left;
                 this.y = e.clientY - rect.top;
             }
         }"
         @mousemove="updateMouse($event)"
         class="relative min-h-screen bg-zinc-950 px-4 pb-20 pt-32 sm:px-6 lg:px-8 overflow-hidden text-zinc-100">

        {{-- Ambient 3D Glow Orbs --}}
        <div class="pointer-events-none absolute -left-40 -top-40 h-96 w-96 rounded-full bg-amber-500/10 blur-[120px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-40 top-1/3 h-96 w-96 rounded-full bg-amber-600/5 blur-[160px]" aria-hidden="true"></div>

        <div class="mx-auto max-w-7xl relative z-10">

            {{-- Section Header --}}
            <div class="flex flex-col items-start gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-amber-400 backdrop-blur-md">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    Cinematic Selection
                </span>
                <h1 class="text-3xl font-black uppercase tracking-tight text-white sm:text-5xl">
                    Explore Movies
                </h1>
                <p class="max-w-2xl text-sm text-zinc-400">
                    Filter through our premiere catalog by genre, language, age certification, and availability status.
                </p>
            </div>

            {{-- Interactive Filter Command Bar --}}
            <form method="GET" action="{{ route('movies.index') }}"
                class="relative mt-8 rounded-3xl border border-zinc-800/80 bg-zinc-950/90 p-5 shadow-2xl backdrop-blur-2xl transition-all duration-300 z-30"
                :style="`background: radial-gradient(600px circle at ${x}px ${y}px, rgba(245, 158, 11, 0.06), rgba(9, 9, 11, 0.95));`">

                {{-- Cursor Border Glow --}}
                <div class="pointer-events-none absolute -inset-px rounded-3xl transition-opacity duration-300"
                     :style="`background: radial-gradient(300px circle at ${x}px ${y}px, rgba(245, 158, 11, 0.25), transparent 70%); mix-blend-mode: overlay;`"
                     aria-hidden="true"></div>

                <div class="relative z-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5 items-end">

                    {{-- Genre Filter Dropdown --}}
                    <div class="relative space-y-1.5" 
                         x-data="{ 
                             open: false, 
                             selected: @js(request('genre', '')), 
                             label: @js($genres->firstWhere('slug', request('genre'))?->name ?? 'All Genres') 
                         }" 
                         @click.outside="open = false">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400">Genre</label>
                        <input type="hidden" name="genre" :value="selected">

                        <button type="button" @click="open = !open" 
                            class="w-full flex items-center justify-between rounded-2xl border border-zinc-800 bg-zinc-900/90 px-4 py-2.5 text-xs font-semibold text-zinc-200 transition-all hover:border-zinc-700 focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                            <span x-text="label" class="truncate"></span>
                            <svg class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-300" :class="{ 'rotate-180 text-amber-400': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Themed Custom Dropdown Options --}}
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                             x-cloak
                             class="absolute left-0 right-0 z-50 mt-2 max-h-60 overflow-y-auto rounded-2xl border border-zinc-800/90 bg-zinc-950/95 p-1.5 shadow-2xl backdrop-blur-2xl space-y-1">

                            <button type="button" @click="selected = ''; label = 'All Genres'; open = false" 
                                class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center justify-between"
                                :class="selected === '' ? 'bg-amber-500/10 text-amber-400 font-bold' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white'">
                                <span>All Genres</span>
                                <span x-show="selected === ''" class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                            </button>
                            @foreach($genres as $genre)
                                <button type="button" @click="selected = @js($genre->slug); label = @js($genre->name); open = false" 
                                    class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center justify-between"
                                    :class="selected === '{{ $genre->slug }}' ? 'bg-amber-500/10 text-amber-400 font-bold' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white'">
                                    <span>{{ $genre->name }}</span>
                                    <span x-show="selected === '{{ $genre->slug }}'" class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Language Filter Dropdown --}}
                    <div class="relative space-y-1.5" 
                         x-data="{ 
                             open: false, 
                             selected: @js(request('language', '')), 
                             label: @js(request('language') ?: 'All Languages') 
                         }" 
                         @click.outside="open = false">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400">Language</label>
                        <input type="hidden" name="language" :value="selected">

                        <button type="button" @click="open = !open" 
                            class="w-full flex items-center justify-between rounded-2xl border border-zinc-800 bg-zinc-900/90 px-4 py-2.5 text-xs font-semibold text-zinc-200 transition-all hover:border-zinc-700 focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                            <span x-text="label" class="truncate"></span>
                            <svg class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-300" :class="{ 'rotate-180 text-amber-400': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                             x-cloak
                             class="absolute left-0 right-0 z-50 mt-2 max-h-60 overflow-y-auto rounded-2xl border border-zinc-800/90 bg-zinc-950/95 p-1.5 shadow-2xl backdrop-blur-2xl space-y-1">

                            <button type="button" @click="selected = ''; label = 'All Languages'; open = false" 
                                class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center justify-between"
                                :class="selected === '' ? 'bg-amber-500/10 text-amber-400 font-bold' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white'">
                                <span>All Languages</span>
                                <span x-show="selected === ''" class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                            </button>
                            @foreach($languages as $language)
                                <button type="button" @click="selected = @js($language); label = @js($language); open = false" 
                                    class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center justify-between"
                                    :class="selected === '{{ $language }}' ? 'bg-amber-500/10 text-amber-400 font-bold' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white'">
                                    <span>{{ $language }}</span>
                                    <span x-show="selected === '{{ $language }}'" class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Status Filter Dropdown --}}
                    @php
                        $statuses = ['now_showing' => 'Now Showing', 'coming_soon' => 'Coming Soon', 'ended' => 'Ended'];
                    @endphp
                    <div class="relative space-y-1.5" 
                         x-data="{ 
                             open: false, 
                             selected: @js(request('status', '')), 
                             label: @js(request('status') && isset($statuses[request('status')]) ? $statuses[request('status')] : 'All Statuses') 
                         }" 
                         @click.outside="open = false">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400">Status</label>
                        <input type="hidden" name="status" :value="selected">

                        <button type="button" @click="open = !open" 
                            class="w-full flex items-center justify-between rounded-2xl border border-zinc-800 bg-zinc-900/90 px-4 py-2.5 text-xs font-semibold text-zinc-200 transition-all hover:border-zinc-700 focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                            <span x-text="label" class="truncate"></span>
                            <svg class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-300" :class="{ 'rotate-180 text-amber-400': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                             x-cloak
                             class="absolute left-0 right-0 z-50 mt-2 max-h-60 overflow-y-auto rounded-2xl border border-zinc-800/90 bg-zinc-950/95 p-1.5 shadow-2xl backdrop-blur-2xl space-y-1">

                            <button type="button" @click="selected = ''; label = 'All Statuses'; open = false" 
                                class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center justify-between"
                                :class="selected === '' ? 'bg-amber-500/10 text-amber-400 font-bold' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white'">
                                <span>All Statuses</span>
                                <span x-show="selected === ''" class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                            </button>
                            @foreach($statuses as $value => $statusLabel)
                                <button type="button" @click="selected = @js($value); label = @js($statusLabel); open = false" 
                                    class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center justify-between"
                                    :class="selected === '{{ $value }}' ? 'bg-amber-500/10 text-amber-400 font-bold' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white'">
                                    <span>{{ $statusLabel }}</span>
                                    <span x-show="selected === '{{ $value }}'" class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Certificate Filter Dropdown --}}
                    <div class="relative space-y-1.5" 
                         x-data="{ 
                             open: false, 
                             selected: @js(request('certificate', '')), 
                             label: @js(request('certificate') ?: 'All Ratings') 
                         }" 
                         @click.outside="open = false">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-400">Rating</label>
                        <input type="hidden" name="certificate" :value="selected">

                        <button type="button" @click="open = !open" 
                            class="w-full flex items-center justify-between rounded-2xl border border-zinc-800 bg-zinc-900/90 px-4 py-2.5 text-xs font-semibold text-zinc-200 transition-all hover:border-zinc-700 focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                            <span x-text="label" class="truncate"></span>
                            <svg class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-300" :class="{ 'rotate-180 text-amber-400': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                             x-cloak
                             class="absolute left-0 right-0 z-50 mt-2 max-h-60 overflow-y-auto rounded-2xl border border-zinc-800/90 bg-zinc-950/95 p-1.5 shadow-2xl backdrop-blur-2xl space-y-1">

                            <button type="button" @click="selected = ''; label = 'All Ratings'; open = false" 
                                class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center justify-between"
                                :class="selected === '' ? 'bg-amber-500/10 text-amber-400 font-bold' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white'">
                                <span>All Ratings</span>
                                <span x-show="selected === ''" class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                            </button>
                            @foreach($certificates as $certificate)
                                <button type="button" @click="selected = @js($certificate); label = @js($certificate); open = false" 
                                    class="w-full text-left px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors flex items-center justify-between"
                                    :class="selected === '{{ $certificate }}' ? 'bg-amber-500/10 text-amber-400 font-bold' : 'text-zinc-300 hover:bg-zinc-900 hover:text-white'">
                                    <span>{{ $certificate }}</span>
                                    <span x-show="selected === '{{ $certificate }}'" class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Action Controls --}}
                    <div class="flex items-center gap-2">
                        <button type="submit"
                            class="group relative inline-flex w-full items-center justify-center overflow-hidden rounded-2xl bg-amber-500 px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-zinc-950 shadow-[0_0_20px_rgba(245,158,11,0.2)] transition-all duration-300 hover:bg-amber-400 hover:shadow-[0_0_30px_rgba(245,158,11,0.4)] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-amber-400">
                            <span class="flex items-center gap-2">
                                Apply
                                <svg class="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </span>
                        </button>

                        @if(request()->hasAny(['genre', 'language', 'status', 'certificate']))
                            <a href="{{ route('movies.index') }}"
                                title="Clear Filters"
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-zinc-800 bg-zinc-900/80 text-zinc-400 transition-colors hover:border-zinc-700 hover:bg-zinc-800 hover:text-white">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </div>

                </div>
            </form>

            {{-- Movie Grid --}}
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($movieCards as $movie)
                    <div class="transition-transform duration-300 hover:-translate-y-1.5">
                        <x-movie-card :movie="$movie" />
                    </div>
                @empty
                    {{-- Empty State --}}
                    <div class="col-span-full rounded-3xl border border-zinc-800/80 bg-zinc-950/80 p-12 text-center backdrop-blur-2xl shadow-2xl">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-zinc-800 bg-zinc-900/80 text-amber-500 shadow-inner">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75h-1.5M7.5 3.75h9M7.5 3.75V20.25m9-16.5V20.25M3.75 9h16.5m-16.5 6h16.5" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-base font-bold text-zinc-200">No Movies Match Your Criteria</h3>
                        <p class="mt-1 text-xs text-zinc-500">Try adjusting or clearing your filters to discover active showtimes.</p>
                        <a href="{{ route('movies.index') }}" 
                           class="mt-6 inline-flex items-center gap-2 rounded-full border border-zinc-800 bg-zinc-900 px-5 py-2 text-xs font-bold uppercase tracking-wider text-amber-400 transition-colors hover:border-amber-500/40 hover:bg-zinc-800">
                           Reset All Filters
                        </a>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($moviesPaginator->hasPages())
                <div class="mt-12 rounded-2xl border border-zinc-800/80 bg-zinc-950/80 p-4 backdrop-blur-xl shadow-xl">
                    {{ $moviesPaginator->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection
