import { Link, router, useForm } from '@inertiajs/react';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import { useEffect, useState } from 'react';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import QrCode from '@/components/QrCode';
import Tilt from '@/components/Tilt';
import { Alert, Breadcrumbs, Select } from '@/components/ui';
import { useLocale, useT } from '@/lib/i18n';
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
        addons: { name: string; name_ur: string | null; quantity: number; total: number }[];
        addons_total: number;
        gift_card_amount: number;
        points_redeemed: number;
        points_earned: number;
        qr: string;
        wallet: { apple: string | null; google: string | null };
        admitted: boolean;
        split: { allowed: boolean; card: boolean; max: number; shares: Share[] };
    };
}

type Share = { id: number; label: string; amount: number; status: 'pending' | 'paid' | 'cancelled'; host: boolean; payer: string | null; url: string; method: string | null };

/** A ragged, torn-paper edge along the top or bottom of a panel. */
const tornEdge = (side: 'top' | 'bottom') => {
    const teeth = Array.from({ length: 41 }, (_, index) => `${index * 2.5}% ${index % 2 ? (side === 'top' ? '7px' : 'calc(100% - 7px)') : (side === 'top' ? '0' : '100%')}`);
    return side === 'top' ? `polygon(${teeth.join(',')}, 100% 100%, 0 100%)` : `polygon(0 0, 100% 0, ${teeth.reverse().join(',')})`;
};

