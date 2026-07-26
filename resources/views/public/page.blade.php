@extends('layouts.app')

@section('title', ($page->meta_title ?: $page->title) . ' | ' . ($siteSettings['site_name'] ?? 'BookMyMovie'))
@section('meta_description', $page->meta_description ?: $page->excerpt ?: ($siteSettings['default_meta_description'] ?? 'Book movie tickets, compare shows, reserve seats, and manage cinema bookings online with BookMyMovie.'))
@section('canonical', rtrim($siteSettings['canonical_base_url'] ?? 'https://bookmymovie.ahmershah.dev', '/') . ($page->canonical_path ?: request()->getPathInfo()))
@section('json_ld', json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $page->meta_title ?: $page->title,
    'description' => $page->meta_description ?: $page->excerpt,
    'url' => rtrim($siteSettings['canonical_base_url'] ?? 'https://bookmymovie.ahmershah.dev', '/') . ($page->canonical_path ?: request()->getPathInfo()),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))

@section('content')
    <section class="bg-gray-950 px-4 pb-20 pt-32 text-gray-100 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <div class="rounded-lg border border-white/10 bg-gradient-to-br from-gray-900 via-gray-950 to-black p-6 shadow-2xl shadow-black/40 sm:p-10">
                <p class="text-xs font-black uppercase tracking-[.24em] text-red-400">
                    {{ $page->hero_label ?: ($siteSettings['site_name'] ?? 'BookMyMovie') }}
                </p>
                <h1 class="mt-4 text-3xl font-black text-white sm:text-5xl">{{ $page->title }}</h1>
                @if($page->excerpt)
                    <p class="mt-5 max-w-3xl text-base leading-7 text-gray-300">{{ $page->excerpt }}</p>
                @endif
            </div>

            @if($page->body)
                <div class="mt-8 rounded-lg border border-white/10 bg-white/[.03] p-6 text-sm leading-7 text-gray-300 sm:p-8">
                    @foreach(preg_split('/\R{2,}/', trim($page->body)) as $paragraph)
                        <p class="mb-4 last:mb-0">{{ $paragraph }}</p>
                    @endforeach
                </div>
            @endif

            <div class="mt-8 grid gap-5 md:grid-cols-2">
                @foreach($page->sections ?? [] as $section)
                    <article class="rounded-lg border border-white/10 bg-gray-900/70 p-6">
                        <h2 class="text-lg font-black text-white">{{ $section['title'] ?? 'Information' }}</h2>
                        @if(! empty($section['body']))
                            <p class="mt-3 text-sm leading-6 text-gray-300">{{ $section['body'] }}</p>
                        @endif
                        @if(! empty($section['items']) && is_array($section['items']))
                            <ul class="mt-4 space-y-2 text-sm text-gray-300">
                                @foreach($section['items'] as $item)
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-red-500"></span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
