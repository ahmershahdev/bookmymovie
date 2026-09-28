import { router, useForm } from '@inertiajs/react';
import CodeInput from '@/components/CodeInput';
import { Field } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { route } from '@/lib/utils';

export default function VerifyEmail({ email }: { email: string }) {
    const form = useForm({ email, code: '' });

    return (
        <>
            <AuthHeading step="02 / 02" label="One last step" title="Check your inbox">
                We sent an 8-character code to <span className="text-paper">{form.data.email || 'your email'}</span>. It expires in 15 minutes.
            </AuthHeading>

            <form onSubmit={(event) => { event.preventDefault(); form.post(route('user.verify'), { preserveScroll: true }); }} noValidate className="space-y-6">
                <Field label="Email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} required maxLength={150} autoComplete="email" />
                <CodeInput value={form.data.code} onChange={(value) => form.setData('code', value)} error={form.errors.code} />
                <button type="submit" disabled={form.data.code.length !== 8 || form.processing} className="btn btn-primary btn-lg w-full">Verify email</button>
            </form>

            <p className="mt-8 text-center text-sm text-mute">
                Didn’t get it? Check spam, or{' '}
                <button type="button" className="link font-semibold text-accent" onClick={() => router.post(route('user.verify.resend'), { email: form.data.email }, { preserveScroll: true, preserveState: true })}>send a new code</button>.
            </p>
        </>
    );
}

VerifyEmail.layout = authLayout;
