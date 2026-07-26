@extends('layouts.admin')

@section('title', 'Admin Dashboard | BookMyMovie')

@section('content')

    @php
        // 2. Premium UI/UX Variables
        $baseTransition = 'transition-all duration-300 ease-in-out';

        // Inputs: Subtle background, smooth borders, glowing focus state
        $inputClass = "mt-2 block w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white outline-none placeholder:text-gray-500 hover:border-white/20 focus:border-red-500 focus:bg-white/10 focus:ring-4 focus:ring-red-500/20 {$baseTransition}";

        // File Inputs: Clean custom button style
        $fileClass = "block w-full cursor-pointer rounded-lg border border-dashed border-white/15 bg-black/20 p-4 text-sm text-gray-400 file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-red-500/10 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-red-300 hover:border-red-400/40 hover:bg-red-500/5 hover:file:bg-red-500/20 {$baseTransition}";

        // Textareas: Matches inputs with appropriate padding
        $areaClass = "mt-2 block w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none placeholder:text-gray-500 hover:border-white/20 focus:border-red-500 focus:bg-white/10 focus:ring-4 focus:ring-red-500/20 {$baseTransition}";

        // Buttons: Gradient, shadow glow, scale on hover
        $buttonClass = "inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-red-600 to-red-500 px-6 py-3 text-sm font-bold tracking-wide text-white shadow-lg shadow-red-600/20 hover:scale-[1.02] hover:shadow-red-600/40 active:scale-95 {$baseTransition}";

        // Panels: Glassmorphism, soft borders, deep shadows
        $panelClass = "scroll-mt-24 rounded-2xl border border-white/5 bg-gray-900/40 p-5 shadow-2xl backdrop-blur-xl sm:p-8 {$baseTransition}";

        // Label typography helper
        $labelClass = "mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-400";

        $selectedGenreIds = $selectedMovie?->genres?->pluck('id')->all() ?? [];
        $movieUploadCards = [
            'poster' => [
                'column' => 'poster_image',
                'field' => 'poster_upload',
                'title' => 'Poster image',
                'ratio' => '9:16 vertical',
                'usage' => 'Shown on movie cards, movie detail poster, search, wishlist, and catalog pages.',
                'previewClass' => 'aspect-[9/16] max-w-[180px]',
            ],
            'hero' => [
                'column' => 'hero_image',
                'field' => 'hero_upload',
                'title' => 'Carousel image',
                'ratio' => '16:9 widescreen',
                'usage' => 'Shown in the home carousel and movie detail backdrop.',
                'previewClass' => 'aspect-video',
            ],
        ];
    @endphp

    <section class="space-y-8">
        {{-- Header --}}
        <header id="overview" class="scroll-mt-24 grid gap-6 border-b border-white/10 pb-8 xl:grid-cols-[1fr_auto] xl:items-end">
            <div>
                <span class="inline-block rounded-full bg-red-500/10 px-3 py-1 text-xs font-bold uppercase tracking-widest text-red-400">
                    Admin Panel
                </span>
                <h1 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">Operations Dashboard</h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-gray-400">
                    Manage the production catalog, hero carousel, seat pricing, bookings, users, SEO, and global site settings.
                </p>
            </div>
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-6 py-3 text-sm font-bold text-white hover:bg-white/10 hover:border-white/20 {{ $baseTransition }}">
                View Live Site
            </a>
        </header>

        {{-- Error Handling --}}
        @if($errors->any())
            <div class="flex items-center gap-3 rounded-xl border-l-4 border-red-500 bg-red-500/10 px-5 py-4 text-sm font-medium text-red-200 shadow-lg backdrop-blur-md">
                <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Stats Grid --}}
        <section class="{{ $panelClass }}">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                <x-stat-card label="Revenue" value="PKR {{ number_format($stats['revenue']) }}" />
                <x-stat-card label="Bookings" :value="$stats['bookings']" />
                <x-stat-card label="Users" :value="$stats['users']" />
                <x-stat-card label="Active shows" :value="$stats['activeShows']" />
                <x-stat-card label="Pending reviews" :value="$stats['pendingReviews']" />
                <x-stat-card label="Top movie" :value="$stats['topMovie']" />
            </div>
            <div class="mt-8 grid gap-6 xl:grid-cols-2">
                <x-admin-list :items="$moviesList" action="Recent movies" />
                <x-admin-list :items="$customerList" action="Top customers" />
            </div>
        </section>

        {{-- Movies Workspace --}}
        <section id="movies" class="{{ $panelClass }} space-y-8">
            <x-section-heading eyebrow="Movies" title="Movie Catalog Workspace" description="Create, edit, remove, restore, upload media, and control home hero placement." />

            <form method="GET" action="{{ route('admin.dashboard') }}#movies" class="grid gap-4 rounded-xl border border-white/5 bg-black/20 p-5 sm:grid-cols-[1fr_auto]">
                <div>
                    <select name="movie_id" class="{{ $inputClass }} mt-0">
                        @foreach($movieEditorList as $movieOption)
                            <option value="{{ $movieOption->id }}" @selected($selectedMovie?->id === $movieOption->id)>
                                {{ $movieOption->title }} &mdash; {{ $movieOption->hero_carousel_enabled ? 'Hero' : 'Catalog' }} &mdash; {{ $movieOption->rating_mode }} rating
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="{{ $buttonClass }}">Load Movie</button>
            </form>

            @if($selectedMovie)
                <form method="POST" action="{{ route('admin.dashboard') }}#movies" enctype="multipart/form-data" class="grid gap-6 rounded-xl border border-white/5 bg-black/20 p-6 lg:grid-cols-2">
                    @csrf
                    <input type="hidden" name="_action" value="update_movie">
                    <input type="hidden" name="movie_id" value="{{ $selectedMovie->id }}">

                    <label class="block"><span class="{{ $labelClass }}">Title</span><input name="title" value="{{ old('title', $selectedMovie->title) }}" class="{{ $inputClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Slug</span><input name="slug" value="{{ old('slug', $selectedMovie->slug) }}" class="{{ $inputClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Language</span><input name="language" value="{{ old('language', $selectedMovie->language) }}" class="{{ $inputClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Duration (mins)</span><input name="duration_minutes" type="number" min="1" value="{{ old('duration_minutes', $selectedMovie->duration_minutes) }}" class="{{ $inputClass }}"></label>

                    <label class="block"><span class="{{ $labelClass }}">Certificate</span>
                        <select name="certificate_rating" class="{{ $inputClass }}">
                            @foreach(['U', 'UA', 'A', 'S', 'G', 'PG', 'PG-13', 'R'] as $rating)
                                <option value="{{ $rating }}" @selected(old('certificate_rating', $selectedMovie->certificate_rating) === $rating)>{{ $rating }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block"><span class="{{ $labelClass }}">Status</span>
                        <select name="status" class="{{ $inputClass }}">
                            @foreach(['coming_soon' => 'Coming soon', 'now_showing' => 'Now showing', 'ended' => 'Ended'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $selectedMovie->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block"><span class="{{ $labelClass }}">Release date</span><input name="release_date" type="date" value="{{ old('release_date', optional($selectedMovie->release_date)->format('Y-m-d')) }}" class="{{ $inputClass }}"></label>

                    <label class="block"><span class="{{ $labelClass }}">Genres</span>
                        <select name="genre_ids[]" multiple class="{{ $inputClass }} min-h-[7rem] py-3 custom-scrollbar">
                            @foreach($genres as $genre)
                                <option value="{{ $genre->id }}" @selected(in_array($genre->id, old('genre_ids', $selectedGenreIds)))>{{ $genre->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block"><span class="{{ $labelClass }}">Base price</span><input name="base_price" type="number" step="0.01" value="{{ old('base_price', $selectedMovie->base_price) }}" class="{{ $inputClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Sale price</span><input name="sale_price" type="number" step="0.01" value="{{ old('sale_price', $selectedMovie->sale_price) }}" class="{{ $inputClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Trailer URL</span><input name="trailer_url" value="{{ old('trailer_url', $selectedMovie->trailer_url) }}" class="{{ $inputClass }}"></label>

                    <label class="block"><span class="{{ $labelClass }}">Rating mode</span>
                        <select name="rating_mode" class="{{ $inputClass }}">
                            <option value="real" @selected(old('rating_mode', $selectedMovie->rating_mode) === 'real')>Use real reviews</option>
                            <option value="fake" @selected(old('rating_mode', $selectedMovie->rating_mode) === 'fake')>Use manual rating</option>
                        </select>
                    </label>

                    <label class="block"><span class="{{ $labelClass }}">Manual rating</span><input name="fake_average_rating" type="number" min="1" max="5" step="0.1" value="{{ old('fake_average_rating', $selectedMovie->fake_average_rating) }}" class="{{ $inputClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Manual reviews count</span><input name="fake_total_reviews" type="number" min="0" value="{{ old('fake_total_reviews', $selectedMovie->fake_total_reviews) }}" class="{{ $inputClass }}"></label>

                    <label class="block lg:col-span-2"><span class="{{ $labelClass }}">Description</span><textarea name="description" rows="4" class="{{ $areaClass }}">{{ old('description', $selectedMovie->description) }}</textarea></label>

                    {{-- Image Uploads --}}
                    @foreach($movieUploadCards as $key => $upload)
                        @php
                            $currentImage = $key === 'hero'
                                ? ($selectedMovie->hero_image ?: $selectedMovie->banner_image)
                                : $selectedMovie->{$upload['column']};
                            $currentUrl = $selectedMovie->publicMediaUrl($currentImage);
                        @endphp
                        <div class="rounded-lg border border-white/10 bg-white/[0.025] p-5 {{ $baseTransition }} hover:border-white/20 hover:bg-white/[0.04]">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <span class="{{ $labelClass }}">{{ $upload['title'] }}</span>
                                    <p class="text-sm font-semibold text-white">{{ $upload['ratio'] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-gray-400">{{ $upload['usage'] }}</p>
                                    <p class="mt-2 text-xs font-medium text-gray-500">PNG, JPG, JPEG, or WebP only. Maximum 3 MB. Saved as optimized WebP.</p>
                                </div>
                                @if($currentUrl)
                                    <div class="relative w-full overflow-hidden rounded-lg border border-white/10 bg-black/30 sm:w-40 {{ $upload['previewClass'] }}">
                                        <img src="{{ $currentUrl }}" alt="{{ $upload['title'] }} preview" class="h-full w-full object-cover">
                                    </div>
                                @endif
                            </div>
                            <div class="mt-4">
                                <input name="{{ $upload['field'] }}" type="file" accept="image/png,image/jpeg,image/webp" class="{{ $fileClass }}">
                            </div>
                            <p class="mt-3 text-xs font-medium text-gray-500">Upload a new file here to replace the current {{ strtolower($upload['title']) }}.</p>
                        </div>
                    @endforeach

                    {{-- Hero Settings --}}
                    <div class="grid gap-6 rounded-xl border border-white/5 bg-white/[0.02] p-5 lg:col-span-2 lg:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-white lg:col-span-2">
                            <input type="checkbox" name="hero_carousel_enabled" value="1" @checked(old('hero_carousel_enabled', $selectedMovie->hero_carousel_enabled)) class="h-5 w-5 rounded border-white/20 bg-black text-red-500 focus:ring-red-500/30">
                            Promote in home hero carousel
                        </label>
                        <label class="block"><span class="{{ $labelClass }}">Hero order</span><input name="hero_sort_order" type="number" min="0" value="{{ old('hero_sort_order', $selectedMovie->hero_sort_order) }}" class="{{ $inputClass }}"></label>
                        <label class="block"><span class="{{ $labelClass }}">Hero eyebrow</span><input name="hero_eyebrow" value="{{ old('hero_eyebrow', $selectedMovie->hero_eyebrow) }}" class="{{ $inputClass }}"></label>
                        <label class="block lg:col-span-2"><span class="{{ $labelClass }}">Hero tagline</span><input name="hero_tagline" maxlength="220" value="{{ old('hero_tagline', $selectedMovie->hero_tagline) }}" class="{{ $inputClass }}"></label>
                    </div>

                    {{-- Metadata --}}
                    <label class="block"><span class="{{ $labelClass }}">Meta title</span><input name="meta_title" maxlength="60" value="{{ old('meta_title', $selectedMovie->meta_title) }}" class="{{ $inputClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Meta description</span><input name="meta_description" maxlength="160" value="{{ old('meta_description', $selectedMovie->meta_description) }}" class="{{ $inputClass }}"></label>

                    <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-white lg:col-span-2">
                        <input type="checkbox" name="kids_discount_eligible" value="1" @checked(old('kids_discount_eligible', $selectedMovie->kids_discount_eligible)) class="h-5 w-5 rounded border-white/20 bg-black text-red-500 focus:ring-red-500/30">
                        Eligible for kids discount
                    </label>

                    <div class="mt-4 flex flex-wrap gap-4 lg:col-span-2">
                        <button class="{{ $buttonClass }}">Update Movie</button>
                    </div>
                </form>

                {{-- Delete Form --}}
                <form method="POST" action="{{ route('admin.dashboard') }}#movies" class="rounded-xl border border-red-500/20 bg-red-950/10 p-6 backdrop-blur-sm">
                    @csrf
                    <input type="hidden" name="_action" value="delete_movie">
                    <input type="hidden" name="movie_id" value="{{ $selectedMovie->id }}">
                    <p class="mb-4 text-sm text-red-300">Danger Zone: This action removes the movie from the active catalog.</p>
                    <button class="rounded-xl border border-red-500/40 bg-red-950/50 px-5 py-2.5 text-sm font-bold text-red-200 hover:bg-red-900 hover:text-white {{ $baseTransition }}">
                        Remove {{ $selectedMovie->title }}
                    </button>
                </form>
            @endif

            <hr class="my-10 border-white/10" />
            <h3 class="text-xl font-black text-white">Add New Movie</h3>

            <form method="POST" action="{{ route('admin.dashboard') }}#movies" enctype="multipart/form-data" class="grid gap-6 rounded-xl border border-white/5 bg-black/20 p-6 lg:grid-cols-2">
                @csrf
                <input type="hidden" name="_action" value="store_movie">
                <label class="block"><span class="{{ $labelClass }}">New movie title</span><input name="title" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Slug</span><input name="slug" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Language</span><input name="language" value="English" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Duration (mins)</span><input name="duration_minutes" type="number" min="1" value="120" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Certificate</span><select name="certificate_rating" class="{{ $inputClass }}"><option value="UA">UA</option><option value="U">U</option><option value="A">A</option><option value="S">S</option><option value="G">G</option><option value="PG">PG</option><option value="PG-13">PG-13</option><option value="R">R</option></select></label>
                <label class="block"><span class="{{ $labelClass }}">Status</span><select name="status" class="{{ $inputClass }}"><option value="coming_soon">Coming soon</option><option value="now_showing">Now showing</option><option value="ended">Ended</option></select></label>
                <label class="block"><span class="{{ $labelClass }}">Release date</span><input name="release_date" type="date" value="{{ now()->toDateString() }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Genres</span><select name="genre_ids[]" multiple class="{{ $inputClass }} min-h-[7rem] py-3 custom-scrollbar">@foreach($genres as $genre)<option value="{{ $genre->id }}">{{ $genre->name }}</option>@endforeach</select></label>
                <label class="block"><span class="{{ $labelClass }}">Base price</span><input name="base_price" type="number" step="0.01" value="2500" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Sale price</span><input name="sale_price" type="number" step="0.01" class="{{ $inputClass }}"></label>
                @foreach($movieUploadCards as $upload)
                    <div class="rounded-lg border border-white/10 bg-white/[0.025] p-5">
                        <span class="{{ $labelClass }}">{{ $upload['title'] }}</span>
                        <p class="text-sm font-semibold text-white">{{ $upload['ratio'] }}</p>
                        <p class="mt-1 text-xs leading-5 text-gray-400">{{ $upload['usage'] }}</p>
                        <p class="mt-2 text-xs font-medium text-gray-500">PNG, JPG, JPEG, or WebP only. Maximum 3 MB. Saved as optimized WebP.</p>
                        <input name="{{ $upload['field'] }}" type="file" accept="image/png,image/jpeg,image/webp" class="{{ $fileClass }} mt-4" required>
                    </div>
                @endforeach
                <label class="block"><span class="{{ $labelClass }}">Trailer URL</span><input name="trailer_url" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Rating mode</span><select name="rating_mode" class="{{ $inputClass }}"><option value="real">Use real reviews</option><option value="fake">Use manual rating</option></select></label>
                <label class="block"><span class="{{ $labelClass }}">Hero order</span><input name="hero_sort_order" type="number" min="0" value="0" class="{{ $inputClass }}"></label>
                <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-white lg:col-span-2"><input type="checkbox" name="hero_carousel_enabled" value="1" class="h-5 w-5 rounded border-white/20 bg-black text-red-500 focus:ring-red-500/30">Show in home hero carousel</label>
                <label class="block"><span class="{{ $labelClass }}">Hero eyebrow</span><input name="hero_eyebrow" class="{{ $inputClass }}"></label>
                <label class="block lg:col-span-2"><span class="{{ $labelClass }}">Hero tagline</span><input name="hero_tagline" maxlength="220" class="{{ $inputClass }}"></label>
                <label class="block lg:col-span-2"><span class="{{ $labelClass }}">Description</span><textarea name="description" rows="4" class="{{ $areaClass }}"></textarea></label>

                <div class="mt-2 lg:col-span-2">
                    <button class="{{ $buttonClass }}">Create Movie</button>
                </div>
            </form>

            {{-- Trashed Movies --}}
            @if($trashedMovies->isNotEmpty())
                <div class="rounded-xl border border-white/5 bg-white/[0.02] p-6">
                    <h3 class="text-lg font-black tracking-tight text-white">Removed Movies Archive</h3>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach($trashedMovies as $movie)
                            <form method="POST" action="{{ route('admin.dashboard') }}#movies" class="flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-black/40 p-4 transition hover:bg-black/60">
                                @csrf
                                <input type="hidden" name="_action" value="restore_movie">
                                <input type="hidden" name="movie_id" value="{{ $movie->id }}">
                                <span class="text-sm font-bold text-gray-300">{{ $movie->title }}</span>
                                <button class="rounded-lg border border-white/20 px-4 py-2 text-xs font-bold text-white hover:border-red-500 hover:text-red-400 {{ $baseTransition }}">Restore</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Website Settings --}}
        <section id="settings" class="{{ $panelClass }} space-y-6">
            <x-section-heading eyebrow="Website" title="Global Site Settings" description="Configure core branding, defaults, and communications." />
            <form method="POST" action="{{ route('admin.dashboard') }}#settings" class="grid gap-6 lg:grid-cols-2">
                @csrf
                <input type="hidden" name="_action" value="update_settings">
                <label class="block"><span class="{{ $labelClass }}">Website name</span><input name="site_name" value="{{ old('site_name', $settings['site_name'] ?? 'BookMyMovie') }}" maxlength="60" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Canonical base URL</span><input name="canonical_base_url" value="{{ old('canonical_base_url', $settings['canonical_base_url'] ?? 'https://bookmymovie.ahmershah.dev') }}" class="{{ $inputClass }}"></label>
                <label class="block lg:col-span-2"><span class="{{ $labelClass }}">Default meta title</span><input name="default_meta_title" value="{{ old('default_meta_title', $settings['default_meta_title'] ?? 'BookMyMovie - Book Cinema Tickets Online') }}" maxlength="60" class="{{ $inputClass }}"></label>
                <label class="block lg:col-span-2"><span class="{{ $labelClass }}">Default meta description</span><textarea name="default_meta_description" rows="3" maxlength="160" class="{{ $areaClass }}">{{ old('default_meta_description', $settings['default_meta_description'] ?? '') }}</textarea></label>
                <label class="block"><span class="{{ $labelClass }}">Support email</span><input name="support_email" type="email" value="{{ old('support_email', $settings['support_email'] ?? '') }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Home ticker messages</span><input name="home_ticker_messages" value="{{ old('home_ticker_messages', $settings['home_ticker_messages'] ?? '') }}" placeholder="Message 1 | Message 2" class="{{ $inputClass }}"></label>
                <div class="mt-2 lg:col-span-2"><button class="{{ $buttonClass }}">Save Settings</button></div>
            </form>
        </section>

        {{-- SEO Management --}}
        <section id="seo" class="{{ $panelClass }} space-y-6">
            <x-section-heading eyebrow="SEO" title="Page Metadata" description="Optimize titles (under 60 chars) and descriptions (under 160 chars) for search engines." />
            <form method="POST" action="{{ route('admin.dashboard') }}#seo" class="space-y-6">
                @csrf
                <input type="hidden" name="_action" value="update_pages">
                @foreach($contentPages as $page)
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-6 shadow-sm">
                        <input type="hidden" name="pages[{{ $loop->index }}][id]" value="{{ $page->id }}">
                        <div class="mb-4 text-lg font-bold text-white capitalize">{{ $page->slug }} Page</div>
                        <div class="grid gap-6 lg:grid-cols-2">
                            <label class="block"><span class="{{ $labelClass }}">Title</span><input name="pages[{{ $loop->index }}][title]" value="{{ old("pages.{$loop->index}.title", $page->title) }}" class="{{ $inputClass }}"></label>
                            <label class="block"><span class="{{ $labelClass }}">Canonical Path</span><input name="pages[{{ $loop->index }}][canonical_path]" value="{{ old("pages.{$loop->index}.canonical_path", $page->canonical_path) }}" placeholder="/example" class="{{ $inputClass }}"></label>
                            <label class="block"><span class="{{ $labelClass }}">Meta Title</span><input name="pages[{{ $loop->index }}][meta_title]" value="{{ old("pages.{$loop->index}.meta_title", $page->meta_title) }}" maxlength="60" class="{{ $inputClass }}"></label>
                            <label class="block"><span class="{{ $labelClass }}">Meta Description</span><input name="pages[{{ $loop->index }}][meta_description]" value="{{ old("pages.{$loop->index}.meta_description", $page->meta_description) }}" maxlength="160" class="{{ $inputClass }}"></label>
                            <label class="block lg:col-span-2"><span class="{{ $labelClass }}">Excerpt</span><textarea name="pages[{{ $loop->index }}][excerpt]" rows="2" class="{{ $areaClass }}">{{ old("pages.{$loop->index}.excerpt", $page->excerpt) }}</textarea></label>
                        </div>
                    </div>
                @endforeach
                <button class="{{ $buttonClass }}">Update SEO Data</button>
            </form>
        </section>

        {{-- Shows & Ticketing --}}
        <section id="shows" class="{{ $panelClass }} space-y-8">
            <x-section-heading eyebrow="Shows" title="Schedule & Pricing" description="Manage theatrical runs and dynamic row-by-row seat pricing." />

            <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
                <div class="rounded-xl border border-white/5 bg-black/20 p-6">
                    <h3 class="text-lg font-black tracking-tight text-white">{{ $selectedMovie?->title ?? 'Selected Movie' }} Showtimes</h3>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @forelse($selectedMovieShows as $showtime)
                            <article class="flex flex-col justify-between rounded-xl border border-white/10 bg-white/[0.02] p-4 transition hover:bg-white/[0.04]">
                                <div>
                                    <p class="text-sm font-bold text-white">{{ $showtime->theater_name }} &mdash; {{ $showtime->screen_name }}</p>
                                    <p class="mt-1.5 text-xs text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($showtime->show_date)->format('D, M j, Y') }} 
                                        <span class="text-white">&bull;</span> 
                                        {{ \Illuminate\Support\Carbon::parse($showtime->show_time)->format('h:i A') }}
                                    </p>
                                </div>
                                <div class="mt-4 flex items-center justify-between text-xs font-bold uppercase tracking-wider">
                                    <span class="text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-md">{{ $showtime->available_seats }} Avail</span>
                                    <span class="text-gray-400 bg-gray-500/10 px-2 py-1 rounded-md">{{ $showtime->booked_seats }} Booked</span>
                                </div>
                            </article>
                        @empty
                            <div class="col-span-full rounded-xl border border-white/5 bg-white/[0.02] p-6 text-center text-sm font-medium text-gray-400">
                                No upcoming scheduled showtimes for the selected movie.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-white/5 bg-black/20 p-6">
                    <h3 class="text-lg font-black tracking-tight text-white">Seat Tier Benefits</h3>
                    <div class="mt-5 space-y-4">
                        @forelse($rowPriceBenefits as $tier)
                            <div class="rounded-xl border border-white/10 bg-white/[0.02] p-4 transition hover:bg-white/[0.04]">
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-sm font-bold text-white">Row {{ $tier->row_label }} &mdash; <span class="text-gray-300 font-medium">{{ $tier->tier_name }}</span></p>
                                    <p class="text-sm font-black text-amber-400">PKR {{ number_format((float) ($tier->sale_price ?: $tier->price)) }}</p>
                                </div>
                                <p class="mt-2 text-xs leading-relaxed text-gray-400">{{ $tier->benefits }}</p>
                            </div>
                        @empty
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-6 text-center text-sm font-medium text-gray-400">
                                Run migrations and seed row prices to populate the A-F benefits ladder.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="pt-4"><x-admin-list :items="$showsList" action="Latest schedule" /></div>
        </section>

        {{-- Short Sections --}}
        <section id="bookings" class="{{ $panelClass }}">
            <x-section-heading eyebrow="Bookings" title="Recent Transactions" description="Monitor the latest ticket sales." />
            <div class="mt-6"><x-admin-list :items="$bookingsList" action="Open" /></div>
        </section>

        <section id="coupons" class="{{ $panelClass }} space-y-6">
            <x-section-heading eyebrow="Coupons" title="Discount Management" description="Generate single-use percentage checkout codes." />
            <form method="POST" action="{{ route('admin.dashboard') }}#coupons" class="grid items-end gap-4 rounded-xl border border-white/5 bg-black/20 p-6 md:grid-cols-3">
                @csrf
                <input type="hidden" name="_action" value="store_coupon">
                <label class="block"><span class="{{ $labelClass }}">Code</span><input name="code" class="{{ $inputClass }} mt-1"></label>
                <label class="block"><span class="{{ $labelClass }}">Discount %</span><input name="discount" type="number" class="{{ $inputClass }} mt-1"></label>
                <button class="{{ $buttonClass }} w-full">Create Coupon</button>
            </form>
            <x-admin-list :items="$couponsList" action="Code" />
        </section>

        <section id="users" class="{{ $panelClass }}">
            <x-section-heading eyebrow="Users" title="Customer Accounts" description="Recent registrations and accounts." />
            <div class="mt-6"><x-admin-list :items="$usersList" action="Manage" /></div>
        </section>

        <section id="reviews" class="{{ $panelClass }}">
            <x-section-heading eyebrow="Reviews" title="Content Moderation" description="Monitor and moderate user film reviews." />
            <div class="mt-6"><x-admin-list :items="$reviewsList" action="Moderate" /></div>
        </section>

        <section id="messages" class="{{ $panelClass }} space-y-6">
            <x-section-heading eyebrow="Messages" title="Global Notifications" description="Broadcast an in-app alert to all registered users." />
            <form method="POST" action="{{ route('admin.dashboard') }}#messages" class="rounded-xl border border-white/5 bg-black/20 p-6">
                @csrf
                <input type="hidden" name="_action" value="send_notification">
                <label class="block mb-4"><span class="{{ $labelClass }}">Broadcast Message</span><textarea name="message" rows="4" class="{{ $areaClass }}"></textarea></label>
                <button class="{{ $buttonClass }}">Send Notification</button>
            </form>
        </section>

        <section id="profile" class="{{ $panelClass }} space-y-6">
            <x-section-heading eyebrow="Profile" title="Administrator Profile" description="Manage your credentials and security." />
            <form method="POST" action="{{ route('admin.dashboard') }}#profile" class="grid gap-6 rounded-xl border border-white/5 bg-black/20 p-6 md:grid-cols-2">
                @csrf
                <input type="hidden" name="_action" value="update_profile">
                <label class="block"><span class="{{ $labelClass }}">Full Name</span><input name="name" value="{{ old('name', $admin->name) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="{{ $labelClass }}">Email Address</span><input name="email" type="email" value="{{ old('email', $admin->email) }}" class="{{ $inputClass }}"></label>
                <label class="block md:col-span-2"><span class="{{ $labelClass }}">New Password (Optional)</span><input name="password" type="password" placeholder="Leave blank to keep current" class="{{ $inputClass }}"></label>
                <div class="mt-2 md:col-span-2"><button class="{{ $buttonClass }}">Save Changes</button></div>
            </form>
        </section>
    </section>
@endsection
