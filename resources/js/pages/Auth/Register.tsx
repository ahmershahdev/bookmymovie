import { Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useRef, useState } from 'react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import { AuthInput, Divider, EMAIL_PATTERN, EmailField, PasswordField, spotlight, SubmitButton, TrustRow } from '@/components/auth';
import Icon from '@/components/Icon';
import SocialLogin from '@/components/SocialLogin';
import { Checkbox, PasswordMeter } from '@/components/ui';
import { AuthHeading, authLayout, Stagger } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { cn, route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

type Check = { state: 'idle' | 'checking' | 'free' | 'taken' | 'invalid'; suggestion?: string | null; reason?: string | null };

/** Mirrors App\Support\CleanText: letters first, single underscores, 3–20 characters. */
const USERNAME = /^[a-z](?:[a-z0-9]|_(?!_))*[a-z0-9]$/;
const usernameProblem = (name: string): string | null => {
    if (name.length < 3 || name.length > 20) return 'Usernames are 3 to 20 characters.';
    if (!/^[a-z]/.test(name)) return 'Start with a letter.';
    if (!USERNAME.test(name)) return 'Lowercase letters, numbers and single underscores only, not at the end.';
    if ((name.match(/[a-z]/g) ?? []).length < 3) return 'Use at least three letters.';
    return null;
};

/** Letters in any alphabet, plus single spaces, apostrophes, hyphens and dots between them. */
const NAME = /^\p{L}[\p{L}\p{M}]*(?:[ .'-]\p{L}[\p{L}\p{M}]*)*\.?$/u;
const cleanName = (value: string) => value.replace(/[^\p{L}\p{M} .'-]/gu, '').replace(/\s{2,}/g, ' ').replace(/^[\s.'-]+/, '').slice(0, 60);

const toHandle = (name: string) => name.normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^[^a-z]+/, '').slice(0, 15).replace(/_+$/, '');

export default function Register({ captcha, providers }: { captcha: CaptchaData; providers: { key: string; label: string; url: string }[] }) {
    const [visible, setVisible] = useState(false);
    const [check, setCheck] = useState<Check>({ state: 'idle' });
    const touched = useRef(false);
    const form = useForm({ name: '', username: '', email: '', phone: '', password: '', password_confirmation: '', terms: false, ...captchaDefaults });
    const errors = form.errors as Record<string, string>;
    const mismatch = form.data.password_confirmation.length > 0 && form.data.password !== form.data.password_confirmation;
    const passwordOk = form.data.password.length >= 8 && /[a-z]/.test(form.data.password) && /[A-Z]/.test(form.data.password) && /\d/.test(form.data.password);

    // Suggest a username from the name until the person edits it themselves.
    useEffect(() => {
        if (!touched.current) form.setData('username', toHandle(form.data.name));
    }, [form.data.name]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        const name = form.data.username;
        if (name.length === 0) { setCheck({ state: 'idle' }); return; }
        const problem = usernameProblem(name);
        if (problem) { setCheck({ state: 'invalid', reason: problem }); return; }
        setCheck({ state: 'checking' });
        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            axios.get(route('username.check'), { params: { u: name }, signal: controller.signal })
                .then(({ data }) => setCheck({ state: !data.valid ? 'invalid' : data.available ? 'free' : 'taken', suggestion: data.suggestion, reason: data.reason }))
                .catch(() => undefined);
        }, 350);
        return () => { window.clearTimeout(timer); controller.abort(); };
    }, [form.data.username]);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        submitForm(form, 'post', route('user.register'), { onError: () => form.reset('password', 'password_confirmation') }, captcha);
    };

    const usernameHint = {
        idle: 'Shown on your reviews. Starts with a letter; a–z, 0–9 and _.',
        checking: 'Checking availability…',
        free: <span className="text-mint">@{form.data.username} is yours if you want it.</span>,
        taken: <span>Taken. {check.suggestion && <button type="button" className="link text-accent" onClick={() => { touched.current = true; form.setData('username', check.suggestion ?? ''); }}>Use @{check.suggestion}</button>}</span>,
        invalid: <span className="text-signal">{check.reason ?? 'That username cannot be used.'}</span>,
    }[check.state];

    // Rough progress through the form, drawn as the ticket stub fills.
    const done = [NAME.test(form.data.name.trim()), check.state === 'free', EMAIL_PATTERN.test(form.data.email), passwordOk, form.data.password_confirmation === form.data.password && passwordOk, form.data.terms].filter(Boolean).length;

    return (
        <>
            <AuthHeading step="01 / 02" label="Create account" title="Join the front row">One free account for every partner cinema. No card needed to book.</AuthHeading>

            <div className="auth-card" onPointerMove={spotlight}>
                <div className="mb-7 flex items-center gap-3" aria-hidden="true">
                    <div className="grid flex-1 grid-cols-6 gap-1">
                        {Array.from({ length: 6 }, (_, index) => (
                            <span key={index} className="h-1 overflow-hidden bg-line">
                                <motion.span className="block h-full bg-volt" initial={false} animate={{ width: index < done ? '100%' : '0%' }} transition={{ duration: 0.45, ease: [0.16, 1, 0.3, 1] }} />
                            </span>
                        ))}
                    </div>
                    <span className="num text-[11px] text-mute">{done}/6</span>
                </div>

                {providers.length > 0 && (<><SocialLogin providers={providers} /><Divider>or with email</Divider></>)}

                <form onSubmit={submit} noValidate>
                    <Stagger className="space-y-6">
                        <AuthInput label="Full name" icon="user" value={form.data.name} onChange={(event) => form.setData('name', cleanName(event.target.value))}
                            error={errors.name} valid={NAME.test(form.data.name.trim()) && form.data.name.trim().length >= 2} required minLength={2} maxLength={60}
                            autoComplete="name" autoFocus placeholder="e.g. Ayesha Raza" hint="Letters only, as it appears on your ID." />

                        <div className="field">
                            <div className="flex items-baseline justify-between gap-3">
                                <label htmlFor="username" className="field-label">Username<span className="text-accent" aria-hidden="true"> *</span></label>
                                <span className={cn('label flex items-center gap-1.5', check.state === 'free' ? 'text-mint' : check.state === 'taken' || check.state === 'invalid' ? 'text-signal' : 'text-mute')} aria-live="polite">
                                    {check.state === 'checking' && <motion.span className="h-1.5 w-1.5 bg-current" animate={{ opacity: [0.2, 1, 0.2] }} transition={{ duration: 0.8, repeat: Infinity }} />}
                                    {check.state === 'free' ? 'Available' : check.state === 'taken' ? 'Taken' : check.state === 'invalid' ? 'Not allowed' : ''}
                                </span>
                            </div>
                            <div className="input-wrap relative">
                                <span className="input-icon num flex h-full items-center text-[15px]">@</span>
                                <input id="username" value={form.data.username} placeholder="ayesha_raza"
                                    onChange={(event) => { touched.current = true; form.setData('username', event.target.value.toLowerCase().replace(/[^a-z0-9_]/g, '').replace(/__+/g, '_').slice(0, 20)); }}
                                    required minLength={3} maxLength={20} autoComplete="username" autoCapitalize="none" spellCheck={false}
                                    aria-invalid={errors.username || check.state === 'invalid' || check.state === 'taken' ? true : undefined} aria-describedby="username-hint"
                                    className="input has-icon num pe-11" />
                                <AnimatePresence>
                                    {check.state === 'free' && (
                                        <motion.span initial={{ scale: 0 }} animate={{ scale: 1 }} exit={{ scale: 0 }} className="absolute inset-y-0 end-3.5 my-auto grid h-5 w-5 place-items-center bg-mint text-noir" aria-hidden="true">
                                            <Icon name="check" size={12} stroke={2.5} />
                                        </motion.span>
                                    )}
                                </AnimatePresence>
                            </div>
                            {errors.username ? <p className="field-error" role="alert"><Icon name="alert" size={15} className="mt-0.5" /> {errors.username}</p> : <p id="username-hint" className="field-hint">{usernameHint}</p>}
                        </div>

                        <EmailField value={form.data.email} onChange={(value) => form.setData('email', value)} error={errors.email} hint="We send an 8-character code to confirm it is you." />

                        <AuthInput label="Mobile number" icon="phone" type="tel" inputMode="tel" value={form.data.phone}
                            onChange={(event) => form.setData('phone', event.target.value.replace(/[^0-9+\-\s()]/g, '').slice(0, 20))}
                            error={errors.phone} valid={form.data.phone.replace(/\D/g, '').length >= 10} minLength={10} maxLength={20} autoComplete="tel"
                            placeholder="0300 1234567" hint="Optional. Only used if your show changes." />

                        <div className="space-y-3">
                            <PasswordField label="Password" visible={visible} onVisible={setVisible} value={form.data.password} onChange={(event) => form.setData('password', event.target.value)}
                                error={errors.password} required maxLength={72} autoComplete="new-password" placeholder="At least 8 characters" />
                            <PasswordMeter password={form.data.password} />
                        </div>
                        <PasswordField label="Confirm password" visible={visible} onVisible={setVisible} value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            error={errors.password_confirmation ?? (mismatch ? 'The passwords do not match yet.' : undefined)} required maxLength={72} autoComplete="new-password" placeholder="Type it once more" />
                        <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                            onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                        <Checkbox checked={form.data.terms} onChange={(event) => form.setData('terms', event.target.checked)} error={errors.terms}>
                            I agree to the <Link href={route('terms')} className="link text-paper">terms</Link> and <Link href={route('privacy')} className="link text-paper">privacy policy</Link>.
                        </Checkbox>
                        <div>
                            <SubmitButton processing={form.processing} hasErrors={form.hasErrors} busyLabel="Creating your account">Create account</SubmitButton>
                            <TrustRow />
                        </div>
                    </Stagger>
                </form>
            </div>

            <div className="mt-8 flex items-center justify-between gap-4 text-sm">
                <span className="text-mute">Already a member?</span>
                <Link href={route('user.login')} className="group inline-flex items-center gap-2 font-semibold text-accent">Sign in <Icon name="arrow-right" size={16} className="transition-transform duration-300 group-hover:translate-x-1" /></Link>
            </div>
        </>
    );
}

Register.layout = authLayout;
