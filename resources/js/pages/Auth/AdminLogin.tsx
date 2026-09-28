import { Link, useForm } from '@inertiajs/react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import { Field } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

export default function AdminLogin({ captcha, sessionMinutes, recoveryEnabled }: { captcha: CaptchaData; sessionMinutes: number; recoveryEnabled: boolean }) {
    const form = useForm({ email: '', password: '', ...captchaDefaults });
    const errors = form.errors as Record<string, string>;

    return (
        <>
            <AuthHeading label="Staff only" title="Box office">Admin sessions end after {sessionMinutes} minutes and every sign-in is logged.</AuthHeading>
            <form onSubmit={(event) => { event.preventDefault(); submitForm(form, 'post', route('admin.login'), { onError: () => form.reset('password') }, captcha); }} noValidate className="space-y-5">
                <Field label="Admin email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} required maxLength={150} autoComplete="username" autoFocus />
                <Field label="Password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={errors.password} required maxLength={72} autoComplete="current-password" />
                <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                    onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg w-full">Sign in to admin</button>
            </form>
            {recoveryEnabled && <p className="mt-8 text-center text-sm text-mute"><Link href={route('admin.credentials.request')} className="link">Recover admin access</Link></p>}
        </>
    );
}

AdminLogin.layout = authLayout;
