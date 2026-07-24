@extends('layouts.app')

@section('title', 'Movies | BookMyMovie')

@section('content')
    <section class="bg-gray-950 px-4 pb-16 pt-36 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <x-section-heading eyebrow="Movies" title="All movies"
                description="Filter by genre, language, certificate, and status." />
            <form method="GET" action="{{ route('movies.index') }}"
                class="mt-8 grid gap-4 rounded-lg border border-white/10 bg-gray-900 p-4 lg:grid-cols-5">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[.18em] text-gray-400">Genre</span>
                    <select name="genre"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-sm text-white focus:border-red-400 focus:ring-red-400">
                        <option value="">All genres</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre->slug }}" @selected(request('genre') === $genre->slug)>{{ $genre->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[.18em] text-gray-400">Language</span>
                    <select name="language"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-sm text-white focus:border-red-400 focus:ring-red-400">
                        <option value="">All languages</option>
                        @foreach($languages as $language)
                            <option value="{{ $language }}" @selected(request('language') === $language)>{{ $language }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[.18em] text-gray-400">Status</span>
                    <select name="status"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-sm text-white focus:border-red-400 focus:ring-red-400">
                        <option value="">All status</option>
                        @foreach(['now_showing' => 'Now Showing', 'coming_soon' => 'Coming Soon', 'ended' => 'Ended'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[.18em] text-gray-400">Certificate</span>
                    <select name="certificate"
                        class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-sm text-white focus:border-red-400 focus:ring-red-400">
                        <option value="">All certificates</option>
                        @foreach($certificates as $certificate)
                            <option value="{{ $certificate }}" @selected(request('certificate') === $certificate)>{{ $certificate }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="self-end rounded-md bg-red-600 px-5 py-2.5 text-sm font-black text-white hover:bg-red-500">Apply</button>
            </form>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($movieCards as $movie)
                    <x-movie-card :movie="$movie" />
                @empty
                    <p class="rounded-lg border border-white/10 bg-gray-900 p-6 text-gray-300">No movies found.</p>
                @endforelse
            </div>
            <div class="mt-8">
                {{ $moviesPaginator->links() }}
            </div>
        </div>
    </section>
@endsection
