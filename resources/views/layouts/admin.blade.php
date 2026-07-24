<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($siteSettings['site_name'] ?? 'BookMyMovie') . ' Admin')</title>
    <meta name="csp-nonce" content="{{ $cspNonce ?? '' }}">
    <link rel="icon" href="{{ asset('images/favicon/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('images/site.webmanifest') }}">
    <meta name="theme-color" content="#05070d">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script nonce="{{ $cspNonce ?? '' }}" defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="min-h-screen bg-gray-950 text-gray-100 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
        <aside class="border-b border-white/10 bg-gray-900 p-5 lg:border-b-0 lg:border-r">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="BookMyMovie logo"
                    class="h-12 w-12 rounded-md object-cover">
                <span class="text-xl font-black text-white">{{ $siteSettings['site_name'] ?? 'BookMyMovie' }}</span>
            </a>
            <p class="mt-6 text-xs font-black uppercase tracking-[.22em] text-red-400">Admin Panel</p>
        </aside>
        <main class="p-4 sm:p-6 lg:p-8">
            @if(session('status'))
                <p class="mb-4 rounded-md border border-green-500/30 bg-green-950 px-4 py-3 text-sm font-bold text-green-100">
                    {{ session('status') }}
                </p>
            @endif
            @yield('content')
        </main>
    </div>
</body>

</html>
