import { Link, router, useForm } from '@inertiajs/react';
import CodeInput from '@/components/CodeInput';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { route } from '@/lib/utils';
import { useT } from '@/lib/i18n';

export default function TwoFactor({ email, minutes }: { email: string; minutes: number }) {
    const t = useT();
    const form = useForm({ code: '' });

    return (
        <>
            <AuthHeading step="02 / 02" label={t('Two-step sign-in')} title={t('Check your inbox')}>
                {t('We sent a 6-digit code to')} <span className="num text-paper">{email}</span>. {t('It expires in :minutes minutes.', { minutes })}
            </AuthHeading>

            <form onSubmit={(event) => { event.preventDefault(); form.post(route('user.two-factor'), { preserveScroll: true }); }} noValidate className="space-y-6">
                <CodeInput label={t('Sign-in code')} length={6} value={form.data.code} onChange={(value) => form.setData('code', value.replace(/\D/g, ''))} error={form.errors.code} />
                <button type="submit" disabled={form.data.code.length !== 6 || form.processing} className="btn btn-primary btn-lg w-full">{t('Sign in')}</button>
            </form>

            <p className="mt-8 text-center text-sm text-mute">
                {t('Didn’t get it? Check spam, or')}{' '}
                <button type="button" className="link font-semibold text-accent" onClick={() => router.post(route('user.two-factor.resend'), {}, { preserveScroll: true })}>{t('send a new code')}</button>.
            </p>
            <p className="mt-3 text-center text-sm"><Link href={route('user.login')} className="link text-mute">{t('Use a different account')}</Link></p>
        </>
    );
}

TwoFactor.layout = authLayout;
