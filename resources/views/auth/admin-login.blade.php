@extends('layouts.auth')

@section('title', 'Admin Login | BookMyMovie')

@section('content')
    <p class="text-sm font-black uppercase tracking-[.22em] text-red-400">Admin</p>
    <h1 class="mt-2 text-2xl font-black text-white">Admin login</h1>
    <form method="POST" action="{{ route('admin.login') }}" class="mt-6 space-y-4" x-data="{ show: false }">
        @csrf
        <label class="block"><span class="text-sm font-bold text-gray-300">Email</span><input name="email" type="email"
                class="mt-2 w-full rounded-md border-white/10 bg-gray-950 text-white focus:border-red-400 focus:ring-red-400"></label>
        @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
        <label class="block"><span class="text-sm font-bold text-gray-300">Password</span>
            <div class="mt-2 flex rounded-md border border-white/10 bg-gray-950"><input name="password"
                    :type="show ? 'text' : 'password'"
                    class="w-full border-0 bg-transparent text-white focus:ring-0"><button type="button"
                    @click="show = !show" class="px-3 text-sm font-bold text-red-300"
                    x-text="show ? 'Hide' : 'Show'"></button></div>
        </label>
        @error('password')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
        <button class="w-full rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Login to
            dashboard</button>
    </form>
@endsection