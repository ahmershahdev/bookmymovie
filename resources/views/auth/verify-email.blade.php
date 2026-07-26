@extends('layouts.auth')

@section('title', 'Verify Email | BookMyMovie')
@section('meta_description', 'Verify your BookMyMovie account with the 8-character email code sent during signup.')
@section('eyebrow', 'Email verification')
@section('heading', 'Enter your verification code')
@section('subheading', 'Use the 8-character code sent to your signup email. After verification, login with your new account.')

@push('styles')
    <style nonce="{{ $cspNonce ?? '' }}">
        .verify-code-field.is-complete .verify-digit {
            animation: verify-orbit 700ms cubic-bezier(.2, .7, .2, 1) both;
            animation-delay: calc(var(--i) * 35ms);
        }

        .verify-code-field.is-success .verify-digit {
            border-color: rgba(52, 211, 153, .65);
            background: rgba(6, 78, 59, .82);
            color: #d1fae5;
            box-shadow: 0 0 22px rgba(16, 185, 129, .22);
        }

        .verify-code-field.is-error .verify-digit {
            border-color: rgba(248, 113, 113, .7);
            background: rgba(127, 29, 29, .7);
            color: #fee2e2;
            box-shadow: 0 0 22px rgba(239, 68, 68, .22);
        }

        @keyframes verify-orbit {
            0% {
                transform: rotate(0deg) translateX(0) scale(1);
            }

            55% {
                transform: translate(var(--merge-x), var(--merge-y)) rotate(220deg) scale(.72);
            }

            100% {
                transform: rotate(360deg) translateX(0) scale(1);
            }
        }
    </style>
@endpush

@section('content')
    @if($errors->any())
        <div class="mb-5 rounded-lg border border-red-500/40 bg-red-950/70 px-4 py-3 text-sm font-bold text-red-100">
            Verification failed: {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('user.verify') }}" class="space-y-5"
        x-data="emailCodeVerification('{{ old('code') }}', {{ $errors->any() ? 'true' : 'false' }})">
        @csrf

        <label class="block">
            <span class="text-sm font-bold text-gray-200">Email address</span>
            <input name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required
                class="auth-field mt-2 w-full rounded-md border-white/10 bg-gray-900/80 px-4 py-3 text-white placeholder:text-gray-500 focus:border-red-400 focus:ring-red-400">
        </label>
        @error('email')
            <p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>
        @enderror

        <div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-bold text-gray-200">8-character code</span>
                <span class="text-xs font-black uppercase"
                    :class="state === 'success' ? 'text-emerald-300' : (state === 'error' ? 'text-red-300' : 'text-gray-500')"
                    x-text="stateLabel()"></span>
            </div>

            <input type="text" inputmode="text" autocomplete="one-time-code" x-model="code" @input="cleanCode()"
                maxlength="8" class="sr-only" name="code" x-ref="codeInput">

            <button type="button" @click="$refs.codeInput.focus()"
                class="verify-code-field mt-3 grid w-full grid-cols-4 gap-2 sm:grid-cols-8"
                :class="{ 'is-complete': complete, 'is-success': state === 'success', 'is-error': state === 'error' }">
                <template x-for="index in 8" :key="index">
                    <span class="verify-digit flex aspect-square items-center justify-center rounded-lg border border-white/10 bg-gray-900/80 text-xl font-black text-white shadow-inner transition"
                        :style="digitStyle(index)" x-text="code[index - 1] || ''"></span>
                </template>
            </button>
            <p class="mt-2 text-xs font-semibold text-gray-500">Click the boxes and type or paste the full code.</p>
        </div>
        @error('code')
            <p class="-mt-3 text-sm font-semibold text-red-300">{{ $message }}</p>
        @enderror

        <button @click="markSuccessPreview()" :disabled="code.length !== 8"
            class="auth-glass w-full rounded-full bg-red-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-950/30 transition hover:-translate-y-0.5 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-400 disabled:cursor-not-allowed disabled:bg-gray-700">
            Verify account
        </button>
    </form>

    <p class="mt-6 text-center text-sm font-semibold text-gray-400">
        Already verified?
        <a href="{{ route('user.login') }}" class="font-black text-red-300 transition hover:text-red-200">Login</a>
    </p>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        function emailCodeVerification(initialCode = '', hasError = false) {
            return {
                code: initialCode.toUpperCase(),
                complete: initialCode.length === 8,
                state: hasError ? 'error' : 'idle',
                cleanCode() {
                    this.code = this.code.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 8);
                    this.complete = this.code.length === 8;
                    this.state = this.complete ? 'ready' : 'idle';
                },
                stateLabel() {
                    return {
                        idle: 'Waiting for code',
                        ready: 'Ready to verify',
                        success: 'Verification successful',
                        error: 'Verification failed',
                    }[this.state];
                },
                digitStyle(index) {
                    const item = index - 1;
                    const mergeX = (3.5 - item) * 44;
                    const mergeY = index > 4 ? -56 : 0;

                    return `--i: ${item}; --merge-x: ${mergeX}px; --merge-y: ${mergeY}px`;
                },
                markSuccessPreview() {
                    if (this.code.length === 8) {
                        this.state = 'success';
                    }
                },
            };
        }
    </script>
@endpush
