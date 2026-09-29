import { useForm } from '@inertiajs/react';
import { Field } from '@/components/ui';
import { AuthHeading, authLayout } from '@/layouts/AuthLayout';
import { cn, route } from '@/lib/utils';

export default function AdminReset({ token }: { token: string }) {
    const form = useForm({ email: '', change_target: 'password', new_email: '', password: '', password_confirmation: '' });

    return (
        <>
            <AuthHeading label="Staff recovery" title="Update credentials">Choose whether to set a new password or move the account to a new email address.</AuthHeading>
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.credentials.reset', token), { preserveScroll: true }); }} noValidate className="space-y-5">
                <Field label="Current admin email" placeholder="admin@bookmymovie.pk" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={form.errors.email} required maxLength={150} autoComplete="username" />
                <fieldset>
                    <legend className="field-label">What do you want to change?</legend>
                    <div className="mt-2 grid grid-cols-2 border border-line-2 p-1">
                        {(['password', 'email'] as const).map((target) => (
                            <button key={target} type="button" onClick={() => form.setData('change_target', target)} aria-pressed={form.data.change_target === target}
                                className={cn('py-2.5 text-[11px] font-bold uppercase tracking-[.08em] transition [font-stretch:115%]', form.data.change_target === target ? 'bg-paper text-ink' : 'text-mute')}>
                                {target === 'password' ? 'Password' : 'Email'}
                            </button>
                        ))}
                    </div>
                </fieldset>
                {form.data.change_target === 'email' ? (
                    <Field label="New admin email" placeholder="Leave as is to keep it" type="email" value={form.data.new_email} onChange={(event) => form.setData('new_email', event.target.value)} error={form.errors.new_email} maxLength={150} />
                ) : (
                    <>
                        <Field label="New password" placeholder="At least 12 characters" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={form.errors.password} maxLength={72} autoComplete="new-password" hint="12+ characters with upper and lower case, a number and a symbol." />
                        <Field label="Confirm new password" placeholder="Type the new password again" type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} maxLength={72} autoComplete="new-password" />
                    </>
                )}
                <button type="submit" disabled={form.processing} className="btn btn-primary btn-lg w-full">Update credentials</button>
            </form>
        </>
    );
}

AdminReset.layout = authLayout;
