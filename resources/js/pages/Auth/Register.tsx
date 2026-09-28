import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import SocialLogin from '@/components/SocialLogin';
import { Checkbox, Field, PasswordMeter } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

export default function Register({ captcha, providers }: { captcha: CaptchaData; providers: { key: string; label: string; url: string }[] }) {
    const [visible, setVisible] = useState(false);
    const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '', terms: false, ...captchaDefaults });
    const errors = form.errors as Record<string, string>;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        submitForm(form, 'post', route('user.register'), { onError: () => form.reset('password', 'password_confirmation') }, captcha);
    };

    return (
        <>
            <AuthHeading step="01 / 02" label="Create account" title="Join the front row">One account for every partner cinema. Free, and no card needed.</AuthHeading>
            <SocialLogin providers={providers} />

            <form onSubmit={submit} noValidate className="space-y-5">
                <Field label="Full name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={errors.name} required minLength={3} maxLength={100} autoComplete="name" autoFocus />
                <Field label="Email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} required maxLength={150} autoComplete="email" hint="We will send an 8-character code to verify it." />
                <Field label="Mobile number" type="tel" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} error={errors.phone} minLength={10} maxLength={20} autoComplete="tel" hint="Optional. Only used if the cinema needs to reach you." />
                <Field label="Password" type={visible ? 'text' : 'password'} value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={errors.password} required maxLength={72} autoComplete="new-password" />
                <PasswordMeter password={form.data.password} />
                <Field label="Confirm password" type={visible ? 'text' : 'password'} value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} error={errors.password_confirmation} required maxLength={72} autoComplete="new-password" />
                <button type="button" className="text-xs text-mute hover:text-paper" onClick={() => setVisible(!visible)}>{visible ? 'Hide passwords' : 'Show passwords'}</button>
                <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                    onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                <Checkbox checked={form.data.terms} onChange={(event) => form.setData('terms', event.target.checked)} error={errors.terms}>
                    I agree to the <Link href={route('terms')} className="link text-paper">terms of service</Link> and <Link href={route('privacy')} className="link text-paper">privacy policy</Link>.
                </Checkbox>
                <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg w-full">{form.processing ? 'Creating account…' : 'Create account'}</button>
            </form>

            <p className="mt-8 text-center text-sm text-mute">Already have an account? <Link href={route('user.login')} className="link font-semibold text-accent">Sign in</Link></p>
        </>
    );
}

Register.layout = authLayout;
