<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-ink">
<head>
    @include('partials.head')
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
