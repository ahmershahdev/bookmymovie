@extends('layouts.auth')

@section('title', 'Reset Admin Credentials | BookMyMovie')
@section('meta_description', 'Reset one BookMyMovie admin credential with a valid 15-minute recovery code.')
@section('eyebrow', 'Admin Recovery')
@section('heading', 'Change one credential')
@section('subheading', 'Choose whether to update the admin password or email. Recovery codes expire after 15 minutes.')

@section('content')
    <form method="POST" action="{{ route('admin.credentials.reset', $token) }}" class="space-y-4" x-data="{ target: 'password' }">
        @csrf
        <label class="block">
            <span class="text-sm font-bold text-gray-300">Current admin email</span>
            <input name="email" type="email" value="{{ old('email') }}" placeholder="admin@bookmymovie.ahmershah.dev"
                class="mt-2 h-12 w-full rounded-md border border-white/10 bg-gray-950 px-4 text-white outline-none placeholder:text-gray-600 focus:border-red-400 focus:ring-2 focus:ring-red-500/30">
        </label>
        @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror

        <div>
            <span class="text-sm font-bold text-gray-300">Credential to change</span>
            <div class="mt-2 grid grid-cols-2 gap-2 rounded-md border border-white/10 bg-gray-950 p-1">
                <label class="cursor-pointer rounded-md px-3 py-2 text-center text-sm font-black" :class="target === 'password' ? 'bg-red-600 text-white' : 'text-gray-300'">
                    <input type="radio" name="change_target" value="password" x-model="target" class="sr-only">
                    Password
                </label>
                <label class="cursor-pointer rounded-md px-3 py-2 text-center text-sm font-black" :class="target === 'email' ? 'bg-red-600 text-white' : 'text-gray-300'">
                    <input type="radio" name="change_target" value="email" x-model="target" class="sr-only">
                    Email
                </label>
            </div>
        </div>
        @error('change_target')<p class="text-sm text-red-300">{{ $message }}</p>@enderror

        <div x-show="target === 'password'">
            <label class="block">
                <span class="text-sm font-bold text-gray-300">New password</span>
                <input name="password" type="password" placeholder="Minimum 8 characters"
                    class="mt-2 h-12 w-full rounded-md border border-white/10 bg-gray-950 px-4 text-white outline-none placeholder:text-gray-600 focus:border-red-400 focus:ring-2 focus:ring-red-500/30">
            </label>
            <label class="mt-4 block">
                <span class="text-sm font-bold text-gray-300">Confirm password</span>
                <input name="password_confirmation" type="password" placeholder="Repeat new password"
                    class="mt-2 h-12 w-full rounded-md border border-white/10 bg-gray-950 px-4 text-white outline-none placeholder:text-gray-600 focus:border-red-400 focus:ring-2 focus:ring-red-500/30">
            </label>
        </div>
        @error('password')<p class="text-sm text-red-300">{{ $message }}</p>@enderror

        <div x-show="target === 'email'">
            <label class="block">
                <span class="text-sm font-bold text-gray-300">New admin email</span>
                <input name="new_email" type="email" placeholder="new-admin@bookmymovie.ahmershah.dev"
                    class="mt-2 h-12 w-full rounded-md border border-white/10 bg-gray-950 px-4 text-white outline-none placeholder:text-gray-600 focus:border-red-400 focus:ring-2 focus:ring-red-500/30">
            </label>
        </div>
        @error('new_email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror

        <button class="w-full rounded-md bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Update credential</button>
    </form>
@endsection
