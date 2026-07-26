@extends('layouts.auth')

@section('title', 'Admin Recovery | BookMyMovie')
@section('meta_description', 'Recover BookMyMovie admin access with the configured secret key and admin email.')
@section('eyebrow', 'Admin Recovery')
@section('heading', 'Recover admin access')
@section('subheading', 'Enter the admin recovery secret and admin email to receive a 15-minute reset code.')

@section('content')
    <form method="POST" action="{{ route('admin.credentials.request') }}" class="space-y-4">
        @csrf
        <label class="block">
            <span class="text-sm font-bold text-gray-300">Recovery secret</span>
            <input name="secret_key" type="password" placeholder="Enter configured secret key"
                class="mt-2 h-12 w-full rounded-md border border-white/10 bg-gray-950 px-4 text-white outline-none placeholder:text-gray-600 focus:border-red-400 focus:ring-2 focus:ring-red-500/30">
        </label>
        @error('secret_key')<p class="text-sm text-red-300">{{ $message }}</p>@enderror

        <label class="block">
            <span class="text-sm font-bold text-gray-300">Admin email</span>
            <input name="email" type="email" value="{{ old('email') }}" placeholder="admin@bookmymovie.ahmershah.dev"
                class="mt-2 h-12 w-full rounded-md border border-white/10 bg-gray-950 px-4 text-white outline-none placeholder:text-gray-600 focus:border-red-400 focus:ring-2 focus:ring-red-500/30">
        </label>
        @error('email')<p class="text-sm text-red-300">{{ $message }}</p>@enderror

        <button class="w-full rounded-md bg-red-600 px-5 py-3 text-sm font-black text-white hover:bg-red-500">Send recovery code</button>
        <a href="{{ route('admin.login') }}" class="block text-center text-sm font-bold text-gray-300 hover:text-white">Back to login</a>
    </form>
@endsection
