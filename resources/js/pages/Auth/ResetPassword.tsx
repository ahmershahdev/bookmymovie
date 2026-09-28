import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import CodeInput from '@/components/CodeInput';
import { EmailField, PasswordField, spotlight, SubmitButton } from '@/components/auth';
import { PasswordMeter } from '@/components/ui';
import { AuthHeading, authLayout, Stagger } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

export default function ResetPassword({ email, captcha }: { email: string; captcha: CaptchaData }) {
    const [visible, setVisible] = useState(false);
    const form = useForm({ email, code: '', password: '', password_confirmation: '', ...captchaDefaults });
    const errors = form.errors as Record<string, string>;
    const mismatch = form.data.password_confirmation.length > 0 && form.data.password !== form.data.password_confirmation;

    return (
        <>
            <AuthHeading step="02 / 02" label="Account recovery" title="Choose a new password">
                Enter the 8-character code from your email, then pick a new password. Every other device will be signed out.
            </AuthHeading>
            <div className="auth-card" onPointerMove={spotlight}>
                <form onSubmit={(event) => { event.preventDefault(); submitForm(form, 'post', route('password.reset'), { onError: () => form.reset('password', 'password_confirmation') }, captcha); }} noValidate>
                    <Stagger className="space-y-6">
                        <EmailField value={form.data.email} onChange={(value) => form.setData('email', value)} error={errors.email} />
                        <CodeInput value={form.data.code} onChange={(value) => form.setData('code', value)} error={errors.code} label="Reset code" />
                        <div className="space-y-3">
                            <PasswordField label="New password" visible={visible} onVisible={setVisible} value={form.data.password} onChange={(event) => form.setData('password', event.target.value)}
                                error={errors.password} required maxLength={72} autoComplete="new-password" placeholder="At least 8 characters" />
                            <PasswordMeter password={form.data.password} />
                        </div>
                        <PasswordField label="Confirm new password" visible={visible} onVisible={setVisible} value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            error={errors.password_confirmation ?? (mismatch ? 'The passwords do not match yet.' : undefined)} required maxLength={72} autoComplete="new-password" placeholder="Type it once more" />
                        <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                            onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                        <SubmitButton processing={form.processing} hasErrors={form.hasErrors} busyLabel="Saving" icon="check">Save new password</SubmitButton>
                    </Stagger>
                </form>
            </div>
            <p className="mt-8 text-center text-sm text-mute">No code? <Link href={route('password.request')} className="link font-semibold text-accent">Send a new one</Link></p>
        </>
    );
}

ResetPassword.layout = authLayout;
