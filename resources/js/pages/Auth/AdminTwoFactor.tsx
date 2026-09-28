import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import CodeInput from '@/components/CodeInput';
import { spotlight, SubmitButton } from '@/components/auth';
import { Field } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { route } from '@/lib/utils';

export default function AdminTwoFactor() {
    const [recovery, setRecovery] = useState(false);
    const form = useForm({ code: '' });
    const ready = recovery ? form.data.code.trim().length >= 9 : form.data.code.length === 6;

    return (
        <>
            <AuthHeading step="02 / 02" label="Staff only" title="One more step">
                {recovery ? 'Enter one of your recovery codes. Each one works once.' : 'Open your authenticator app and enter the 6-digit code for BookMyMovie Admin.'}
            </AuthHeading>
            <div className="auth-card" onPointerMove={spotlight}>
                <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.two-factor.verify'), { preserveScroll: true, onError: () => form.reset('code') }); }} className="space-y-6" noValidate>
                    {recovery
                        ? <Field label="Recovery code" value={form.data.code} onChange={(event) => form.setData('code', event.target.value.toUpperCase())} error={form.errors.code} placeholder="ABCD-EFGH" autoFocus inputClassName="num tracking-widest" />
                        : <CodeInput label="Authenticator code" length={6} value={form.data.code} onChange={(value) => form.setData('code', value.replace(/\D/g, ''))} error={form.errors.code} />}
                    <SubmitButton processing={form.processing} hasErrors={form.hasErrors} busyLabel="Checking" icon="lock">{ready ? 'Verify and sign in' : 'Verify'}</SubmitButton>
                </form>
            </div>
            <div className="mt-8 flex items-center justify-between gap-4 text-sm">
                <button type="button" className="link text-mute" onClick={() => { setRecovery(!recovery); form.reset('code'); }}>{recovery ? 'Use the app instead' : 'Lost your phone? Use a recovery code'}</button>
                <Link href={route('admin.login')} className="link text-mute">Start again</Link>
            </div>
        </>
    );
}

AdminTwoFactor.layout = authLayout;
