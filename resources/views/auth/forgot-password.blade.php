@extends('layouts.auth')

@section('title', 'Forgot Password | BookMyMovie')
@section('eyebrow', 'Password help')
@section('heading', 'Reset your password')
@section('subheading', 'Enter the email connected to your account and we will generate a reset token for you.')

@section('content')
    <form method="POST" action="{{ route('password.request') }}" class="space-y-5">
        @csrf

        <label class="block">
            <span class="text-sm font-bold text-gray-200">Email address</span>
            <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required minlength="6"
                maxlength="150" placeholder="name@example.com"
                class="auth-field mt-2 w-full rounded-md border-white/10 bg-gray-900/80 px-4 py-3 text-white placeholder:text-gray-500 focus:border-red-400 focus:ring-red-400">
        </label>
        @error('email')
            <p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>
        @enderror

        <x-recaptcha action="forgot_password" />

        <button
            class="auth-glass w-full rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-950/30 transition hover:-translate-y-0.5 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
            Send reset token
        </button>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm font-semibold">
        <a href="{{ route('user.login') }}" class="font-black text-red-300 transition hover:text-red-200">
            Back to login
        </a>
        <span class="text-gray-700">|</span>
        <a href="{{ route('user.register') }}" class="font-black text-red-300 transition hover:text-red-200">
            Create account
        </a>
    </div>
@endsection
