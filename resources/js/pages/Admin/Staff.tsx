import { router, useForm } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { Field, Select } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { cn, route } from '@/lib/utils';

type Member = { id: number; name: string; email: string; role: string; role_label: string; active: boolean; two_factor: boolean; last_login: string; me: boolean };
type Role = { key: string; label: string };

const ROLE_HELP: Record<string, string> = {
    superadmin: 'Everything, including staff accounts and site settings.',
    admin: 'Films, shows, prices, coupons, stock, members, bans, reviews and refunds.',
    box_office: 'Sees the dashboard, bookings and members, and checks tickets in at the door.',
};

export default function AdminStaff({ staff, roles }: { staff: Member[]; roles: Role[] }) {
    const form = useForm({ name: '', email: '', role: 'box_office', password: '' });
    const errors = form.errors as Record<string, string>;

    return (
        <AdminLayout label="Owner" title="Staff & roles" lede="Who can sign in to the back office and what each person may do. Every change is written to the audit log."
            guide={<Guide id="staff" steps={[
                ['Invite a person', 'Add their name, email and a starting password, and pick a role.'],
                ['Choose the smallest role', 'Box office can check tickets in and view bookings; Admin can also edit films and offers; Owner can do everything.'],
                ['Require two-step sign-in', 'The shield shows who has it on. Reset it if someone loses their phone.'],
                ['Deactivate, don’t delete', 'Switching someone off ends their session on their next click and keeps their history.'],
            ]} />}>
            <section className="grid gap-3 md:grid-cols-3">
                {roles.map((role) => (
                    <div key={role.key} className="border border-line p-5">
                        <p className="label label-accent">{role.label}</p>
                        <p className="mt-2 text-sm text-paper-2">{ROLE_HELP[role.key]}</p>
                        <p className="num mt-3 text-xs text-mute">{staff.filter((member) => member.role === role.key).length} people</p>
                    </div>
                ))}
            </section>

            <section className="grid gap-6">
                <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.staff.store'), { preserveScroll: true, onSuccess: () => form.reset() }); }} className="panel order-last grid gap-4 p-6 md:grid-cols-2" noValidate>
                    <p className="label label-accent md:col-span-2">Add a staff account</p>
                    <Field label="Full name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={errors.name} required placeholder="e.g. Bilal Ahmed" />
                    <Field label="Work email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={errors.email} required placeholder="name@yourcinema.com" />
                    <Select label="Role" value={form.data.role} onChange={(event) => form.setData('role', event.target.value)} options={roles.map((role) => [role.key, role.label] as [string, string])} hint={ROLE_HELP[form.data.role]} />
                    <Field label="Temporary password" placeholder="10+ characters: upper, lower, number, symbol" type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} error={errors.password} required autoComplete="new-password" hint="10+ characters with upper and lower case, a number and a symbol. Ask them to change it and turn on two-step sign-in." />
                    <div className="md:col-span-2"><button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary w-full sm:w-auto"><Icon name="plus" size={16} /> Create account</button></div>
                </form>

                <div className="overflow-x-auto border border-line" data-lenis-prevent>
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead className="bg-ink-2"><tr className="label">{['Person', 'Role', 'Two-step', 'Last sign-in', 'Access'].map((heading) => <th key={heading} scope="col" className="whitespace-nowrap px-4 py-3 font-normal">{heading}</th>)}</tr></thead>
                        <tbody>
                            {staff.map((member) => (
                                <tr key={member.id} className={cn('border-t border-line align-middle', !member.active && 'opacity-50')}>
                                    <td className="px-4 py-3"><span className="block font-semibold">{member.name}{member.me && <span className="tag ms-2">You</span>}</span><span className="text-xs text-mute">{member.email}</span></td>
                                    <td className="px-4 py-3">
                                        <select value={member.role} disabled={member.me} aria-label={`Role for ${member.name}`} className="input !min-h-9 min-w-[9.5rem] !py-1 text-sm"
                                            onChange={(event) => router.put(route('admin.staff.update', member.id), { role: event.target.value, active: member.active }, { preserveScroll: true })}>
                                            {roles.map((role) => <option key={role.key} value={role.key}>{role.label}</option>)}
                                        </select>
                                    </td>
                                    <td className="px-4 py-3">
                                        {member.two_factor
                                            ? <span className="flex items-center gap-2"><span className="tag tag-mint">On</span>{!member.me && <button type="button" className="text-xs text-mute hover:text-signal" onClick={() => router.post(route('admin.staff.reset-2fa', member.id), {}, { preserveScroll: true })}>Reset</button>}</span>
                                            : <span className="tag tag-signal">Off</span>}
                                    </td>
                                    <td className="num whitespace-nowrap px-4 py-3 text-xs text-mute">{member.last_login}</td>
                                    <td className="px-4 py-3">
                                        <button type="button" disabled={member.me} onClick={() => router.put(route('admin.staff.update', member.id), { role: member.role, active: !member.active }, { preserveScroll: true })}
                                            className={cn('btn btn-sm', member.active ? 'btn-ghost' : 'btn-primary')}>{member.active ? 'Deactivate' : 'Reactivate'}</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>
        </AdminLayout>
    );
}

AdminStaff.layout = null;
