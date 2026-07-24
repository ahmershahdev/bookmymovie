@props(['action' => 'form_submit'])

@php
    $v2SiteKey = config('services.recaptcha.v2_site_key');
    $v3SiteKey = config('services.recaptcha.v3_site_key');
@endphp

<div class="space-y-3" data-recaptcha-block>
    @if($v3SiteKey)
        <input type="hidden" name="recaptcha_v3_token" data-recaptcha-v3-token data-recaptcha-action="{{ $action }}">
    @endif

    @if($v2SiteKey)
        <div class="overflow-hidden rounded-md border border-white/10 bg-gray-950/80 p-3">
            <div data-recaptcha-v2 data-sitekey="{{ $v2SiteKey }}"></div>
        </div>
    @endif

    <div class="flex items-center gap-2 rounded-md border border-white/10 bg-white/[.03] px-3 py-2 text-xs font-semibold text-gray-400">
        <span class="h-2 w-2 rounded-full {{ ($v2SiteKey || $v3SiteKey) ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
        @if($v2SiteKey && $v3SiteKey)
            Protected by Google reCAPTCHA v2 checkbox and v3 risk scoring.
        @elseif($v2SiteKey || $v3SiteKey)
            Protected by Google reCAPTCHA.
        @else
            Add reCAPTCHA keys in .env to enable live captcha checks.
        @endif
    </div>

    @error('captcha')
        <p class="text-sm font-semibold text-red-300">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
        @if($v2SiteKey || $v3SiteKey)
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
