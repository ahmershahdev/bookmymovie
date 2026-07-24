@extends('layouts.auth')

@section('title', 'Register | BookMyMovie')
@section('eyebrow', 'Create account')
@section('heading', 'Sign up for BookMyMovie')
@section('subheading', 'Save your profile once and move faster through ticket booking, wishlists, and checkout.')

@section('content')
    <div class="grid gap-3 sm:grid-cols-2">
        <a href="{{ route('oauth.redirect', 'google') }}"
            class="auth-glass inline-flex min-h-12 items-center justify-center gap-3 rounded-md border border-white/10 bg-white px-4 py-3 text-sm font-black text-gray-900 transition hover:-translate-y-0.5 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                <path fill="#34A853"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.24 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                <path fill="#FBBC05"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                <path fill="#EA4335"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z" />
            </svg>
            Google
        </a>
        <a href="{{ route('oauth.redirect', 'facebook') }}"
            class="auth-glass inline-flex min-h-12 items-center justify-center gap-3 rounded-md border border-blue-500/40 bg-[#1877F2] px-4 py-3 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-[#166FE5] focus:outline-none focus:ring-2 focus:ring-blue-300">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path
                    d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.438H7.078v-3.49h3.047V9.414c0-3.025 1.792-4.697 4.533-4.697 1.313 0 2.686.236 2.686.236v2.971h-1.513c-1.49 0-1.956.93-1.956 1.884v2.265h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" />
            </svg>
            Facebook
        </a>
    </div>

    <div class="my-6 flex items-center gap-3">
        <span class="h-px flex-1 bg-white/10"></span>
        <span class="text-xs font-bold uppercase text-gray-500">or register with email</span>
        <span class="h-px flex-1 bg-white/10"></span>
    </div>

    <form method="POST" action="{{ route('user.register') }}" class="space-y-5" x-data="passwordStrengthForm()">
        @csrf

        <label class="block">
            <span class="text-sm font-bold text-gray-200">Full name</span>
            <input name="name" value="{{ old('name') }}" autocomplete="name" required minlength="3" maxlength="100"
                placeholder="Syed Ahmer Shah"
                class="auth-field mt-2 w-full rounded-md border-white/10 bg-gray-900/80 px-4 py-3 text-white placeholder:text-gray-500 focus:border-red-400 focus:ring-red-400">
        </label>
        @error('name')
            <p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>
        @enderror

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
            <span class="text-sm font-bold text-gray-200">Phone number</span>
            <input name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" minlength="10"
                maxlength="20" pattern="[0-9+\-\s()]{10,20}" placeholder="+92 300 1234567"
                class="auth-field mt-2 w-full rounded-md border-white/10 bg-gray-900/80 px-4 py-3 text-white placeholder:text-gray-500 focus:border-red-400 focus:ring-red-400">
        </label>
        @error('phone')
            <p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>
        @enderror

        <label class="block">
            <span class="text-sm font-bold text-gray-200">Password</span>
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
            <span class="text-sm font-bold text-gray-200">Confirm password</span>
            <span
                class="auth-field mt-2 flex rounded-md border border-white/10 bg-gray-900/80 focus-within:border-red-400 focus-within:ring-1 focus-within:ring-red-400">
                <input name="password_confirmation" :type="showConfirmation ? 'text' : 'password'"
                    autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Repeat your password"
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

        <x-recaptcha action="user_register" />

        <button
            class="auth-glass w-full rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-950/30 transition hover:-translate-y-0.5 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm font-semibold text-gray-400">
        Already have an account?
        <a href="{{ route('user.login') }}" class="font-black text-red-300 transition hover:text-red-200">
            Login
        </a>
    </p>
@endsection
