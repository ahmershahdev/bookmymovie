import { useEffect, useId, useRef } from 'react';
import Icon from '@/components/Icon';
import type { Captcha as CaptchaData } from '@/types';

type Grecaptcha = {
    ready: (callback: () => void) => void;
    execute: (key: string, options: { action: string }) => Promise<string>;
    render: (element: HTMLElement, options: Record<string, unknown>) => number;
};

declare global {
    interface Window {
        grecaptcha?: Grecaptcha;
    }
}

export interface CaptchaFields {
    custom_captcha_answer: string;
    website_url: string;
    'g-recaptcha-response'?: string;
    recaptcha_v3_token?: string;
}

export const captchaDefaults: CaptchaFields = { custom_captcha_answer: '', website_url: '' };

let scriptPromise: Promise<void> | null = null;

function loadRecaptcha(captcha: CaptchaData): Promise<void> {
    if (scriptPromise) return scriptPromise;
    scriptPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = `https://www.google.com/recaptcha/api.js?${captcha.v3_site_key ? 'render=' + encodeURIComponent(captcha.v3_site_key) : 'render=explicit'}`;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('reCAPTCHA failed to load'));
        document.head.appendChild(script);
    });
    return scriptPromise;
}

/** Fetches a fresh reCAPTCHA v3 token right before submitting, when v3 is on. */
export async function recaptchaToken(captcha: CaptchaData | undefined): Promise<string | undefined> {
    if (!captcha?.v3_site_key) return undefined;
    await loadRecaptcha(captcha);
    const grecaptcha = window.grecaptcha;
    if (!grecaptcha) return undefined;
    return new Promise((resolve) => grecaptcha.ready(() => grecaptcha.execute(captcha.v3_site_key as string, { action: captcha.action }).then(resolve, () => resolve(undefined))));
}

/**
 * Layered bot checks for public forms: a honeypot, a server-drawn image code
 * (its answer only exists as a salted hash in the session) and, when keys are
 * configured, Google reCAPTCHA. A new image arrives with every failed submit.
 */
export default function Captcha({ captcha, answer, honeypot, error, onAnswer, onHoneypot, onV2Token }: {
    captcha: CaptchaData;
    answer: string;
    honeypot: string;
    error?: string;
    onAnswer: (value: string) => void;
    onHoneypot: (value: string) => void;
    onV2Token?: (token: string) => void;
}) {
    const id = useId();
    const v2Ref = useRef<HTMLDivElement>(null);
    const googleEnabled = Boolean(captcha.v2_site_key || captcha.v3_site_key);

    // A new challenge image means the old answer is useless.
    useEffect(() => {
        onAnswer('');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [captcha.image]);

    useEffect(() => {
        if (!captcha.v2_site_key && !captcha.v3_site_key) return;
        loadRecaptcha(captcha).then(() => {
            if (!captcha.v2_site_key || !v2Ref.current || v2Ref.current.dataset.rendered) return;
            const attempt = (tries = 0) => {
                if (window.grecaptcha?.render && v2Ref.current) {
                    window.grecaptcha.render(v2Ref.current, { sitekey: captcha.v2_site_key, theme: 'dark', callback: (token: string) => onV2Token?.(token) });
                    v2Ref.current.dataset.rendered = 'true';
                } else if (tries < 20) {
                    window.setTimeout(() => attempt(tries + 1), 250);
                }
            };
            attempt();
        }, () => undefined);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [captcha.v2_site_key, captcha.v3_site_key]);

    return (
        <div className="space-y-3">
            {/* Honeypot: invisible to people, irresistible to bots. */}
            <div className="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                <label htmlFor={`${id}-hp`}>Leave this field empty</label>
                <input type="text" id={`${id}-hp`} name="website_url" tabIndex={-1} autoComplete="off" value={honeypot} onChange={(event) => onHoneypot(event.target.value)} />
            </div>

            <div className="field">
                <label htmlFor={id} className="field-label">Security code</label>
                <div className="grid grid-cols-[minmax(0,11rem)_1fr] items-stretch gap-2">
                    {captcha.image && (
                        <img src={captcha.image} alt={`Security code image. Type the ${captcha.length} characters shown.`} width={176} height={52}
                            className="h-[3.25rem] w-full border border-line-2 object-cover" />
                    )}
                    <input id={id} name="custom_captcha_answer" type="text" inputMode="text" autoComplete="off" autoCapitalize="characters" spellCheck={false}
                        maxLength={8} required placeholder={`${captcha.length} characters`} aria-describedby={`${id}-hint`}
                        aria-invalid={error ? true : undefined} value={answer} onChange={(event) => onAnswer(event.target.value)}
                        className="input num uppercase tracking-[.3em]" />
                </div>
                <p id={`${id}-hint`} className="field-hint">Letters and numbers, not case-sensitive. A new code appears after each attempt.</p>
            </div>

            {captcha.v2_site_key && <div ref={v2Ref} />}

            {error && (
                <p className="field-error" role="alert"><Icon name="alert" size={16} className="mt-0.5" /> {error}</p>
            )}

            <p className="flex items-center gap-2 text-xs text-mute">
                <Icon name="shield" size={14} className={googleEnabled ? 'text-mint' : 'text-accent'} />
                {googleEnabled ? (
                    <span>
                        Protected by a security code and Google reCAPTCHA. Google’s <a href="https://policies.google.com/privacy" className="link" target="_blank" rel="noopener">Privacy Policy</a> and{' '}
                        <a href="https://policies.google.com/terms" className="link" target="_blank" rel="noopener">Terms</a> apply.
                    </span>
                ) : (
                    <span>Protected by a security code, a honeypot and rate limiting.</span>
                )}
            </p>
        </div>
    );
}
