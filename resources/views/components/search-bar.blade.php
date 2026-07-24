@props([
    'movies' => [],
    'size' => 'default'
])

@php
    $suggestions = collect($movies)->map(fn($movie) => [
        'title' => $movie['title'],
        'genre' => $movie['genre'],
        'language' => $movie['language'],
        'url' => route('movies.show', $movie['slug']),
    ])->values();
@endphp

<form method="GET" action="{{ route('search') }}"
    x-data="{ 
        query: '', 
        focused: false, 
        selectedIndex: -1,
        suggestions: @js($suggestions), 
        get filtered() { 
            const value = this.query.toLowerCase().trim(); 
            return value.length < 1 
                ? this.suggestions.slice(0, 5) 
                : this.suggestions.filter(item => 
                    `${item.title} ${item.genre} ${item.language}`.toLowerCase().includes(value)
                  ).slice(0, 6); 
        },
        selectNext() {
            if (this.selectedIndex < this.filtered.length - 1) this.selectedIndex++;
        },
        selectPrev() {
            if (this.selectedIndex > 0) this.selectedIndex--;
        },
        goToSelected() {
            if (this.selectedIndex >= 0 && this.filtered[this.selectedIndex]) {
                window.location.href = this.filtered[this.selectedIndex].url;
            }
        }
    }"
    class="relative w-full" 
    role="search">
    
    <label for="movie-search-{{ $size }}" class="sr-only">Search movies, genres, and languages</label>

    {{-- Main Input Container --}}
    <div class="group relative flex items-center rounded-full border border-zinc-800 bg-zinc-950/90 px-4 py-1.5 shadow-[0_10px_30px_rgba(0,0,0,0.8)] backdrop-blur-xl transition-all duration-300 focus-within:border-amber-500/60 focus-within:ring-2 focus-within:ring-amber-500/20 hover:border-zinc-700 {{ $size === 'large' ? 'min-h-14 px-6' : '' }}">
        
        {{-- Search Icon --}}
        <svg class="h-5 w-5 shrink-0 text-zinc-400 transition-colors duration-300 group-focus-within:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z" />
        </svg>

        {{-- Input Field --}}
        <input id="movie-search-{{ $size }}" 
               x-ref="searchInput"
               name="q" 
               type="text" 
               x-model="query" 
               @focus="focused = true; selectedIndex = -1;"
               @keydown.escape="focused = false"
               @keydown.arrow-down.prevent="selectNext()"
               @keydown.arrow-up.prevent="selectPrev()"
               @keydown.enter="if(selectedIndex >= 0) { $event.preventDefault(); goToSelected(); }"
               placeholder="Search movies, genres, languages..."
               autocomplete="off"
               class="w-full border-0 bg-transparent px-3 text-sm text-zinc-100 placeholder:text-zinc-500 focus:outline-none focus:ring-0 {{ $size === 'large' ? 'py-3 text-base' : 'py-2' }}">

        {{-- Clear Button (Appears when user types) --}}
        <button type="button" 
                x-show="query.length > 0" 
                @click="query = ''; $refs.searchInput.focus()"
                class="mr-2 text-zinc-500 hover:text-zinc-300 transition-colors"
                aria-label="Clear search">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Search Button --}}
        <button type="submit"
                class="shrink-0 rounded-full bg-amber-500 px-5 py-2 text-xs font-bold uppercase tracking-widest text-zinc-950 transition-all duration-300 hover:bg-amber-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.4)] active:scale-95 focus:outline-none focus:ring-2 focus:ring-amber-400">
            Search
        </button>
    </div>

    @error('q')
        <p class="mt-2 text-xs font-medium text-amber-500/90">{{ $message }}</p>
    @enderror

    {{-- Dropdown Suggestions Menu --}}
    <div x-cloak 
         x-show="focused && filtered.length" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-98"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-98"
         @click.outside="focused = false"
         class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950/95 p-2 shadow-[0_20px_50px_rgba(0,0,0,0.9)] backdrop-blur-2xl">
        
        {{-- Header Label for Quick Suggestions --}}
        <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-zinc-500">
            <span x-text="query.length < 1 ? 'Popular Searches' : 'Matches'"></span>
        </div>

        <div class="space-y-1">
            <template x-for="(movie, index) in filtered" :key="movie.title">
                <a :href="movie.url"
                   @mouseenter="selectedIndex = index"
                   :class="{ 'bg-zinc-900 border-zinc-700/60': selectedIndex === index, 'border-transparent hover:bg-zinc-900/50': selectedIndex !== index }"
                   class="group flex items-center justify-between gap-4 rounded-xl border px-3.5 py-2.5 transition-all duration-200">
                    
                    <div class="flex flex-col gap-0.5">
                        <span class="text-sm font-semibold text-zinc-100 transition-colors group-hover:text-amber-400" x-text="movie.title"></span>
                        
                        {{-- Meta Tag Badges --}}
                        <div class="flex items-center gap-2 text-xs text-zinc-400">
                            <span class="rounded-md bg-zinc-900 px-1.5 py-0.5 text-[10px] font-medium text-zinc-400 border border-zinc-800" x-text="movie.genre"></span>
                            <span class="text-[10px] text-zinc-500">•</span>
                            <span class="text-xs text-zinc-400" x-text="movie.language"></span>
                        </div>
                    </div>

                    {{-- Arrow Indicator --}}
                    <div class="flex items-center text-xs font-semibold text-amber-500 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                        <svg class="h-4 w-4 transform transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            </template>
        </div>
    </div>
</form>
