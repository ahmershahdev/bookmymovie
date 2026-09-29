import { Link, router, useForm } from '@inertiajs/react';
import Avatar from '@/components/Avatar';
import Icon from '@/components/Icon';
import { Checkbox, Field } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { cn, money, route } from '@/lib/utils';
import type { MemberRow } from './Users';

interface Props {
    member: MemberRow & {
        phone: string | null; city: string | null; bio: string | null; blocked_reason: string | null; blocked_at: string | null;
        loyalty_points: number; two_factor: boolean; ip_banned: boolean; spent: number;
    };
    bookings: { number: string; film: string | null; at: string | null; total: number; status: string }[];
    reviews: { id: number; film: string | null; rating: number; title: string | null; text: string; approved: boolean; verified: boolean }[];
    logins: { ip: string | null; agent: string | null; at: string }[];
    devices: { label: string; ip: string | null; first: string; last: string; banned: boolean }[];
    linked: { id: number; username: string; name: string; banned: boolean }[];
}

export default function AdminUserShow({ member, bookings, reviews, logins, devices, linked }: Props) {
    const ban = useForm({ reason: '', ban_ip: true, ban_devices: true, days: '' });

    return (
        <AdminLayout label="Member" title={`@${member.username}`}
            lede="One member's account: profile, bookings, reviews and every device and IP they used."
            guide={<Guide id="member" steps={[
                ['Check the profile', 'Name, email, phone, points and when they last signed in.'],
                ['Read their history', 'Bookings and reviews show what they have actually done on the site.'],
                ['Look for shared devices', 'Accounts that used the same browser are listed; that is often the same person.'],
                ['Block only with a reason', 'A block also covers their IPs and browsers. Write why: it is kept on record and can be lifted any time.'],
            ]} />}
            actions={<Link href={route('admin.users')} className="btn btn-ghost"><Icon name="arrow-left" size={14} /> All members</Link>}>
            <section className="grid gap-6 lg:grid-cols-12">
                <div className="panel p-6 lg:col-span-5">
                    <div className="flex items-center gap-4">
                        <Avatar name={member.name} seed={member.username} src={member.avatar} size={72} />
                        <div className="min-w-0">
                            <p className="headline truncate text-3xl">{member.name}</p>
                            <p className="num text-sm text-mute">@{member.username}{member.city && <> · {member.city}</>}</p>
                        </div>
                    </div>
                    {member.bio && <p className="mt-4 text-sm text-paper-2">{member.bio}</p>}
                    <dl className="mt-6 grid grid-cols-2 gap-4 text-sm">
                        {[
                            ['Email', member.email + (member.verified ? ' ✓' : '')],
                            ['Phone', member.phone ?? '—'],
                            ['Joined', member.joined ?? '—'],
                            ['Last sign-in', member.last_login ?? 'Never'],
                            ['Last IP', member.last_ip ?? '—'],
                            ['Two-step sign-in', member.two_factor ? 'On' : 'Off'],
                            ['Spent', money(member.spent)],
                            ['Points', member.loyalty_points.toLocaleString('en-PK')],
                        ].map(([label, value]) => (
                            <div key={label}><dt className="label">{label}</dt><dd className="mt-1 break-all">{value}</dd></div>
                        ))}
                    </dl>
                    <Link href={route('profile.show', member.username)} className="btn btn-ghost btn-sm mt-6">Public profile <Icon name="arrow-up-right" size={14} /></Link>
                </div>

                <div className="lg:col-span-7">
                    {member.banned ? (
                        <div className="panel border-signal/50 p-6">
                            <p className="label text-signal">Banned {member.blocked_at && <>on {member.blocked_at}</>}</p>
                            <p className="mt-3 text-lg">{member.blocked_reason}</p>
                            {(member.ip_banned || devices.some((device) => device.banned)) && (
                                <p className="mt-2 text-sm text-mute">Their {[member.ip_banned && 'IPs', devices.some((device) => device.banned) && 'devices'].filter(Boolean).join(' and ')} are blocked too. Lifting the ban unblocks them.</p>
                            )}
                            <button type="button" onClick={() => router.post(route('admin.users.unban', member.id), {}, { preserveScroll: true })} className="btn btn-primary mt-6">Lift the ban</button>
                        </div>
                    ) : (
                        <form onSubmit={(event) => { event.preventDefault(); ban.post(route('admin.users.ban', member.id), { preserveScroll: true }); }} className="panel space-y-4 p-6" noValidate>
                            <p className="label label-accent">Ban this member</p>
                            <p className="text-sm text-mute">They are signed out on every device and cannot sign in. Their reviews stay up unless you hide them.</p>
                            <Field label="Reason (kept on record)" value={ban.data.reason} onChange={(event) => ban.setData('reason', event.target.value)} error={ban.errors.reason} required maxLength={200} placeholder="Abusive reviews, chargeback fraud…" />
                            <Checkbox checked={ban.data.ban_devices} onChange={(event) => ban.setData('ban_devices', event.target.checked)} disabled={devices.length === 0}>
                                Block every browser they used ({devices.length} on record), so they cannot sign up again from it
                            </Checkbox>
                            <Checkbox checked={ban.data.ban_ip} onChange={(event) => ban.setData('ban_ip', event.target.checked)} disabled={!member.last_ip}>
                                Block the IP addresses they signed in from {member.last_ip ? <span className="num">(latest {member.last_ip})</span> : '(none on record)'}
                            </Checkbox>
                            {(ban.data.ban_ip || ban.data.ban_devices) && (
                                <Field label="Block length in days" placeholder="e.g. 30" type="number" min={1} max={3650} value={ban.data.days} onChange={(event) => ban.setData('days', event.target.value)} error={ban.errors.days}
                                    hint="Empty means until lifted. Shared IPs (offices, mobile networks) can block innocent people, so a limit such as 30 is kinder." />
                            )}
                            <button type="submit" disabled={ban.processing} aria-busy={ban.processing} className="btn w-full bg-signal text-noir hover:brightness-110"><Icon name="lock" size={16} /> Ban member</button>
                        </form>
                    )}

                    {linked.length > 0 && (
                        <div className="mt-6 border border-signal/40 bg-signal/[.04]">
                            <p className="label flex items-center gap-2 border-b border-line px-4 py-3 text-signal"><Icon name="alert" size={13} /> Other accounts on the same browser</p>
                            <ul className="divide-y divide-line text-sm">
                                {linked.map((other) => (
                                    <li key={other.id} className="flex items-center justify-between gap-4 px-4 py-3">
                                        <Link href={route('admin.users.show', other.id)} className="link">{other.name} <span className="num text-mute">@{other.username}</span></Link>
                                        <span className={cn('tag', other.banned ? 'tag-signal' : 'tag-mint')}>{other.banned ? 'Banned' : 'Active'}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <div className="mt-6 border border-line">
                        <p className="label border-b border-line px-4 py-3">Devices</p>
                        <ul className="divide-y divide-line text-sm">
                            {devices.map((device, index) => (
                                <li key={index} className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 py-3">
                                    <span className="flex items-center gap-2 font-semibold"><Icon name="screen" size={14} className="text-mute" /> {device.label}</span>
                                    <span className="num text-xs text-mute">{device.ip ?? '—'} · first {device.first} · last {device.last}</span>
                                    {device.banned && <span className="tag tag-signal">Blocked</span>}
                                </li>
                            ))}
                            {devices.length === 0 && <li className="px-4 py-6 text-mute">No devices recorded yet. They are logged from the next sign-in.</li>}
                        </ul>
                    </div>

                    <div className="mt-6 border border-line">
                        <p className="label border-b border-line px-4 py-3">Recent sign-ins</p>
                        <ul className="divide-y divide-line text-sm">
                            {logins.map((login, index) => (
                                <li key={index} className="flex justify-between gap-4 px-4 py-3"><span className="num">{login.ip ?? '—'}</span><span className="truncate text-xs text-mute">{login.agent}</span><span className="num shrink-0 text-xs text-mute">{login.at}</span></li>
                            ))}
                            {logins.length === 0 && <li className="px-4 py-6 text-mute">No sign-ins recorded.</li>}
                        </ul>
                    </div>
                </div>
            </section>

            <section className="grid gap-6 lg:grid-cols-2">
                <div className="border border-line">
                    <p className="label border-b border-line px-4 py-3">Bookings ({member.bookings})</p>
                    <ul className="divide-y divide-line text-sm">
                        {bookings.map((booking) => (
                            <li key={booking.number} className="flex items-center justify-between gap-4 px-4 py-3">
                                <span className="min-w-0"><span className="block truncate font-semibold">{booking.film}</span><span className="num text-xs text-mute">{booking.number} · {booking.at}</span></span>
                                <span className="text-right"><span className="num block">{money(booking.total)}</span><span className="text-xs capitalize text-mute">{booking.status}</span></span>
                            </li>
                        ))}
                        {bookings.length === 0 && <li className="px-4 py-6 text-mute">No bookings.</li>}
                    </ul>
                </div>
                <div className="border border-line">
                    <p className="label border-b border-line px-4 py-3">Reviews ({member.reviews})</p>
                    <ul className="divide-y divide-line text-sm">
                        {reviews.map((review) => (
                            <li key={review.id} className="px-4 py-3">
                                <p className="flex items-center justify-between gap-3"><span className="font-semibold">{review.film}</span><span className="num text-accent">{'★'.repeat(review.rating)}</span></p>
                                {review.title && <p className="mt-1 text-paper">{review.title}</p>}
                                <p className="mt-1 line-clamp-2 text-paper-2">{review.text}</p>
                                <p className="mt-1 text-xs text-mute">{review.verified ? 'Verified booking' : 'Unverified'} · <span className={cn(!review.approved && 'text-signal')}>{review.approved ? 'Live' : 'Hidden'}</span></p>
                            </li>
                        ))}
                        {reviews.length === 0 && <li className="px-4 py-6 text-mute">No reviews.</li>}
                    </ul>
                </div>
            </section>
        </AdminLayout>
    );
}

AdminUserShow.layout = null;
