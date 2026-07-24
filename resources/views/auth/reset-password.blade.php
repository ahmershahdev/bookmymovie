@extends('layouts.auth')

@section('title', 'Reset Password | BookMyMovie')
@section('eyebrow', 'Secure update')
@section('heading', 'Create a new password')
@section('subheading', 'Use a unique password with a mix of letters, numbers, and symbols.')

@section('content')
    <form method="POST" action="{{ route('password.reset', $token) }}" class="space-y-5" x-data="passwordStrengthForm()">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label class="block">
            <span class="text-sm font-bold text-gray-200">Email address</span>
            <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required minlength="6"
                maxlength="150" placeholder="name@example.com"
                class="auth-field mt-2 w-full rounded-md border-white/10 bg-gray-900/80 px-4 py-3 text-white placeholder:text-gray-500 focus:border-red-400 focus:ring-red-400">
        </label>
        @error('email')
            <p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>
        @enderror

        <label class="block">
            <span class="text-sm font-bold text-gray-200">New password</span>
            <span
                class="auth-field mt-2 flex rounded-md border border-white/10 bg-gray-900/80 focus-within:border-red-400 focus-within:ring-1 focus-within:ring-red-400">
                <input name="password" x-model="password" :type="showPassword ? 'text' : 'password'"
                    autocomplete="new-password" required minlength="8" maxlength="72"
                    placeholder="8+ characters with a number and symbol"
                    class="w-full border-0 bg-transparent px-4 py-3 text-white placeholder:text-gray-500 focus:ring-0">
                <button type="button" @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    class="flex w-12 items-center justify-center text-gray-400 transition hover:text-white focus:outline-none">
                    <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12z" />
                        <circle cx="12" cy="12" r="2.75" />
                    </svg>
                    <svg x-cloak x-show="showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 3l18 18" />
                        <path d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58" />
                        <path d="M9.88 5.42A9.39 9.39 0 0112 5.25c6.25 0 9.75 6.75 9.75 6.75a17.1 17.1 0 01-3.22 4.09" />
                        <path d="M6.61 6.61C3.85 8.42 2.25 12 2.25 12s3.5 6.75 9.75 6.75a9.8 9.8 0 004.05-.87" />
                    </svg>
                </button>
            </span>
        </label>
        @error('password')
            <p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>
        @enderror

        <div class="rounded-lg border border-white/10 bg-white/[.03] p-4">
            <div class="flex items-center justify-between gap-3">
                <span class="text-xs font-black uppercase text-gray-400">Password strength</span>
                <span class="text-xs font-black text-white" x-text="label()"></span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-800">
                <div class="h-full rounded-full transition-all duration-300" :class="color()" :style="`width: ${width()}`">
                </div>
            </div>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <template x-for="check in checks" :key="check.label">
                    <div class="flex items-center gap-2 text-xs font-semibold"
                        :class="check.test(password) ? 'text-emerald-300' : 'text-gray-500'">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full border text-[10px]"
                            :class="check.test(password) ? 'border-emerald-400 bg-emerald-400 text-gray-950' : 'border-gray-700'">
                            <span x-text="check.test(password) ? 'OK' : ''"></span>
                        </span>
                        <span x-text="check.label"></span>
                    </div>
                </template>
            </div>
        </div>

        <label class="block">
            <span class="text-sm font-bold text-gray-200">Confirm new password</span>
            <span
                class="auth-field mt-2 flex rounded-md border border-white/10 bg-gray-900/80 focus-within:border-red-400 focus-within:ring-1 focus-within:ring-red-400">
                <input name="password_confirmation" :type="showConfirmation ? 'text' : 'password'"
                    autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Repeat your new password"
                    class="w-full border-0 bg-transparent px-4 py-3 text-white placeholder:text-gray-500 focus:ring-0">
                <button type="button" @click="showConfirmation = !showConfirmation"
                    :aria-label="showConfirmation ? 'Hide password confirmation' : 'Show password confirmation'"
                    class="flex w-12 items-center justify-center text-gray-400 transition hover:text-white focus:outline-none">
                    <svg x-show="!showConfirmation" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12s-3.5 6.75-9.75 6.75S2.25 12 2.25 12z" />
                        <circle cx="12" cy="12" r="2.75" />
                    </svg>
                    <svg x-cloak x-show="showConfirmation" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 3l18 18" />
                        <path d="M10.58 10.58A2 2 0 0012 14a2 2 0 001.42-.58" />
                        <path d="M9.88 5.42A9.39 9.39 0 0112 5.25c6.25 0 9.75 6.75 9.75 6.75a17.1 17.1 0 01-3.22 4.09" />
                        <path d="M6.61 6.61C3.85 8.42 2.25 12 2.25 12s3.5 6.75 9.75 6.75a9.8 9.8 0 004.05-.87" />
                    </svg>
                </button>
            </span>
        </label>
        @error('password_confirmation')
            <p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>
        @enderror

        <x-recaptcha action="reset_password" />

        <button
            class="auth-glass w-full rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-950/30 transition hover:-translate-y-0.5 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
            Update password
        </button>
    </form>

    <p class="mt-6 text-center text-sm font-semibold text-gray-400">
        Remembered your password?
        <a href="{{ route('user.login') }}" class="font-black text-red-300 transition hover:text-red-200">
            Login
        </a>
    </p>
@endsection
