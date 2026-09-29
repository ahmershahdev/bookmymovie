import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Avatar from '@/components/Avatar';
import Icon from '@/components/Icon';
import { Field, Pagination } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { cn, route } from '@/lib/utils';
import type { PageLink } from '@/types';

export type MemberRow = {
    id: number; name: string; username: string; email: string; avatar: string | null; verified: boolean; banned: boolean;
    last_ip: string | null; last_login: string | null; joined: string | null; bookings: number; reviews: number;
};

interface Props {
    users: { data: MemberRow[]; links: PageLink[]; total: number };
    filters: { q: string; status: 'all' | 'active' | 'banned' };
    bannedIps: { id: number; ip: string; reason: string; user: string | null; expires: string | null; active: boolean; at: string | null }[];
    bannedDevices: { id: number; label: string; hash: string; reason: string; user: string | null; expires: string | null; active: boolean; at: string | null }[];
    counts: { total: number; banned: number; ips: number; devices: number };
}

export default function AdminUsers({ users, filters, bannedIps, bannedDevices, counts }: Props) {
    const [q, setQ] = useState(filters.q);
    const search = (next: Partial<Props['filters']>) => router.get(route('admin.users'), { ...filters, q, ...next }, { preserveState: true, preserveScroll: true, replace: true });

    return (
        <AdminLayout label="People" title="Members" lede="Everyone with an account. Open a member to see their bookings, reviews and sign-ins, or ban them and the IP they use."
            guide={<Guide id="users" steps={[
                ['Search', 'Type a name, @username, email or IP address, then filter to show only active or banned members.'],
                ['Open a member', 'See their bookings, reviews, loyalty points and recent sign-ins.'],
                ['Ban with a reason', 'A ban blocks the account, and optionally its IP and device, so a new account won’t help.'],
                ['Unban any time', 'Bans and blocked IPs/devices are listed at the bottom with an undo button.'],
            ]} />}>
            <div className="grid-lines grid-cols-2 lg:grid-cols-4">
                {[['Members', counts.total], ['Banned accounts', counts.banned], ['Blocked IPs', counts.ips], ['Blocked devices', counts.devices]].map(([label, value]) => (
                    <div key={label} className="p-6"><p className="label">{label}</p><p className="display mt-3 text-5xl">{Number(value).toLocaleString('en-PK')}</p></div>
                ))}
            </div>

            <section className="space-y-4" aria-label="Members">
                <form onSubmit={(event) => { event.preventDefault(); search({}); }} className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <Field label="Search" value={q} onChange={(event) => setQ(event.target.value)} placeholder="Name, @username, email or IP" className="sm:max-w-md sm:flex-1" />
                    <button type="submit" className="btn btn-primary">Search</button>
                    <div className="flex gap-1 sm:ms-auto">
                        {(['all', 'active', 'banned'] as const).map((status) => (
                            <button key={status} type="button" onClick={() => search({ status })} aria-pressed={filters.status === status} className={'chip capitalize'}>{status}</button>
                        ))}
                    </div>
                </form>

                <div className="overflow-x-auto border border-line" data-lenis-prevent>
                    <table className="w-full min-w-[860px] text-left text-sm">
                        <thead className="bg-ink-2">
                            <tr className="label">
                                {['Member', 'Email', 'Last sign-in', 'Bookings', 'Reviews', 'Status', ''].map((heading) => <th key={heading} scope="col" className="px-4 py-3 font-normal">{heading}</th>)}
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((user) => (
                                <tr key={user.id} className="border-t border-line align-middle transition-colors hover:bg-ink-2">
                                    <td className="px-4 py-3">
                                        <Link href={route('admin.users.show', user.id)} className="flex items-center gap-3">
                                            <Avatar name={user.name} seed={user.username} src={user.avatar} size={36} />
                                            <span className="min-w-0"><span className="block truncate font-semibold">{user.name}</span><span className="num block text-xs text-mute">@{user.username}</span></span>
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-paper-2">{user.email}{user.verified && <Icon name="check" size={13} className="ms-1.5 inline text-mint" />}</td>
                                    <td className="num px-4 py-3 text-xs text-mute">{user.last_login ?? 'Never'}<span className="block text-dim">{user.last_ip ?? '—'}</span></td>
                                    <td className="num px-4 py-3">{user.bookings}</td>
                                    <td className="num px-4 py-3">{user.reviews}</td>
                                    <td className="px-4 py-3"><span className={cn('tag', user.banned ? 'tag-signal' : 'tag-mint')}>{user.banned ? 'Banned' : 'Active'}</span></td>
                                    <td className="px-4 py-3 text-right"><Link href={route('admin.users.show', user.id)} className="btn btn-ghost btn-sm">Open <Icon name="arrow-right" size={14} /></Link></td>
                                </tr>
                            ))}
                            {users.data.length === 0 && <tr><td colSpan={7} className="px-4 py-10 text-center text-mute">No members match.</td></tr>}
                        </tbody>
                    </table>
                </div>
                <Pagination links={users.links} />
            </section>

            <IpBans bans={bannedIps} />
            <DeviceBans bans={bannedDevices} />
        </AdminLayout>
    );
}

