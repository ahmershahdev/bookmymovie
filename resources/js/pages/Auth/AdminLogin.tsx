import { Link, useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import { EmailField, PasswordField, spotlight, SubmitButton, TrustRow } from '@/components/auth';
import Icon from '@/components/Icon';
import { AuthHeading, authLayout, Stagger } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

type Demo = { email: string; password: string; readOnly: boolean } | null;

export default function AdminLogin({ captcha, sessionMinutes, recoveryEnabled, demo }: { captcha: CaptchaData; sessionMinutes: number; recoveryEnabled: boolean; demo: Demo }) {
    const form = useForm({ email: '', password: '', ...captchaDefaults });
    const errors = form.errors as Record<string, string>;

    return (
        <>
            <AuthHeading label="Staff only" title="Box office">Sessions end after {sessionMinutes} minutes of use, and every sign-in is written to the audit log.</AuthHeading>

            {demo && (
                <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="mb-8 border border-volt/40 bg-volt/[.06] p-5">
                    <p className="label label-accent flex items-center gap-2"><Icon name="sparkle" size={13} /> Try the demo admin</p>
                    <dl className="num mt-3 grid gap-1 text-sm">
                        <div className="flex gap-2"><dt className="w-20 text-mute">Email</dt><dd className="select-all">{demo.email}</dd></div>
                        <div className="flex gap-2"><dt className="w-20 text-mute">Password</dt><dd className="select-all">{demo.password}</dd></div>
                    </dl>
                    <p className="mt-3 text-xs text-mute">{demo.readOnly ? 'Read-only on this site: you can open everything, but saving is switched off.' : 'Full access on this local copy.'}</p>
                    <button type="button" onClick={() => form.setData({ ...form.data, email: demo.email, password: demo.password })} className="btn btn-ghost btn-sm mt-4">Fill in for me</button>
                </motion.div>
            )}

            <div className="auth-card" onPointerMove={spotlight}>
            <form onSubmit={(event) => { event.preventDefault(); submitForm(form, 'post', route('admin.login'), { onError: () => form.reset('password') }, captcha); }} noValidate>
                <Stagger className="space-y-6">
                    <EmailField label="Admin email" value={form.data.email} onChange={(value) => form.setData('email', value)} error={errors.email} autoComplete="username" autoFocus placeholder="admin@yourdomain.com" />
                    <PasswordField label="Password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={errors.password} required maxLength={72} autoComplete="current-password" placeholder="Admin password" />
                    <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                        onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                    <div>
                        <SubmitButton processing={form.processing} hasErrors={form.hasErrors} busyLabel="Checking" icon="lock">Sign in to admin</SubmitButton>
                        <TrustRow />
                    </div>
                </Stagger>
            </form>
            </div>
            {recoveryEnabled && <p className="mt-8 text-center text-sm text-mute"><Link href={route('admin.credentials.request')} className="link">Recover admin access</Link></p>}
        </>
    );
}

AdminLogin.layout = authLayout;
