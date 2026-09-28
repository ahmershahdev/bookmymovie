import { Link, useForm } from '@inertiajs/react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import Icon from '@/components/Icon';
import { Field } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

export default function ForgotPassword({ captcha }: { captcha: CaptchaData }) {
    const form = useForm({ email: '', ...captchaDefaults });
    const errors = form.errors as Record<string, string>;

    return (
        <>
            <AuthHeading step="01 / 02" label="Account recovery" title="Forgot it? It happens.">Enter the email on your account. If it matches, we will email an 8-character code that works once, for 15 minutes.</AuthHeading>
            <form onSubmit={(event) => { event.preventDefault(); submitForm(form, 'post', route('password.request'), {}, captcha); }} noValidate className="space-y-5">
                <Field label="Email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} required maxLength={150} autoComplete="email" autoFocus />
                <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                    onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg w-full">Email me a reset code</button>
            </form>
            <div className="panel mt-8 flex gap-3 p-5 text-sm text-mute">
                <Icon name="shield" size={18} className="shrink-0 text-accent" /> For your security we show the same message whether or not an account exists, and the code is only ever sent by email.
            </div>
            <p className="mt-8 text-center text-sm text-mute">Remembered it? <Link href={route('user.login')} className="link font-semibold text-accent">Back to sign in</Link></p>
        </>
    );
}

ForgotPassword.layout = authLayout;
