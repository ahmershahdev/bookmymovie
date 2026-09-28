import { router, useForm } from '@inertiajs/react';
import CodeInput from '@/components/CodeInput';
import Icon from '@/components/Icon';
import QrCode from '@/components/QrCode';
import { Alert, Field } from '@/components/ui';
import AdminLayout from '@/layouts/AdminLayout';
import { route } from '@/lib/utils';

interface Props {
    enabled: boolean;
    setup: { secret: string; uri: string } | null;
    recoveryCodes: string[] | null;
    codesLeft: number;
    role: string;
}

export default function AdminSecurity({ enabled, setup, recoveryCodes, codesLeft, role }: Props) {
    const confirm = useForm({ code: '' });
    const disable = useForm({ password: '' });

    return (
        <AdminLayout label={`Signed in as ${role}`} title="My security" lede="Two-step sign-in asks for a 6-digit code from an authenticator app after your password, so a leaked password alone cannot open the back office.">
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
                        <Field label="Your password" type="password" value={disable.data.password} onChange={(event) => disable.setData('password', event.target.value)} error={disable.errors.password} autoComplete="current-password" />
                        <button type="submit" disabled={disable.processing} className="btn btn-danger">Turn off two-step sign-in</button>
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
