@use('App\Support\Seo')
@php
    /*
    | Pages describe themselves with a $seo array (title, description, image,
    | image_alt, type, robots, canonical, schema[]) and optional $breadcrumbs.
    | Older views that still use @section('title') / @section('meta_description')
    | are supported as a fallback.
    */
    $seo = $seo ?? [];
    $siteName = $siteSettings['site_name'] ?? 'BookMyMovie';

    $titleSource = $seo['title']
        ?? (trim($__env->yieldContent('meta_title')) ?: trim($__env->yieldContent('title')))
        ?: ($siteSettings['default_meta_title'] ?? $siteName.' | Cinema Tickets, Showtimes & Seats');
    $pageTitle = Seo::title(html_entity_decode($titleSource, ENT_QUOTES));

    $descriptionSource = $seo['description']
        ?? trim($__env->yieldContent('meta_description'))
        ?: ($siteSettings['default_meta_description'] ?? 'Book cinema tickets online with live seat maps and honest prices.');
    $pageDescription = Seo::description(html_entity_decode($descriptionSource, ENT_QUOTES));

    // Sign in and create account are public entry points and may be indexed.
    $privateRoute = request()->routeIs('user.*', 'admin.*', 'password.*', 'movies.seats', 'oauth.*', 'payments.*')
        && ! request()->routeIs('user.login', 'user.register');
    $robots = $seo['robots'] ?? ($privateRoute ? 'noindex, nofollow' : (request()->routeIs('search') ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1'));

    $page = (int) request()->query('page', 1);
    $canonical = $seo['canonical'] ?? Seo::url(request()->getPathInfo()).($page > 1 ? '?page='.$page : '');
    $ogImage = $seo['image'] ?? Seo::defaultImage();
    $ogImageAlt = $seo['image_alt'] ?? $siteName.': cinema tickets, showtimes and seats';

    $crumbs = $breadcrumbs ?? \App\Support\Breadcrumbs::for(request());
    $schemaGraph = Seo::graph([
        Seo::organization($siteSettings ?? []),
        Seo::website($siteSettings ?? []),
        count($crumbs) > 1 ? Seo::breadcrumbs($crumbs) : null,
        ...($seo['schema'] ?? []),
    ]);
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title inertia>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}" inertia="description">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="author" content="Syed Ahmer Shah">
<meta name="application-name" content="{{ $siteName }}">
<meta name="format-detection" content="telephone=no">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="en_PK">
<meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:secure_url" content="{{ $ogImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="{{ Seo::IMAGE_SIZE }}">
<meta property="og:image:height" content="{{ Seo::IMAGE_SIZE }}">
<meta property="og:image:alt" content="{{ $ogImageAlt }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $ogImageAlt }}">

<link rel="icon" href="{{ asset('images/favicon/favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('images/site.webmanifest') }}">
<link rel="sitemap" type="application/xml" href="{{ asset('sitemap.xml') }}">
<meta name="theme-color" content="#0a0a0a">
<meta name="color-scheme" content="dark light">
@if (! empty($seo['preload']['href']))
<link rel="preload" as="image" href="{{ $seo['preload']['href'] }}" @if (! empty($seo['preload']['srcset'])) imagesrcset="{{ $seo['preload']['srcset'] }}" imagesizes="{{ $seo['preload']['sizes'] }}" @endif fetchpriority="high">
@endif

<script type="application/ld+json" nonce="{{ $cspNonce ?? '' }}">{!! $schemaGraph !!}</script>
@stack('head')
