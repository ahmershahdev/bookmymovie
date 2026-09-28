<!DOCTYPE html>
<html lang="en" class="bg-ink">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · @yield('title') | BookMyMovie</title>
    <link rel="icon" href="{{ asset('images/favicon/favicon.ico') }}" sizes="any">
    <meta name="theme-color" content="#0a0a0a">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-ink font-sans text-paper antialiased">
    <main class="relative isolate flex min-h-screen flex-col overflow-hidden">
        <p class="pointer-events-none absolute inset-x-0 top-1/2 -z-10 -translate-y-1/2 select-none text-center text-[42vw] font-black leading-none text-paper/[.04] [font-stretch:62%]" aria-hidden="true">@yield('code')</p>
        <header class="shell flex h-20 items-center">
            <a href="{{ url('/') }}" class="text-[1.05rem] font-extrabold uppercase [font-stretch:125%]">Book<span class="text-volt">My</span>Movie</a>
        </header>
        <div class="shell flex flex-1 flex-col justify-center pb-24">
            <p class="label label-volt">Error @yield('code')</p>
            <h1 class="display mt-5 max-w-5xl text-[clamp(4rem,11vw,10rem)]">@yield('title')</h1>
            <p class="lede mt-6 max-w-xl">@yield('message')</p>
            <div class="mt-10 flex flex-wrap gap-2">
                @section('actions')
                    <a href="{{ url('/') }}" class="btn btn-primary">Back to home</a>
                    <a href="{{ url('/movies') }}" class="btn btn-ghost">Browse films</a>
                @show
            </div>
        </div>
    </main>
</body>
</html>
