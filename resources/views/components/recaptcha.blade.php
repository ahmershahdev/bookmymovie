@props(['action' => 'form_submit'])

@php
    $customCaptcha = \App\Support\FormSecurity::customCaptchaChallenge($action);
    $customCaptchaId = 'custom-captcha-' . \Illuminate\Support\Str::slug($action);
    $v2SiteKey = config('services.recaptcha.v2_site_key');
    $v3SiteKey = config('services.recaptcha.v3_site_key');
    $skipGoogleRecaptcha = request() ? \App\Support\FormSecurity::shouldSkipGoogleRecaptcha(request()) : false;
    $googleRecaptchaEnabled = ($v2SiteKey || $v3SiteKey) && ! $skipGoogleRecaptcha;
@endphp

<div class="space-y-3" data-recaptcha-block>
    <div class="rounded-md border border-white/10 bg-gray-950/80 p-3">
        <label for="{{ $customCaptchaId }}" class="block text-xs font-black uppercase tracking-wide text-gold">
            Security code
        </label>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(9rem,12rem)] sm:items-center">
            <div
                class="relative isolate flex min-h-14 w-full items-center justify-center overflow-hidden rounded-md border border-red-500/20 bg-black/40 px-3 py-2 shadow-inner shadow-black/40 select-none"
                aria-label="Security code {{ implode(' ', str_split($customCaptcha['code'])) }}"
            >
                <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(220,38,38,.16),transparent_34%,rgba(212,175,55,.12)_64%,transparent)]"></div>
                <div class="absolute left-0 right-0 top-1/2 h-px -translate-y-1/2 bg-red-400/35"></div>
                <div class="absolute inset-x-0 top-3 h-px bg-white/10"></div>
                <div class="absolute inset-x-0 bottom-3 h-px bg-white/10"></div>
                <div class="relative flex flex-wrap items-center justify-center gap-1.5 text-center font-black tracking-[.28em] text-white pointer-events-none">
                    @foreach($customCaptcha['characters'] as $character)
                        <span class="{{ $character['class'] }} inline-block min-w-5 drop-shadow-[0_2px_8px_rgba(220,38,38,.55)]" aria-hidden="true">
                            {{ $character['value'] }}
                        </span>
                    @endforeach
                </div>
            </div>
            <input
                id="{{ $customCaptchaId }}"
                name="custom_captcha_answer"
                type="text"
                inputmode="text"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                maxlength="8"
                required
                placeholder="Enter code"
                class="h-12 w-full rounded-md border border-white/10 bg-white/[.04] px-3 text-sm font-bold uppercase tracking-wide text-white outline-none transition focus:border-red-400 focus:ring-2 focus:ring-red-500/30"
            >
        </div>
    </div>

    @if($v3SiteKey && ! $skipGoogleRecaptcha)
        <input type="hidden" name="recaptcha_v3_token" data-recaptcha-v3-token data-recaptcha-action="{{ $action }}">
    @endif

    @if($v2SiteKey && ! $skipGoogleRecaptcha)
        <div class="overflow-hidden rounded-md border border-white/10 bg-gray-950/80 p-3">
            <div data-recaptcha-v2 data-sitekey="{{ $v2SiteKey }}"></div>
        </div>
    @endif

    <div class="flex items-center gap-2 rounded-md border border-white/10 bg-white/[.03] px-3 py-2 text-xs font-semibold text-gray-400">
        <span class="h-2 w-2 shrink-0 rounded-full {{ $googleRecaptchaEnabled ? 'bg-emerald-400' : 'bg-gold' }}"></span>
        @if($googleRecaptchaEnabled && $v2SiteKey && $v3SiteKey)
            Protected by custom captcha, Google reCAPTCHA v2 checkbox, and v3 risk scoring.
        @elseif($googleRecaptchaEnabled)
            Protected by custom captcha and Google reCAPTCHA.
        @elseif($skipGoogleRecaptcha && ($v2SiteKey || $v3SiteKey))
            Localhost uses custom captcha; Google reCAPTCHA is skipped here.
        @else
            Protected by custom captcha. Add Google reCAPTCHA keys in .env for production checks.
        @endif
    </div>

    @error('captcha')
        <p class="text-sm font-semibold text-red-300">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
        @if($googleRecaptchaEnabled)
            <script nonce="{{ $cspNonce ?? '' }}" src="https://www.google.com/recaptcha/api.js?{{ $v3SiteKey ? 'render=' . urlencode($v3SiteKey) : 'render=explicit' }}" async defer></script>
            <script nonce="{{ $cspNonce ?? '' }}">
                window.bookMyMovieRenderCaptcha = function () {
                    if (!window.grecaptcha || !window.grecaptcha.render) {
                        return false;
                    }

                    document.querySelectorAll('[data-recaptcha-v2]:not([data-recaptcha-rendered])').forEach((element) => {
                        window.grecaptcha.render(element, {
                            sitekey: element.dataset.sitekey,
                        });
                        element.dataset.recaptchaRendered = 'true';
                    });

                    return true;
                };

                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('[data-recaptcha-block]').forEach((block) => {
                        const form = block.closest('form');

                        if (form) {
                            form.dataset.recaptchaForm = 'true';
                        }
                    });

                    const v3SiteKey = @json($v3SiteKey);

                    if (v3SiteKey) {
                        document.querySelectorAll('form[data-recaptcha-form]').forEach((form) => {
                            form.addEventListener('submit', (event) => {
                                const tokenInput = form.querySelector('[data-recaptcha-v3-token]');

                                if (!tokenInput || tokenInput.value || !window.grecaptcha) {
                                    return;
                                }

                                event.preventDefault();
                                window.grecaptcha.ready(() => {
                                    window.grecaptcha.execute(v3SiteKey, {
                                        action: tokenInput.dataset.recaptchaAction || 'form_submit',
                                    }).then((token) => {
                                        tokenInput.value = token;
                                        form.requestSubmit();
                                    });
                                });
                            });
                        });
                    }

                    let captchaAttempts = 0;
                    const captchaTimer = window.setInterval(() => {
                        captchaAttempts++;

                        if (window.bookMyMovieRenderCaptcha() || captchaAttempts >= 20) {
                            window.clearInterval(captchaTimer);
                        }
                    }, 250);
                });
            </script>
        @endif
    @endpush
@endonce