AdminUsers.layout = null;

function IpBans({ bans }: { bans: Props['bannedIps'] }) {
    const form = useForm({ ip: '', reason: '', days: '' });

    return (
        <section className="grid gap-6 lg:grid-cols-12" aria-labelledby="ip-bans">
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.ips.store'), { preserveScroll: true, onSuccess: () => form.reset() }); }}
                className="panel space-y-4 p-6 lg:col-span-4" noValidate>
                <p id="ip-bans" className="label label-accent">Block an IP address</p>
                <p className="text-sm text-mute">Blocked addresses get a 403 on every page, signed in or not. Use it for scraping, card testing or abuse; ban the account too if there is one.</p>
                <Field label="IP address" value={form.data.ip} onChange={(event) => form.setData('ip', event.target.value)} error={form.errors.ip} placeholder="203.0.113.24" required />
                <Field label="Reason" placeholder="e.g. Repeated fake bookings" value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} error={form.errors.reason} required maxLength={200} />
                <Field label="For how many days?" placeholder="e.g. 30" type="number" min={1} max={3650} value={form.data.days} onChange={(event) => form.setData('days', event.target.value)} error={form.errors.days} hint="Leave empty to block until you lift it." />
                <button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary w-full"><Icon name="shield" size={16} /> Block address</button>
            </form>
            <div className="lg:col-span-8">
                <ul className="divide-y divide-line border border-line">
                    {bans.map((ban) => (
                        <li key={ban.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="num text-lg">{ban.ip} <span className={cn('tag ms-2', ban.active ? 'tag-signal' : '')}>{ban.active ? 'Blocked' : 'Expired'}</span></p>
                                <p className="mt-1 text-sm text-paper-2">{ban.reason}</p>
                                <p className="mt-1 text-xs text-mute">{ban.at}{ban.user && <> · @{ban.user}</>} · {ban.expires ? `until ${ban.expires}` : 'no expiry'}</p>
                            </div>
                            <button type="button" onClick={() => router.delete(route('admin.ips.destroy', ban.id), { preserveScroll: true })} className="btn btn-ghost btn-sm">Unblock</button>
                        </li>
                    ))}
                    {bans.length === 0 && <li className="p-8 text-sm text-mute">No IP addresses are blocked.</li>}
                </ul>
            </div>
        </section>
    );
}

function DeviceBans({ bans }: { bans: Props['bannedDevices'] }) {
    return (
        <section className="grid gap-6 lg:grid-cols-12" aria-labelledby="device-bans">
            <div className="lg:col-span-4">
                <p id="device-bans" className="label label-accent">Blocked devices</p>
                <p className="mt-3 text-sm text-mute">Browsers blocked along with a banned member. Each keeps a private, encrypted id, so a new account or a new network on the same browser still sees the ban.</p>
            </div>
            <ul className="divide-y divide-line border border-line lg:col-span-8">
                {bans.map((ban) => (
                    <li key={ban.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-lg font-semibold">{ban.label} <span className="num ms-1 text-xs text-dim">#{ban.hash}</span> <span className={cn('tag ms-2', ban.active ? 'tag-signal' : '')}>{ban.active ? 'Blocked' : 'Expired'}</span></p>
                            <p className="mt-1 text-sm text-paper-2">{ban.reason}</p>
                            <p className="mt-1 text-xs text-mute">{ban.at}{ban.user && <> · @{ban.user}</>} · {ban.expires ? `until ${ban.expires}` : 'no expiry'}</p>
                        </div>
                        <button type="button" onClick={() => router.delete(route('admin.devices.destroy', ban.id), { preserveScroll: true })} className="btn btn-ghost btn-sm">Unblock</button>
                    </li>
                ))}
                {bans.length === 0 && <li className="p-8 text-sm text-mute">No devices are blocked.</li>}
            </ul>
        </section>
    );
}
