import { Link, useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import Captcha, { captchaDefaults } from '@/components/Captcha';
import { EmailField, spotlight, SubmitButton } from '@/components/auth';
import Icon from '@/components/Icon';
import { AuthHeading, authLayout, Stagger } from '@/layouts/AuthLayout';
import { submitForm } from '@/lib/form';
import { route } from '@/lib/utils';
import type { Captcha as CaptchaData } from '@/types';

const steps = [
    ['mail', 'Enter your email', 'The one you signed up with.'],
    ['lock', 'Get a code', 'Eight characters, within a minute.'],
    ['check', 'New password', 'The code works once, for 15 minutes.'],
];

export default function ForgotPassword({ captcha }: { captcha: CaptchaData }) {
    const form = useForm({ email: '', ...captchaDefaults });
    const errors = form.errors as Record<string, string>;

    return (
        <>
            <AuthHeading step="01 / 02" label="Account recovery" title="Locked out?">It happens to everyone. Three quick steps and you are back in your seat.</AuthHeading>

            <ol className="mb-8 grid grid-cols-3 border border-line">
                {steps.map(([icon, title, text], index) => (
                    <motion.li key={title} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.15 + index * 0.08, duration: 0.5, ease: [0.16, 1, 0.3, 1] }}
                        className={`relative p-3 sm:p-4 ${index > 0 ? 'border-s border-line' : ''} ${index === 0 ? 'bg-ink-2' : ''}`}>
                        {index === 0 && <span className="absolute inset-x-0 top-0 h-0.5 bg-volt" />}
                        <span className={`grid h-8 w-8 place-items-center border ${index === 0 ? 'border-volt bg-volt text-noir' : 'border-line-2 text-dim'}`}><Icon name={icon} size={15} /></span>
                        <p className="mt-3 text-[13px] font-semibold leading-tight">{title}</p>
                        <p className="mt-1 hidden text-xs text-mute sm:block">{text}</p>
                    </motion.li>
                ))}
            </ol>

            <div className="auth-card" onPointerMove={spotlight}>
                <form onSubmit={(event) => { event.preventDefault(); submitForm(form, 'post', route('password.request'), {}, captcha); }} noValidate>
                    <Stagger className="space-y-6">
                        <EmailField value={form.data.email} onChange={(value) => form.setData('email', value)} error={errors.email} autoFocus hint="We will send a one-time code if an account exists." />
                        <Captcha captcha={captcha} answer={form.data.custom_captcha_answer} honeypot={form.data.website_url} error={errors.captcha}
                            onAnswer={(value) => form.setData('custom_captcha_answer', value)} onHoneypot={(value) => form.setData('website_url', value)} onV2Token={(token) => form.setData('g-recaptcha-response', token)} />
                        <SubmitButton processing={form.processing} hasErrors={form.hasErrors} busyLabel="Sending your code" icon="mail">Email me a reset code</SubmitButton>
                    </Stagger>
                </form>
            </div>

            <p className="mt-6 flex gap-3 text-xs leading-relaxed text-mute">
                <Icon name="shield" size={16} className="shrink-0 text-accent" /> You will see the same message whether or not the email has an account, so nobody can use this page to find out who is a member.
            </p>

            <div className="mt-8 flex items-center justify-between gap-4 border-t border-line pt-6 text-sm">
                <span className="text-mute">Remembered it?</span>
                <Link href={route('user.login')} className="group inline-flex items-center gap-2 font-semibold text-accent"><Icon name="arrow-left" size={16} className="transition-transform duration-300 group-hover:-translate-x-1" /> Back to sign in</Link>
            </div>
        </>
    );
}

ForgotPassword.layout = authLayout;