export default function BookingShow({ booking }: Props) {
    const { errors } = useShared();
    const t = useT();
    const locale = useLocale();
    const [confirm, setConfirm] = useState(false);
    const cancel = useForm({ reason: '' });
    const reduce = useReducedMotion();
    // The tear plays once per ticket after it is scanned, then stays torn.
    const [tearKey, setTearKey] = useState(0);
    const [animateTear, setAnimateTear] = useState(false);
    useEffect(() => {
        if (!booking.admitted) return;
        const key = `bmm-torn-${booking.number}`;
        try {
            if (!localStorage.getItem(key)) { setAnimateTear(true); localStorage.setItem(key, '1'); }
        } catch { setAnimateTear(false); }
    }, [booking.admitted, booking.number]);
    const torn = booking.admitted && !booking.cancelled;

    return (
        <section className="pb-24 pt-[calc(var(--header)+2.5rem)]">
            <div className="shell">
                <Breadcrumbs className="mb-10" />
                {errors.booking && <div className="mb-8"><Alert tone="error">{errors.booking}</Alert></div>}

                <div className="grid gap-10 lg:grid-cols-12">
                    <div className="lg:col-span-8">
                    <Tilt max={torn ? 4 : 6} scale={1.01} depth={1600}>
                    <article className={cn(torn ? 'relative' : 'ticket overflow-hidden', booking.cancelled && 'opacity-70')} style={{ ['--tear' as string]: '66%' }} aria-label="E-ticket">
                        <div className={cn(torn && 'border border-b-0 border-line bg-ink-2')} style={torn ? { clipPath: tornEdge('bottom'), paddingBottom: 7 } : undefined}>
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
                        </div>

                        {!torn && <div className="perforation" />}

                        <motion.div key={tearKey} className={cn(torn && 'border border-t-0 border-line bg-ink-2 shadow-2xl shadow-black/40')}
                            style={torn ? { clipPath: tornEdge('top'), paddingTop: 7, transformOrigin: '8% 0%' } : undefined}
                            initial={torn && animateTear && !reduce ? { rotate: 0, y: 0, x: 0 } : false}
                            animate={torn ? { rotate: 2.5, y: 22, x: 10 } : { rotate: 0, y: 0, x: 0 }}
                            transition={{ duration: 1.1, ease: [0.34, 1.56, 0.64, 1], delay: animateTear ? 0.6 : 0 }}>
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
                                {booking.cancelled ? (
                                    <div className="grid h-[184px] w-[184px] place-items-center border border-dashed border-signal p-4 text-center text-sm text-signal">{t('Cancelled: this ticket no longer admits')}</div>
                                ) : (
                                    <QrCode value={booking.qr} label={t('Ticket QR code for :number', { number: booking.number })} />
                                )}
                                <p className="num text-sm font-semibold tracking-[.14em]">{booking.number}</p>
                                <p className="label text-[9px]">{torn ? 'Stub torn at the door' : t('Scan at the door')}</p>
                            </div>
                        </div>
                        </motion.div>
                        <AnimatePresence>
                            {torn && (
                                <motion.p initial={{ opacity: 0, scale: 1.6, rotate: -14 }} animate={{ opacity: 1, scale: 1, rotate: -8 }} transition={{ delay: animateTear ? 1.4 : 0, type: 'spring', stiffness: 260, damping: 14 }}
                                    className="pointer-events-none absolute right-6 top-6 z-30 border-2 border-mint px-3 py-1 text-sm font-extrabold uppercase tracking-[.2em] text-mint [font-stretch:125%]">Admitted</motion.p>
                            )}
                        </AnimatePresence>
                    </article>
                    </Tilt>
                    {torn && (
                        <button type="button" data-print-hide onClick={() => { setAnimateTear(true); setTearKey((key) => key + 1); }} className="mt-10 text-xs text-mute hover:text-paper">
                            <Icon name="refresh" size={12} className="me-1 inline" /> Replay the tear
                        </button>
                    )}
                    </div>

                    <aside className="space-y-4 lg:col-span-4" data-print-hide>
                        <div className="panel p-7">
                            <p className="label">Payment</p>
                            <dl className="mt-5 space-y-3 text-sm">
                                <div className="flex justify-between"><dt className="text-mute">{booking.adults} adult{booking.kids ? `, ${booking.kids} child` : ''}</dt><dd className="num">{money(booking.subtotal)}</dd></div>
                                {booking.discount > 0 && <div className="flex justify-between"><dt className="text-mute">{t('Coupon')} {booking.coupon}</dt><dd className="num text-mint">− {money(booking.discount)}</dd></div>}
                                {booking.addons.map((addon) => (
                                    <div key={addon.name} className="flex justify-between"><dt className="text-mute"><span className="num">{addon.quantity}×</span> {locale === 'ur' && addon.name_ur ? addon.name_ur : addon.name}</dt><dd className="num">{money(addon.total)}</dd></div>
                                ))}
                                {booking.gift_card_amount > 0 && <div className="flex justify-between"><dt className="text-mute">{t('Gift card')}</dt><dd className="num text-mint">− {money(booking.gift_card_amount)}</dd></div>}
                                {booking.points_redeemed > 0 && <div className="flex justify-between"><dt className="text-mute">{t('Loyalty points')}</dt><dd className="num text-mint">− {money(booking.points_redeemed)}</dd></div>}
                                <div className="flex justify-between"><dt className="text-mute">{t('Booking fee')}</dt><dd>{t('None')}</dd></div>
                                <div className="flex items-baseline justify-between border-t border-line pt-4"><dt>Total</dt><dd className="display text-5xl tabular">{money(booking.total)}</dd></div>
                            </dl>
                            <p className="mt-5 text-xs text-mute">
                                {booking.cancelled ? `Cancelled ${booking.cancelled_at}. Nothing is owed.` : booking.paid ? `Paid ${booking.paid_at} with ${booking.method_label}.` : booking.method === 'cod' ? 'Pay by cash or card at the box office at least 20 minutes before the show.' : `Awaiting payment with ${booking.method_label}. You can also pay at the counter.`}
                            </p>
                            {booking.can_pay_online && (
                                <a href={route('payments.start', booking.number)} className="btn btn-primary mt-5 w-full">Pay {money(booking.total)} with {booking.method_label} <Icon name="arrow-right" size={16} className="arrow" /></a>
                            )}
                            {booking.points_earned > 0 && !booking.cancelled && (
                                <p className="mt-4 flex items-center gap-2 border-t border-line pt-4 text-xs text-paper-2"><Icon name="star" size={14} className="text-accent" /> {t('You earned :points loyalty points with this booking.', { points: booking.points_earned })}</p>
                            )}
                        </div>

                        {(booking.split.allowed || booking.split.shares.length > 0) && !booking.cancelled && <SplitPanel number={booking.number} split={booking.split} total={booking.total} />}

                        {!booking.cancelled && (
                            <div className="panel p-7">
                                <p className="label">{t('Keep it handy')}</p>
                                <p className="mt-3 text-sm text-mute">{t('This ticket is saved on this device and opens even without internet.')}</p>
                                {(booking.wallet.apple || booking.wallet.google) && (
                                    <div className="mt-5 grid gap-2">
                                        {booking.wallet.apple && (
                                            <a href={booking.wallet.apple} className="btn btn-light w-full"><Icon name="wallet" size={16} /> {t('Add to Apple Wallet')}</a>
                                        )}
                                        {booking.wallet.google && (
                                            <a href={booking.wallet.google} className="btn btn-light w-full"><Icon name="wallet" size={16} /> {t('Save to Google Wallet')}</a>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

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

/** Split the bill: equal shares, one private link per friend. */
function SplitPanel({ number, split, total }: { number: string; split: Props['booking']['split']; total: number }) {
    const { errors } = useShared();
    const [people, setPeople] = useState(Math.min(2, split.max));
    const [copied, setCopied] = useState<number | null>(null);
    const paid = split.shares.filter((share) => share.status === 'paid');
    const anyPaid = paid.length > 0;
    const copy = (share: Share) => {
        navigator.clipboard?.writeText(share.url).then(() => { setCopied(share.id); window.setTimeout(() => setCopied(null), 1600); }).catch(() => undefined);
    };

    return (
        <div className="panel p-7">
            <p className="label label-accent flex items-center gap-2"><Icon name="user" size={13} /> Split the bill</p>
            {errors.split && <p className="field-error mt-3">{errors.split}</p>}
            {split.shares.length === 0 ? (
                <>
                    <p className="mt-3 text-sm text-paper-2">Going as a group? Split {money(total)} into equal shares and send each friend a private link. {split.card ? 'They can pay by card online or at the counter.' : 'They pay their share at the counter.'}</p>
                    <div className="mt-5 flex items-center gap-3">
                        <div className="flex border border-line-2" role="radiogroup" aria-label="How many people">
                            {Array.from({ length: split.max - 1 }, (_, index) => index + 2).map((count) => (
                                <button key={count} type="button" role="radio" aria-checked={people === count} onClick={() => setPeople(count)}
                                    className={cn('num h-10 w-10 text-sm transition', people === count ? 'bg-paper text-ink' : 'text-mute hover:text-paper')}>{count}</button>
                            ))}
                        </div>
                        <span className="num text-sm text-mute">≈ {money(Math.round((total / people) * 100) / 100)} each</span>
                    </div>
                    <button type="button" className="btn btn-primary mt-5 w-full" onClick={() => router.post(route('user.booking.split', number), { people }, { preserveScroll: true })}>Split {people} ways</button>
                </>
            ) : (
                <>
                    <div className="mt-4 flex items-center gap-3">
                        <div className="h-1.5 flex-1 bg-line"><motion.div className="h-full bg-mint" initial={false} animate={{ width: `${(paid.length / split.shares.length) * 100}%` }} /></div>
                        <span className="num text-xs text-mute">{paid.length}/{split.shares.length} paid</span>
                    </div>
                    <ul className="mt-4 space-y-2">
                        {split.shares.map((share) => (
                            <li key={share.id} className="flex items-center justify-between gap-3 border border-line p-3 text-sm">
                                <span className="min-w-0">
                                    <span className="block font-semibold">{share.host ? 'You' : share.payer ?? share.label}</span>
                                    <span className="num text-xs text-mute">{money(share.amount)} · <span className={cn(share.status === 'paid' ? 'text-mint' : share.status === 'cancelled' ? 'text-signal' : '')}>{share.status === 'paid' ? `paid${share.method ? ` (${share.method})` : ''}` : share.status}</span></span>
                                </span>
                                {!share.host && share.status === 'pending' && (
                                    <span className="flex shrink-0 gap-1">
                                        <button type="button" onClick={() => copy(share)} className="btn btn-ghost btn-sm">{copied === share.id ? 'Copied' : 'Copy link'}</button>
                                        <a href={`https://wa.me/?text=${encodeURIComponent(`Your share for our movie night: ${share.url}`)}`} target="_blank" rel="noopener" className="btn btn-ghost btn-icon btn-sm" aria-label="Send on WhatsApp"><Icon name="chat" size={14} /></a>
                                    </span>
                                )}
                                {share.host && share.status === 'pending' && <span className="text-xs text-mute">Pay yours with the booking</span>}
                            </li>
                        ))}
                    </ul>
                    {!anyPaid && <button type="button" className="mt-4 text-xs text-mute hover:text-signal" onClick={() => router.delete(route('user.booking.split.destroy', number), { preserveScroll: true })}>Remove the split</button>}
                    <p className="mt-4 text-xs text-mute">Anything still unpaid is settled at the counter before the show.</p>
                </>
            )}
        </div>
    );
}
