@extends('layouts.app')

@section('title', 'Compare Movies | BookMyMovie')
@section('meta_description', 'Compare up to four selected movies by rating, runtime, language, certificate, price, and booking links.')

@section('content')
    <section class="min-h-screen bg-gray-950 px-4 pb-20 pt-36 sm:px-6 lg:px-8" x-data="compareMoviesPage()" x-init="init()">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <x-section-heading eyebrow="Compare" title="Movie comparison"
                    description="Compare up to 4 movies selected from cards or detail pages. Your list is stored in this browser." />
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('movies.index') }}"
                        class="rounded-full border border-white/10 bg-white/5 px-5 py-3 text-sm font-black text-white hover:border-red-400/60 hover:bg-white/10">
                        Browse movies
                    </a>
                    <button type="button" @click="clear()"
                        class="rounded-full border border-red-500/30 bg-red-950/30 px-5 py-3 text-sm font-black text-red-100 hover:bg-red-900/50">
                        Clear compare
                    </button>
                </div>
            </div>

            <template x-if="movies.length === 0">
                <div class="mt-10 rounded-lg border border-white/10 bg-gray-900 p-8 text-center">
                    <h2 class="text-xl font-black text-white">No movies selected</h2>
                    <p class="mt-2 text-sm text-gray-400">Use the compare button on any movie card to add it here.</p>
                    <a href="{{ route('movies.index') }}"
                        class="mt-6 inline-flex rounded-full bg-red-600 px-6 py-3 text-sm font-black text-white hover:bg-red-500">
                        Explore movies
                    </a>
                </div>
            </template>

            <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-4" x-show="movies.length > 0">
                <template x-for="movie in movies" :key="movie.id">
                    <article class="flex min-h-full flex-col rounded-lg border border-white/10 bg-gray-900 p-4">
                        <div class="aspect-[9/16] overflow-hidden rounded-md bg-gray-950">
                            <img :src="movie.poster_url || '{{ asset('images/logo.png') }}'" :alt="movie.title"
                                class="h-full w-full object-cover" loading="lazy">
                        </div>

                        <div class="mt-4 flex flex-1 flex-col">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-black text-white" x-text="movie.title"></h2>
                                    <p class="mt-1 text-xs font-semibold text-gray-400" x-text="movie.genre || 'Cinema'"></p>
                                </div>
                                <button type="button" @click="remove(movie.id)"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-red-500/30 bg-red-950/30 text-red-100 hover:bg-red-900/50"
                                    aria-label="Remove movie from compare">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <dl class="mt-5 flex-1 divide-y divide-white/10 text-sm">
                                <div class="flex justify-between gap-3 py-3"><dt class="text-gray-400">Rating</dt><dd class="font-black text-amber-300" x-text="Number(movie.rating || 0).toFixed(1) + ' / 5'"></dd></div>
                                <div class="flex justify-between gap-3 py-3"><dt class="text-gray-400">Reviews</dt><dd class="font-bold text-white" x-text="movie.reviews || 0"></dd></div>
                                <div class="flex justify-between gap-3 py-3"><dt class="text-gray-400">Runtime</dt><dd class="font-bold text-white" x-text="movie.duration"></dd></div>
                                <div class="flex justify-between gap-3 py-3"><dt class="text-gray-400">Language</dt><dd class="font-bold text-white" x-text="movie.language"></dd></div>
                                <div class="flex justify-between gap-3 py-3"><dt class="text-gray-400">Certificate</dt><dd class="font-bold text-white" x-text="movie.certificate"></dd></div>
                                <div class="flex justify-between gap-3 py-3"><dt class="text-gray-400">From</dt><dd class="font-black text-gold" x-text="'PKR ' + formatPrice(movie.price)"></dd></div>
                            </dl>

                            <div class="mt-5 grid gap-2">
                                <a :href="movieUrl(movie.slug)"
                                    class="rounded-md bg-red-600 px-4 py-2.5 text-center text-xs font-black uppercase tracking-wide text-white hover:bg-red-500">
                                    Details
                                </a>
                                <a :href="seatUrl(movie)"
                                    class="rounded-md border border-white/10 bg-white/5 px-4 py-2.5 text-center text-xs font-black uppercase tracking-wide text-white hover:border-red-400/60 hover:bg-white/10">
                                    Add to cart
                                </a>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        function compareMoviesPage() {
            return {
                key: 'bookmymovie.compare.movies',
                movies: [],
                init() {
                    this.load();
                    window.addEventListener('bookmymovie-compare-updated', () => this.load());
                },
                load() {
                    this.movies = JSON.parse(localStorage.getItem(this.key) || '[]').slice(0, 4);
                },
                remove(id) {
                    this.movies = this.movies.filter(movie => Number(movie.id) !== Number(id));
                    localStorage.setItem(this.key, JSON.stringify(this.movies));
                },
                clear() {
                    this.movies = [];
                    localStorage.removeItem(this.key);
                },
                formatPrice(value) {
                    return Number(value || 0).toLocaleString();
                },
                movieUrl(slug) {
                    return `/movies/${slug}`;
                },
                seatUrl(movie) {
                    return movie.first_show_id ? `/movies/${movie.slug}/book/${movie.first_show_id}` : this.movieUrl(movie.slug);
                },
            };
        }
    </script>
@endpush
