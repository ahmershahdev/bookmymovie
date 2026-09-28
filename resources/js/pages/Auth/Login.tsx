import { Link, useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import { Divider, EmailField, PasswordField, spotlight, SubmitButton, TrustRow } from '@/components/auth';
import Icon from '@/components/Icon';
import SocialLogin from '@/components/SocialLogin';
import { Checkbox } from '@/components/ui';
import { AuthHeading, authLayout, Stagger } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

type Provider = { key: string; label: string; url: string };

export default function Login({ captcha, providers }: { captcha: CaptchaData; providers: Provider[] }) {
    const form = useForm({ email: '', password: '', remember: false, ...captchaDefaults });
    const errors = form.errors as Record<string, string>;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        submitForm(form, 'post', route('user.login'), { onError: () => form.reset('password') }, captcha);
    };

    return (
        <>
            <AuthHeading label="Sign in" title="Welcome back">Your tickets, seat holds and watchlist are right where you left them.</AuthHeading>

            <div className="auth-card" onPointerMove={spotlight}>
                {providers.length > 0 && (<><SocialLogin providers={providers} /><Divider>or with email</Divider></>)}

                <form onSubmit={submit} noValidate>
                    <Stagger className="space-y-6">
                        <EmailField value={form.data.email} onChange={(value) => form.setData('email', value)} error={errors.email} autoFocus />
                        <PasswordField label="Password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={errors.password}
                            required maxLength={72} autoComplete="current-password" placeholder="Your password"
                            aside={<Link href={route('password.request')} className="link text-xs text-mute hover:text-accent">Forgot password?</Link>} />
                        <Checkbox checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)}>Keep me signed in on this device for 30 days</Checkbox>
                        <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                            onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                        <div>
                            <SubmitButton processing={form.processing} hasErrors={form.hasErrors} busyLabel="Signing you in">Sign in</SubmitButton>
                            <TrustRow />
                        </div>
                    </Stagger>
                </form>
            </div>

            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ delay: 0.6 }} className="mt-8 flex items-center justify-between gap-4 text-sm">
                <span className="text-mute">New to BookMyMovie?</span>
                <Link href={route('user.register')} className="group inline-flex items-center gap-2 font-semibold text-accent">
                    Create a free account <Icon name="arrow-right" size={16} className="transition-transform duration-300 group-hover:translate-x-1" />
                </Link>
            </motion.div>
        </>
    );
}

Login.layout = authLayout;
