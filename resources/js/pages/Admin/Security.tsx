import { router, useForm } from '@inertiajs/react';
import CodeInput from '@/components/CodeInput';
import Icon from '@/components/Icon';
import QrCode from '@/components/QrCode';
import { Alert, Field } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { route } from '@/lib/utils';

interface Props {
    enabled: boolean;
    setup: { secret: string; uri: string } | null;
    recoveryCodes: string[] | null;
    codesLeft: number;
    role: string;
    profile: { name: string; email: string };
}

export default function AdminSecurity({ enabled, setup, recoveryCodes, codesLeft, role, profile }: Props) {
    const confirm = useForm({ code: '' });
    const disable = useForm({ password: '' });

    return (
        <AdminLayout label={`Signed in as ${role}`} title="My security" lede="Your name, sign-in email and password, plus two-step sign-in: a 6-digit code from an authenticator app after your password, so a leaked password alone cannot open the back office."
            guide={
                <Guide id="security" steps={[
                    ['Install an authenticator', 'Google Authenticator, Microsoft Authenticator, 1Password or Authy all work.'],
                    ['Scan the QR code', 'Press “Set up two-step sign-in”, then scan the square with the app.'],
                    ['Type the 6-digit code', 'This proves the app is linked. Two-step sign-in switches on straight away.'],
                    ['Keep the recovery codes', 'Shown once. Each lets you in one time if you lose your phone.'],
                ]} />
            }>
            <ProfileForm profile={profile} />
            {recoveryCodes && (
                <div className="panel border-volt/50 p-6">
                    <p className="label label-accent">Recovery codes, shown once</p>
                    <p className="mt-2 text-sm text-paper-2">Each works once if you lose your phone. Store them in a password manager or print them.</p>
                    <ul className="num mt-4 grid grid-cols-2 gap-2 sm:grid-cols-5">{recoveryCodes.map((code) => <li key={code} className="select-all border border-line bg-ink px-3 py-2 text-center">{code}</li>)}</ul>
                    <button type="button" className="btn btn-ghost btn-sm mt-4" onClick={() => navigator.clipboard?.writeText(recoveryCodes.join('\n'))}><Icon name="compare" size={14} /> Copy all</button>
                </div>
            )}

            {enabled ? (
                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="panel p-6">
                        <p className="flex items-center gap-2 text-lg font-semibold"><Icon name="shield" size={18} className="text-mint" /> Two-step sign-in is on</p>
                        <p className="mt-2 text-sm text-mute">{codesLeft} recovery {codesLeft === 1 ? 'code' : 'codes'} left.</p>
                    </div>
                    <form onSubmit={(event) => { event.preventDefault(); disable.delete(route('admin.security.disable'), { preserveScroll: true, onSuccess: () => disable.reset() }); }} className="panel space-y-4 p-6" noValidate>
                        <p className="label">Turn it off</p>
                        <Field label="Your password" placeholder="Your current password" type="password" value={disable.data.password} onChange={(event) => disable.setData('password', event.target.value)} error={disable.errors.password} autoComplete="current-password" />
                        <button type="submit" disabled={disable.processing} aria-busy={disable.processing} className="btn btn-danger">Turn off two-step sign-in</button>
                    </form>
                </section>
            ) : setup ? (
                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="panel p-6">
                        <p className="label label-accent">1 · Scan with your authenticator app</p>
                        <div className="mt-5 inline-block bg-white p-3"><QrCode value={setup.uri} size={200} label="Authenticator setup code" /></div>
                        <p className="mt-4 text-sm text-mute">Can't scan? Type this key into the app:</p>
                        <p className="num mt-1 select-all break-all text-lg tracking-wider">{setup.secret}</p>
                    </div>
                    <form onSubmit={(event) => { event.preventDefault(); confirm.post(route('admin.security.confirm'), { preserveScroll: true }); }} className="panel space-y-5 p-6" noValidate>
                        <p className="label label-accent">2 · Enter the 6-digit code it shows</p>
                        <CodeInput label="Code from the app" length={6} value={confirm.data.code} onChange={(value) => confirm.setData('code', value.replace(/\D/g, ''))} error={confirm.errors.code} />
                        <button type="submit" disabled={confirm.data.code.length !== 6 || confirm.processing} className="btn btn-primary w-full"><Icon name="check" size={16} /> Turn on</button>
                    </form>
                </section>
            ) : (
                <section className="panel max-w-2xl p-6">
                    <Alert icon="alert">Two-step sign-in is off for your account.</Alert>
                    <p className="mt-4 text-sm text-paper-2">Works with Google Authenticator, Microsoft Authenticator, 1Password, Authy and any other TOTP app. No SMS, no email needed.</p>
                    <button type="button" onClick={() => router.post(route('admin.security.begin'), {}, { preserveScroll: true })} className="btn btn-primary mt-6"><Icon name="shield" size={16} /> Set up two-step sign-in</button>
                </section>
            )}
        </AdminLayout>
    );
}

AdminSecurity.layout = null;

function ProfileForm({ profile }: { profile: Props['profile'] }) {
    const form = useForm({ _action: 'update_profile', name: profile.name, email: profile.email, current_password: '', password: '' });
    const errors = form.errors as Record<string, string>;
    const sensitive = form.data.password !== '' || form.data.email.toLowerCase() !== profile.email.toLowerCase();

    return (
        <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.dashboard'), { preserveScroll: true, onSuccess: () => form.reset('password', 'current_password') }); }} className="panel grid gap-5 p-6 md:grid-cols-2" noValidate>
            <div className="md:col-span-2"><p className="label label-accent">Profile</p><p className="mt-1.5 text-xs text-mute">Changing your email or password asks for your current password first.</p></div>
            <Field label="Full name" placeholder="e.g. Ayesha Khan" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={errors.name} autoComplete="name" />
            <Field label="Sign-in email" placeholder="you@bookmymovie.pk" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} autoComplete="username" />
            <Field label="New password (optional)" type="password" placeholder="10+ characters: upper, lower, number, symbol" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={errors.password} autoComplete="new-password" />
            <Field label={sensitive ? 'Current password (required)' : 'Current password'} placeholder={'Needed to change email or password'} type="password" value={form.data.current_password} onChange={(event) => form.setData('current_password', event.target.value)} error={errors.current_password} autoComplete="current-password" />
            <div className="md:col-span-2"><button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary">{form.processing ? 'Saving…' : 'Save profile'}</button></div>
        </form>
    );
}
