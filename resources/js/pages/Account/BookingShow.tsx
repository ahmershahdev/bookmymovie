import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { Alert, Breadcrumbs, Select } from '@/components/ui';
import { cn, money, route, useShared } from '@/lib/utils';
import type { BookingSummary } from '@/types';

interface Props {
    booking: BookingSummary & {
        certificate: string;
        format: string;
        screen: string;
        address: string;
        date: string;
        time: string;
        doors: string;
        tickets: { seat: string; type: string; price: number; code: string }[];
        pattern: boolean[];
        adults: number;
        kids: number;
        subtotal: number;
        discount: number;
        coupon: string | null;
        cancelled_at: string | null;
        paid_at: string | null;
        customer: { name: string; email: string; phone: string };
        booked_at: string | null;
        map_url: string;
        cancellable: boolean;
        cancel_until: string | null;
    };
}

export default function BookingShow({ booking }: Props) {
    const { errors } = useShared();
    const [confirm, setConfirm] = useState(false);
    const cancel = useForm({ reason: '' });

    return (
        <section className="pb-24 pt-[calc(var(--header)+2.5rem)]">
            <div className="shell">
                <Breadcrumbs className="mb-10" />
                {errors.booking && <div className="mb-8"><Alert tone="error">{errors.booking}</Alert></div>}

                <div className="grid gap-10 lg:grid-cols-12">
                    <article className={cn('ticket overflow-hidden lg:col-span-8', booking.cancelled && 'opacity-70')} style={{ ['--tear' as string]: '66%' }} aria-label="E-ticket">
                        <div className="grid gap-8 p-7 sm:grid-cols-[10rem_1fr] sm:p-10">
                            <Poster movie={booking.movie} size="sm" />
                            <div>
                                <div className="flex flex-wrap gap-1.5">
                                    <span className={cn('tag', booking.cancelled ? 'tag-signal' : booking.paid ? 'tag-mint' : 'tag-volt')}>{booking.cancelled ? 'Cancelled' : booking.paid ? 'Paid' : booking.method === 'cod' ? 'Pay at counter' : 'Awaiting payment'}</span>
                                    <span className="tag">{booking.certificate}</span>
                                    <span className="tag">{booking.format}</span>
                                </div>
                                <SplitHeading as="h1" text={booking.title} className="mt-5 text-[clamp(3.5rem,8vw,6.5rem)]" />
                                <dl className="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-3">
                                    <div><dt className="label">Date</dt><dd className="num mt-1.5 text-2xl">{booking.date}</dd></div>
                                    <div><dt className="label">Starts</dt><dd className="num mt-1.5 text-2xl">{booking.time}</dd></div>
                                    <div><dt className="label">Doors</dt><dd className="num mt-1.5 text-2xl">{booking.doors}</dd></div>
                                    <div className="col-span-2"><dt className="label">Cinema</dt><dd className="mt-1.5">{booking.theater}<span className="block text-sm text-mute">{booking.address}</span></dd></div>
                                    <div><dt className="label">Screen</dt><dd className="mt-1.5">{booking.screen}</dd></div>
                                </dl>
                            </div>
                        </div>

                        <div className="perforation" />

                        <div className="grid gap-8 p-7 sm:grid-cols-[1fr_auto] sm:p-10">
                            <div>
                                <p className="label">Seats & ticket codes</p>
                                <ul className="mt-4 grid gap-2 sm:grid-cols-2">
                                    {booking.tickets.map((ticket) => (
                                        <li key={ticket.code} className="flex items-center gap-4 border border-line p-3">
                                            <span className="num grid h-12 w-14 place-items-center bg-volt text-lg font-bold text-noir">{ticket.seat}</span>
                                            <span className="min-w-0 text-sm">
                                                <span className="block">{ticket.type} · <span className="num">{money(ticket.price)}</span></span>
                                                <span className="num block truncate text-[11px] text-mute">{ticket.code}</span>
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div className="flex flex-col items-center justify-center gap-3 sm:border-l sm:border-line sm:pl-8">
                                <div className="grid grid-cols-8 gap-[3px] bg-paper p-3" aria-hidden="true">
                                    {booking.pattern.map((on, index) => <span key={index} className={cn('h-3 w-3', on ? 'bg-ink' : 'bg-transparent')} />)}
                                </div>
                                <p className="num text-sm font-semibold tracking-[.14em]">{booking.number}</p>
                            </div>
                        </div>
                    </article>

                    <aside className="space-y-4 lg:col-span-4" data-print-hide>
                        <div className="panel p-7">
                            <p className="label">Payment</p>
                            <dl className="mt-5 space-y-3 text-sm">
                                <div className="flex justify-between"><dt className="text-mute">{booking.adults} adult{booking.kids ? `, ${booking.kids} child` : ''}</dt><dd className="num">{money(booking.subtotal)}</dd></div>
                                {booking.discount > 0 && <div className="flex justify-between"><dt className="text-mute">Coupon {booking.coupon}</dt><dd className="num text-mint">− {money(booking.discount)}</dd></div>}
                                <div className="flex justify-between"><dt className="text-mute">Booking fee</dt><dd>None</dd></div>
                                <div className="flex items-baseline justify-between border-t border-line pt-4"><dt>Total</dt><dd className="display text-5xl tabular">{money(booking.total)}</dd></div>
                            </dl>
                            <p className="mt-5 text-xs text-mute">
                                {booking.cancelled ? `Cancelled ${booking.cancelled_at}. Nothing is owed.` : booking.paid ? `Paid ${booking.paid_at} with ${booking.method_label}.` : booking.method === 'cod' ? 'Pay by cash or card at the box office at least 20 minutes before the show.' : `Awaiting payment with ${booking.method_label}. You can also pay at the counter.`}
                            </p>
                            {booking.can_pay_online && (
                                <a href={route('payments.start', booking.number)} className="btn btn-primary mt-5 w-full">Pay {money(booking.total)} with {booking.method_label} <Icon name="arrow-right" size={16} className="arrow" /></a>
                            )}
                        </div>

                        <div className="panel p-7">
                            <p className="label">Booked by</p>
                            <p className="mt-3">{booking.customer.name}</p>
                            <p className="text-sm text-mute">{booking.customer.email} · {booking.customer.phone}</p>
                            <p className="mt-4 text-xs text-mute">Booked {booking.booked_at}</p>
                        </div>

                        <div className="grid gap-2">
                            <Link href={route('user.tracking', booking.number)} className="btn btn-ghost">Track booking</Link>
                            <button type="button" onClick={() => window.print()} className="btn btn-ghost"><Icon name="print" size={16} /> Print ticket</button>
                            <a href={booking.map_url} target="_blank" rel="noopener" className="btn btn-ghost">Directions <Icon name="arrow-up-right" size={14} /></a>
                        </div>

                        {booking.cancellable && (
                            <form onSubmit={(event) => { event.preventDefault(); cancel.post(route('user.booking.cancel', booking.number), { preserveScroll: true }); }} className="panel p-7">
                                <p className="font-semibold">Plans changed?</p>
                                <p className="mt-1 text-sm text-mute">Cancel free until {booking.cancel_until}. Your seats go straight back on sale.</p>
                                {!confirm ? (
                                    <button type="button" onClick={() => setConfirm(true)} className="btn btn-danger btn-sm mt-5">Cancel booking</button>
                                ) : (
                                    <div className="mt-5 space-y-3">
                                        <Select label="Reason (optional)" value={cancel.data.reason} onChange={(event) => cancel.setData('reason', event.target.value)} error={cancel.errors.reason}
                                            options={[['', 'Prefer not to say'], ['Plans changed', 'Plans changed'], ['Booked the wrong show', 'Booked the wrong show'], ['Found better seats', 'Found better seats'], ['Other', 'Other']]} />
                                        <div className="flex gap-2">
                                            <button type="submit" disabled={cancel.processing} className="btn btn-danger btn-sm">Yes, cancel it</button>
                                            <button type="button" onClick={() => setConfirm(false)} className="btn btn-ghost btn-sm">Keep it</button>
                                        </div>
                                    </div>
                                )}
                            </form>
                        )}
                    </aside>
                </div>
            </div>
        </section>
    );
}
