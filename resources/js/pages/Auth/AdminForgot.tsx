import { Link, useForm } from '@inertiajs/react';
import { Field } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { route } from '@/lib/utils';

export default function AdminForgot() {
    const form = useForm({ secret_key: '', email: '' });

    return (
        <>
            <AuthHeading label="Staff recovery" title="Recover access">Enter the recovery secret from the server configuration. A single-use link is emailed to the admin address.</AuthHeading>
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.credentials.request'), { preserveScroll: true, onError: () => form.reset('secret_key') }); }} noValidate className="space-y-5">
                <Field label="Recovery secret" type="password" value={form.data.secret_key} onChange={(event) => form.setData('secret_key', event.target.value)} error={form.errors.secret_key} required maxLength={200} autoComplete="off" />
                <Field label="Admin email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} required maxLength={150} autoComplete="username" />
                <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg w-full">Email recovery link</button>
            </form>
            <p className="mt-8 text-center text-sm text-mute"><Link href={route('admin.login')} className="link">Back to admin sign in</Link></p>
        </>
    );
}

AdminForgot.layout = authLayout;
