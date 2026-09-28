import { useForm } from '@inertiajs/react';
import { Field } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { route } from '@/lib/utils';

export default function AdminRegister() {
    const form = useForm({ invite_code: '', name: '', email: '', password: '', password_confirmation: '' });

    return (
        <>
            <AuthHeading label="Staff invitation" title="Join the team">You need the invitation code issued by the site owner. Admin passwords must be at least 12 characters.</AuthHeading>
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.register'), { preserveScroll: true, onError: () => form.reset('password', 'password_confirmation') }); }} noValidate className="space-y-5">
                <Field label="Invitation code" type="password" value={form.data.invite_code} onChange={(event) => form.setData('invite_code', event.target.value)} error={form.errors.invite_code} required maxLength={200} autoComplete="off" />
                <Field label="Full name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={form.errors.name} required minLength={3} maxLength={100} autoComplete="name" />
                <Field label="Work email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} required maxLength={150} autoComplete="email" />
                <Field label="Password" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={form.errors.password} required maxLength={72} autoComplete="new-password" hint="12+ characters with upper and lower case, a number and a symbol." />
                <Field label="Confirm password" type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} required maxLength={72} autoComplete="new-password" />
                <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg w-full">Create admin account</button>
            </form>
        </>
    );
}

AdminRegister.layout = authLayout;
