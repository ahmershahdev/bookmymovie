@props(['movies' => [], 'size' => 'default'])

@php
    $suggestions = collect($movies)->map(fn($movie) => [
        'title' => $movie['title'],
        'genre' => $movie['genre'],
        'language' => $movie['language'],
        'url' => route('movies.show', $movie['slug']),
    ])->values();
@endphp

<form method="GET" action="{{ route('search') }}"
    x-data="{ query: '', focused: false, suggestions: @js($suggestions), get filtered() { const value = this.query.toLowerCase(); return value.length < 1 ? this.suggestions.slice(0, 5) : this.suggestions.filter(item => `${item.title} ${item.genre} ${item.language}`.toLowerCase().includes(value)).slice(0, 6); } }"
    class="relative w-full" role="search">
    @csrf
    <label for="movie-search-{{ $size }}" class="sr-only">Search movies, genres, and languages</label>
    <div
        class="flex items-center rounded-full border border-white/10 bg-gray-900/95 px-4 py-2 shadow-2xl shadow-black/30 transition focus-within:border-red-400/70 focus-within:ring-2 focus-within:ring-red-500/30 {{ $size === 'large' ? 'min-h-14' : '' }}">
        <svg class="h-5 w-5 shrink-0 text-red-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.8" aria-hidden="true">
            <path d="m21 21-4.35-4.35" />
            <circle cx="10.5" cy="10.5" r="7.5" />
        </svg>
        <input id="movie-search-{{ $size }}" name="q" type="search" x-model="query" @focus="focused = true"
            @keydown.escape="focused = false" placeholder="Search movies, genre, language..."
            class="w-full border-0 bg-transparent text-sm text-white placeholder:text-gray-500 focus:ring-0 {{ $size === 'large' ? 'py-3 text-base' : 'py-2' }}">
        <button type="submit"
            class="rounded-full bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-300">Search</button>
    </div>
    @error('q')
        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
    @enderror

    <div x-cloak x-show="focused && filtered.length" x-transition @click.outside="focused = false"
        class="absolute left-0 right-0 top-full z-40 mt-3 overflow-hidden rounded-lg border border-white/10 bg-gray-900 shadow-2xl shadow-black/60">
        <template x-for="movie in filtered" :key="movie.title">
            <a :href="movie.url"
                class="flex items-center justify-between gap-4 border-b border-white/5 px-4 py-3 last:border-b-0 hover:bg-red-950/30">
                <span>
                    <span class="block text-sm font-bold text-white" x-text="movie.title"></span>
                    <span class="block text-xs text-gray-400" x-text="`${movie.genre} - ${movie.language}`"></span>
                </span>
                <span class="text-xs font-bold text-red-300">View</span>
            </a>
        </template>
    </div>
</form>