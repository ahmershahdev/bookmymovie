<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BookMyMovie Admin')</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="min-h-screen bg-gray-950 text-gray-100 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
        <aside class="border-b border-white/10 bg-gray-900 p-5 lg:border-b-0 lg:border-r">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="BookMyMovie logo"
                    class="h-12 w-12 rounded-md object-cover">
                <span class="text-xl font-black text-white">BookMyMovie</span>
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
