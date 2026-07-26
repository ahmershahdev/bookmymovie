@extends('layouts.auth')

@section('title', 'Admin Login | BookMyMovie')
@section('meta_description', 'Secure BookMyMovie admin login for managing movies, pricing, bookings, content, and website settings.')

@section('content')
    <div class="rounded-lg border border-white/10 bg-white/[.04] p-5 shadow-2xl shadow-black/30">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-black uppercase tracking-[.22em] text-red-400">Admin</p>
                <h1 class="mt-2 text-2xl font-black text-white">Secure dashboard login</h1>
            </div>
            <div class="rounded-md border border-gold/20 bg-gold/10 px-3 py-2 text-xs font-black text-gold">60 min</div>
        </div>

        <form method="POST" action="{{ route('admin.login') }}" class="mt-6 space-y-4" x-data="{ show: false }">
        @csrf
            <label class="block">
                <span class="text-sm font-bold text-gray-300">Admin email</span>
                <input name="email" type="email" value="{{ old('email') }}" placeholder="admin@bookmymovie.ahmershah.dev"
                    autocomplete="email"
                    class="mt-2 h-12 w-full rounded-md border border-white/10 bg-gray-950 px-4 text-white outline-none transition placeholder:text-gray-600 focus:border-red-400 focus:ring-2 focus:ring-red-500/30">
            </label>
            @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror

            <label class="block">
                <span class="text-sm font-bold text-gray-300">Password</span>
                <div class="mt-2 flex h-12 rounded-md border border-white/10 bg-gray-950 transition focus-within:border-red-400 focus-within:ring-2 focus-within:ring-red-500/30">
                    <input name="password" :type="show ? 'text' : 'password'" placeholder="Enter admin password"
                        autocomplete="current-password"
                        class="w-full border-0 bg-transparent px-4 text-white outline-none placeholder:text-gray-600 focus:ring-0">
                    <button type="button" @click="show = !show" class="px-4 text-sm font-black text-red-300 hover:text-red-200"
                        x-text="show ? 'Hide' : 'Show'"></button>
                </div>
            </label>
            @error('password')<p class="text-sm text-red-300">{{ $message }}</p>@enderror

            <div class="flex items-center justify-between gap-3 text-sm">
                <label class="flex items-center gap-2 font-semibold text-gray-300">
                    <input name="remember" type="checkbox" value="1" class="rounded border-white/10 bg-gray-950 text-red-600 focus:ring-red-500">
                    Remember device
                </label>
                <a href="{{ route('admin.credentials.request') }}" class="font-bold text-red-300 hover:text-red-200">Forgot credentials?</a>
            </div>

            <button class="w-full rounded-md bg-red-600 px-5 py-3 text-sm font-black text-white transition hover:bg-red-500">Login to dashboard</button>
        </form>
    </div>
@endsection
