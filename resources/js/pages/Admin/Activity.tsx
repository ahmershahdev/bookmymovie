import { router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Icon from '@/components/Icon';
import { Field, TextArea } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { cn, money, route } from '@/lib/utils';

type Log = { id: number; at: string; actor: string; actor_name: string; action: string; subject: string | null; changes: Record<string, unknown> | null; ip: string | null };
type Booking = { number: string; customer: string | null; film: string | null; starts: string | null; total: number; gift_card: number; points: number; method: string; payment: string; status: string; refundable: boolean };
type GiftCard = { id: number; code: string; balance: number; initial: number; recipient: string | null; expires: string | null; active: boolean };

interface Props {
    logs: Log[];
    bookings: Booking[];
    giftCards: GiftCard[];
}

const tabs = [['log', 'Audit log', 'shield'], ['refunds', 'Bookings & refunds', 'ticket'], ['gift', 'Gift cards', 'gift']] as const;
type Tab = (typeof tabs)[number][0];

/** Back office: who changed what, cancellations with refunds, and gift cards. */
export default function AdminActivity({ logs, bookings, giftCards }: Props) {
    const [tab, setTab] = useState<Tab>('log');

    return (
        <AdminLayout label="Back office" title="Bookings & refunds" lede="Every admin and customer action that changes data, cancellations with refunds, and gift cards."
            guide={<Guide id="activity" steps={[
                ['Find the booking', 'Recent bookings are listed with their number, as printed on the ticket and confirmation email.'],
                ['Refund or cancel', 'Write a short reason, then press the button. Paid bookings are refunded and cancelled; the seats go back on sale.'],
                ['Issue gift cards', 'Pick an amount and share the code with the customer. Deactivate a card to stop it being spent.'],
                ['Check the audit log', 'Every change made by staff or customers is listed with who, what and when.'],
            ]} />}>
            <div className="flex flex-wrap gap-1 border-b border-line" role="tablist" aria-label="Sections">
                {tabs.map(([id, label, icon]) => (
                    <button key={id} type="button" role="tab" aria-selected={tab === id} onClick={() => setTab(id)}
                        className={cn('-mb-px flex items-center gap-2 border-b-2 px-4 py-3 text-[11px] font-semibold uppercase tracking-[.08em] transition [font-stretch:115%]',
                            tab === id ? 'border-volt text-paper' : 'border-transparent text-mute hover:text-paper')}>
                        <Icon name={icon} size={15} /> {label}
                        <span className="num text-[10px] text-dim">{id === 'log' ? logs.length : id === 'refunds' ? bookings.length : giftCards.length}</span>
                    </button>
                ))}
            </div>

            {tab === 'log' && <AuditLog logs={logs} />}
            {tab === 'refunds' && <Refunds bookings={bookings} />}
            {tab === 'gift' && <GiftCards cards={giftCards} />}
        </AdminLayout>
    );
}

AdminActivity.layout = null;

function AuditLog({ logs }: { logs: Log[] }) {
    const [query, setQuery] = useState('');
    const [actor, setActor] = useState('all');
    const [open, setOpen] = useState<number | null>(null);
    const actors = useMemo(() => ['all', ...new Set(logs.map((log) => log.actor))], [logs]);

    const shown = useMemo(() => {
        const q = query.toLowerCase().trim();
        return logs.filter((log) => (actor === 'all' || log.actor === actor)
            && (!q || [log.action, log.actor_name, log.subject ?? '', log.ip ?? ''].some((value) => value.toLowerCase().includes(q))));
    }, [logs, query, actor]);

    return (
        <section className="space-y-4" aria-label="Audit log">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                <Field label="Search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Action, person, booking or IP" className="sm:max-w-sm sm:flex-1" />
                <div className="flex flex-wrap gap-1">
                    {actors.map((value) => (
                        <button key={value} type="button" onClick={() => setActor(value)} aria-pressed={actor === value} className={'chip capitalize'}>{value}</button>
                    ))}
                </div>
            </div>

            <div className="overflow-x-auto border border-line" data-lenis-prevent>
                <table className="w-full min-w-[760px] text-left text-sm">
                    <thead className="bg-ink-2">
                        <tr className="label">
                            <th scope="col" className="px-4 py-3 font-normal">When</th>
                            <th scope="col" className="px-4 py-3 font-normal">Who</th>
                            <th scope="col" className="px-4 py-3 font-normal">Action</th>
                            <th scope="col" className="px-4 py-3 font-normal">On</th>
                            <th scope="col" className="px-4 py-3 font-normal">IP</th>
                            <th scope="col" className="px-4 py-3 font-normal"><span className="sr-only">Changes</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        {shown.map((log) => (
                            <LogRow key={log.id} log={log} open={open === log.id} onToggle={() => setOpen(open === log.id ? null : log.id)} />
                        ))}
                        {shown.length === 0 && (
                            <tr><td colSpan={6} className="px-4 py-10 text-center text-mute">Nothing matches.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
            <p className="text-xs text-dim">Showing the latest {logs.length} entries. Entries are written by the app and cannot be edited here.</p>
        </section>
    );
}

function LogRow({ log, open, onToggle }: { log: Log; open: boolean; onToggle: () => void }) {
    const tone = log.actor === 'admin' ? 'tag-volt' : log.actor === 'user' ? 'tag-mint' : '';
    return (
        <>
            <tr className="border-t border-line align-top">
                <td className="num whitespace-nowrap px-4 py-3 text-xs text-mute">{log.at}</td>
                <td className="px-4 py-3"><span className={cn('tag me-2', tone)}>{log.actor}</span>{log.actor_name}</td>
                <td className="num px-4 py-3 text-xs">{log.action}</td>
                <td className="num px-4 py-3 text-xs text-paper-2">{log.subject ?? '—'}</td>
                <td className="num px-4 py-3 text-xs text-mute">{log.ip ?? '—'}</td>
                <td className="px-4 py-3 text-right">
                    {log.changes && (
                        <button type="button" onClick={onToggle} aria-expanded={open} className="link text-xs text-accent">{open ? 'Hide' : 'Changes'}</button>
                    )}
                </td>
            </tr>
            {open && log.changes && (
                <tr className="bg-ink-2">
                    <td colSpan={6} className="px-4 py-3">
                        <pre className="num max-h-72 overflow-auto whitespace-pre-wrap break-all text-xs text-paper-2" data-lenis-prevent>{JSON.stringify(log.changes, null, 2)}</pre>
                    </td>
                </tr>
            )}
        </>
    );
}

function Refunds({ bookings }: { bookings: Booking[] }) {
    const [target, setTarget] = useState<string | null>(null);
    const form = useForm({ reason: '' });

    const submit = (number: string) => form.post(route('admin.bookings.refund', number), {
        preserveScroll: true,
        onSuccess: () => { setTarget(null); form.reset(); },
    });

    return (
        <section className="space-y-3" aria-label="Bookings and refunds">
            <p className="max-w-3xl text-sm text-mute">
                Cancelling a confirmed booking frees its seats and returns any coupon use, gift card balance and loyalty points. If it was paid, it is marked refunded and the customer is emailed.
            </p>
            {bookings.map((booking) => (
                <article key={booking.number} className="panel p-5">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div className="min-w-0">
                            <p className="num text-xs text-mute">{booking.number} · {booking.starts ?? 'No show time'}</p>
                            <p className="headline mt-1 truncate text-2xl">{booking.film ?? 'Unknown film'}</p>
                            <p className="mt-1 text-sm text-paper-2">{booking.customer ?? 'Guest'}</p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="num text-lg">{money(booking.total)}</span>
                            {booking.gift_card > 0 && <span className="tag">Gift card {money(booking.gift_card)}</span>}
                            {booking.points > 0 && <span className="tag">{booking.points} pts</span>}
                            <Status value={booking.payment} />
                            <Status value={booking.status} />
                            {booking.refundable && target !== booking.number && (
                                <button type="button" onClick={() => { setTarget(booking.number); form.reset(); form.clearErrors(); }} className="btn btn-ghost btn-sm">
                                    <Icon name="refresh" size={14} /> {booking.payment === 'paid' ? 'Refund' : 'Cancel'}
                                </button>
                            )}
                        </div>
                    </div>
                    {target === booking.number && (
                        <form onSubmit={(event) => { event.preventDefault(); submit(booking.number); }} className="mt-5 grid gap-3 border-t border-line pt-5 sm:grid-cols-[1fr_auto] sm:items-end">
                            <Field label="Reason (sent to the customer)" value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)}
                                error={form.errors.reason} required minLength={4} maxLength={200} placeholder="Screen fault, show cancelled" autoFocus />
                            <div className="flex gap-2">
                                <button type="button" onClick={() => setTarget(null)} className="btn btn-ghost">Keep booking</button>
                                <button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary">{form.processing ? 'Working…' : booking.payment === 'paid' ? 'Refund and cancel' : 'Cancel booking'}</button>
                            </div>
                        </form>
                    )}
                </article>
            ))}
        </section>
    );
}

function Status({ value }: { value: string }) {
    const tone = ['paid', 'confirmed'].includes(value) ? 'tag-mint' : ['refunded', 'cancelled', 'failed'].includes(value) ? 'tag-signal' : 'tag-volt';
    return <span className={cn('tag capitalize', tone)}>{value}</span>;
}

function GiftCards({ cards }: { cards: GiftCard[] }) {
    const form = useForm({ amount: '2000', recipient_email: '', message: '', months: '12' });

    return (
        <section className="grid gap-8 lg:grid-cols-12" aria-label="Gift cards">
            <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.gift-cards.store'), { preserveScroll: true, onSuccess: () => form.reset('recipient_email', 'message') }); }}
                className="panel space-y-4 p-6 lg:col-span-4" noValidate>
                <p className="label label-accent">Issue a card</p>
                <Field label="Amount (PKR)" placeholder="e.g. 2500" type="number" min={100} max={100000} step={100} value={form.data.amount} onChange={(event) => form.setData('amount', event.target.value)} error={form.errors.amount} required />
                <Field label="Valid for (months)" placeholder="e.g. 12" type="number" min={1} max={36} value={form.data.months} onChange={(event) => form.setData('months', event.target.value)} error={form.errors.months} required />
                <Field label="Recipient email" placeholder="name@example.com" type="email" value={form.data.recipient_email} onChange={(event) => form.setData('recipient_email', event.target.value)} error={form.errors.recipient_email} hint="Optional. The code is emailed to them." />
                <TextArea label="Message" placeholder="e.g. Happy birthday! Enjoy a film on us." value={form.data.message} onChange={(event) => form.setData('message', event.target.value)} error={form.errors.message} maxLength={200} rows={3} />
                <button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary w-full">{form.processing ? 'Issuing…' : 'Issue gift card'}</button>
            </form>

            <div className="lg:col-span-8">
                <ul className="grid gap-px border border-line bg-line sm:grid-cols-2">
                    {cards.map((card) => (
                        <li key={card.id} className="bg-ink p-5">
                            <div className="flex items-start justify-between gap-3">
                                <p className="num text-lg tracking-wider">{card.code}</p>
                                <span className={cn('tag', card.active ? 'tag-mint' : 'tag-signal')}>{card.active ? 'Active' : 'Off'}</span>
                            </div>
                            <p className="mt-3 text-sm"><span className="num text-2xl">{money(card.balance)}</span> <span className="text-mute">of {money(card.initial)}</span></p>
                            <p className="mt-1 text-xs text-mute">{card.recipient ?? 'No recipient'} · {card.expires ? `until ${card.expires}` : 'no expiry'}</p>
                            {card.active && (
                                <button type="button" className="link mt-3 text-xs text-signal"
                                    onClick={() => router.delete(route('admin.gift-cards.destroy', card.id), { preserveScroll: true })}>Switch off</button>
                            )}
                        </li>
                    ))}
                    {cards.length === 0 && <li className="bg-ink p-8 text-sm text-mute sm:col-span-2">No gift cards yet.</li>}
                </ul>
            </div>
        </section>
    );
}
