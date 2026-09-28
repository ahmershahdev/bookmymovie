import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import Icon from '@/components/Icon';
import SocialLogin from '@/components/SocialLogin';
import { Checkbox, Field } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

type Provider = { key: string; label: string; url: string };

export default function Login({ captcha, providers }: { captcha: CaptchaData; providers: Provider[] }) {
    const [visible, setVisible] = useState(false);
    const form = useForm({ email: '', password: '', remember: false, ...captchaDefaults });
    const errors = form.errors as Record<string, string>;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        submitForm(form, 'post', route('user.login'), { onError: () => form.reset('password') }, captcha);
    };

    return (
        <>
            <AuthHeading label="Sign in" title="Welcome back">Sign in to hold seats, find your e-tickets and manage bookings.</AuthHeading>
            <SocialLogin providers={providers} />

            <form onSubmit={submit} noValidate className="space-y-5">
                <Field label="Email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} required maxLength={150} autoComplete="email" autoFocus />
                <div>
                    <Field label="Password" type={visible ? 'text' : 'password'} value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={errors.password} required maxLength={72} autoComplete="current-password"
                        aside={<Link href={route('password.request')} className="link text-xs text-mute hover:text-paper">Forgot password?</Link>} />
                    <button type="button" className="mt-2 inline-flex items-center gap-1.5 text-xs text-mute hover:text-paper" onClick={() => setVisible(!visible)}>
                        <Icon name={visible ? 'eye-off' : 'eye'} size={14} /> {visible ? 'Hide password' : 'Show password'}
                    </button>
                </div>
                <Checkbox checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)}>Keep me signed in on this device</Checkbox>
                <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                    onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg w-full">{form.processing ? 'Signing in…' : 'Sign in'}</button>
            </form>

            <p className="mt-8 text-center text-sm text-mute">New to BookMyMovie? <Link href={route('user.register')} className="link font-semibold text-accent">Create an account</Link></p>
        </>
    );
}

Login.layout = authLayout;
