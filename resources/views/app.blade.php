<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}" class="bg-ink">
<head>
    @include('partials.head')
    {{-- The two text faces start downloading with the HTML, so text does not re-flow when they arrive (CLS). --}}
    @unless (file_exists(public_path('hot')))
        @foreach (['node_modules/@fontsource-variable/archivo/files/archivo-latin-wdth-normal.woff2', 'node_modules/@fontsource-variable/geist-mono/files/geist-mono-latin-wght-normal.woff2'] as $font)
            @php($fontUrl = rescue(fn () => \Illuminate\Support\Facades\Vite::asset($font), null, false))
            @if ($fontUrl)
    <link rel="preload" as="font" type="font/woff2" href="{{ $fontUrl }}" crossorigin>
            @endif
        @endforeach
    @endunless
    {{-- Apply the saved or system theme before first paint, so there is no flash. --}}
    <script nonce="{{ $cspNonce ?? '' }}">(function(){try{var t=localStorage.getItem('bmm-theme');if(t!=='light'&&t!=='dark'){t=matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'}document.documentElement.dataset.theme=t}catch(e){document.documentElement.dataset.theme='dark'}})();</script>
    @routes(null, $cspNonce ?? null)
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    @inertiaHead
</head>
<body class="min-h-screen bg-ink font-sans text-paper antialiased">
    @inertia
    <noscript>
        <div style="padding:2rem;font-family:system-ui;color:#f3f1ec;background:#0a0a0a">BookMyMovie needs JavaScript to book seats. Please enable it and reload the page.</div>
    </noscript>
</body>
</html>
