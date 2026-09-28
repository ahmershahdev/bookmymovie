<!DOCTYPE html>
<html lang="en" class="bg-ink">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Redirecting to {{ $provider }} | BookMyMovie</title>
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-ink font-sans text-paper antialiased">
    <form id="handoff" method="POST" action="{{ $form['action'] }}" class="shell max-w-lg text-center">
        @foreach($form['fields'] as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <p class="label label-volt">Secure payment</p>
        <h1 class="display mt-4 text-7xl">Taking you to {{ $provider }}</h1>
        <p class="lede mt-5">Do not close this tab. If nothing happens in a few seconds, press the button.</p>
        <button type="submit" class="btn btn-primary btn-lg mt-8">Continue to {{ $provider }}</button>
    </form>
    <script nonce="{{ $cspNonce ?? '' }}">document.getElementById('handoff').submit();</script>
</body>
</html>
