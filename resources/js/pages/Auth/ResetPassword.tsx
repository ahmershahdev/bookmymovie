import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import CodeInput from '@/components/CodeInput';
import { Field, PasswordMeter } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

export default function ResetPassword({ email, captcha }: { email: string; captcha: CaptchaData }) {
    const [visible, setVisible] = useState(false);
    const form = useForm({ email, code: '', password: '', password_confirmation: '', ...captchaDefaults });
    const errors = form.errors as Record<string, string>;

    return (
        <>
            <AuthHeading step="02 / 02" label="Account recovery" title="Choose a new password">
                Enter the 8-character code from your email, then pick a new password. Every other device will be signed out.
            </AuthHeading>
            <form onSubmit={(event) => { event.preventDefault(); submitForm(form, 'post', route('password.reset'), { onError: () => form.reset('password', 'password_confirmation') }, captcha); }} noValidate className="space-y-5">
                <Field label="Email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} required maxLength={150} autoComplete="email" />
                <CodeInput value={form.data.code} onChange={(value) => form.setData('code', value)} error={errors.code} label="Reset code" />
                <Field label="New password" type={visible ? 'text' : 'password'} value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={errors.password} required maxLength={72} autoComplete="new-password" />
                <PasswordMeter password={form.data.password} />
                <Field label="Confirm new password" type={visible ? 'text' : 'password'} value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} required maxLength={72} autoComplete="new-password" />
                <button type="button" className="text-xs text-mute hover:text-paper" onClick={() => setVisible(!visible)}>{visible ? 'Hide passwords' : 'Show passwords'}</button>
                <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                    onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                <button type="submit" disabled={form.processing || form.data.code.length !== 8} className="btn btn-primary btn-lg w-full">Save new password</button>
            </form>
            <p className="mt-8 text-center text-sm text-mute">No code? <Link href={route('password.request')} className="link font-semibold text-accent">Send a new one</Link></p>
        </>
    );
}

ResetPassword.layout = authLayout;
