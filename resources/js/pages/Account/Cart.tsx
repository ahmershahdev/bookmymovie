import { Link, router } from '@inertiajs/react';
import { CheckoutSteps } from '@/components/account';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { Alert, Breadcrumbs, EmptyState, useCountdown } from '@/components/ui';
import { cn, money, plural, route, useShared } from '@/lib/utils';
import type { MovieCard } from '@/types';

export interface CartShow { id: number; movie: MovieCard; title: string; slug: string; when: string; short: string; theater: string; screen: string; format: string }
export interface CartItem { id: number; seat: string; type: string; ticket_type?: 'adult' | 'kid'; child_allowed?: boolean; tier: string; price: number }

interface Props {
    expiresAt: string | null;
    total: number;
    show: CartShow | null;
    items: CartItem[];
    maxSeats: number;
}

export default function Cart({ expiresAt, total, show, items, maxSeats }: Props) {
    const { errors, site } = useShared();
    const hold = useCountdown(expiresAt, () => router.reload());

    return (
        <>
            <section className="pb-10 pt-[calc(var(--header)+2.5rem)]">
                <div className="shell">
                    <Breadcrumbs className="mb-10" />
                    <CheckoutSteps step={2} />
                    <SplitHeading as="h1" text="Your seats" accent={['seats']} className="text-[clamp(3.5rem,10vw,8.5rem)]" />
                </div>
            </section>

            <div className="shell grid gap-10 lg:grid-cols-12">
                <div className="lg:col-span-8">
                    {(errors.cart || errors.seats) && <div className="mb-6"><Alert tone="error">{errors.cart || errors.seats}</Alert></div>}

                    {!show || items.length === 0 ? (
                        <EmptyState icon="bag" title="No seats on hold" action={<Link href={route('movies.index')} className="btn btn-primary">Find a showtime</Link>}>
                            Pick a film and a showtime, tap your seats and they will be held here for {site.hold_minutes} minutes.
                        </EmptyState>
                    ) : (
                        <>
                            <div className="alert alert-info mb-6 items-center justify-between" role="timer">
                                <span className="flex items-center gap-3"><Icon name="clock" size={18} /> Seats held for <strong className={cn('num text-base', hold.urgent && 'text-signal')}>{hold.label}</strong></span>
                                <span className="text-xs text-mute">They return to sale when the timer ends.</span>
                            </div>

                            <article className="panel overflow-hidden">
                                <div className="grid gap-6 p-7 sm:grid-cols-[7rem_1fr]">
                                    <Poster movie={show.movie} size="sm" />
                                    <div>
                                        <h2 className="display text-6xl">{show.title}</h2>
                                        <p className="num mt-2 text-paper-2">{show.when}</p>
                                        <p className="text-sm text-mute">{show.theater} · {show.screen} · {show.format}</p>
                                    </div>
                                </div>
                                <ul className="divide-y divide-line border-t border-line">
                                    {items.map((item) => (
                                        <li key={item.id} className="flex items-center justify-between gap-4 px-7 py-4">
                                            <div className="flex items-center gap-4">
                                                <span className="num grid h-10 w-12 place-items-center bg-volt text-sm font-bold text-noir">{item.seat}</span>
                                                <span className="text-sm"><span className="block">{item.type}</span><span className="text-mute">{item.tier}</span></span>
                                            </div>
                                            {item.child_allowed && (
                                                <div className="hidden grid-cols-2 border border-line-2 p-0.5 sm:grid" role="radiogroup" aria-label={`Ticket for seat ${item.seat}`}>
                                                    {(['adult', 'kid'] as const).map((type) => (
                                                        <button key={type} type="button" role="radio" aria-checked={item.ticket_type === type}
                                                            onClick={() => item.ticket_type !== type && router.patch(route('user.cart.item', item.id), { ticket_type: type }, { preserveScroll: true })}
                                                            className={cn('px-3 py-1.5 text-[10px] font-bold uppercase tracking-[.08em] transition [font-stretch:115%]', item.ticket_type === type ? 'bg-paper text-ink' : 'text-mute hover:text-paper')}>
                                                            {type === 'adult' ? 'Adult' : 'Child'}
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                            <div className="flex items-center gap-4">
                                                <span className="num">{money(item.price)}</span>
                                                <button type="button" onClick={() => router.delete(route('user.cart.remove', item.id), { preserveScroll: true })}
                                                    className="grid h-9 w-9 place-items-center border border-line-2 text-mute transition hover:border-signal hover:text-signal" aria-label={`Release seat ${item.seat}`}>
                                                    <Icon name="minus" size={14} />
                                                </button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                                <div className="flex flex-wrap items-center justify-between gap-3 border-t border-line px-7 py-5">
                                    {items.length < maxSeats ? (
                                        <Link href={route('movies.seats', { slug: show.slug, show: show.id })} className="btn btn-ghost btn-sm"><Icon name="plus" size={14} /> Add seats ({maxSeats - items.length} left)</Link>
                                    ) : (
                                        <span className="text-sm text-mute">Seat limit reached ({maxSeats} per booking).</span>
                                    )}
                                    <button type="button" onClick={() => router.delete(route('user.cart.clear'))} className="link text-sm text-mute hover:text-signal">Release all seats</button>
                                </div>
                            </article>
                        </>
                    )}
                </div>

                <aside className="lg:col-span-4">
                    <div className="panel sticky top-24 p-7">
                        <p className="label">Summary</p>
                        <dl className="mt-5 space-y-3 text-sm">
                            <div className="flex justify-between"><dt className="text-mute">{plural(items.length, 'seat')}</dt><dd className="num">{money(total)}</dd></div>
                            <div className="flex justify-between"><dt className="text-mute">Booking fee</dt><dd>None</dd></div>
                            <div className="flex justify-between"><dt className="text-mute">Coupon</dt><dd className="text-mute">Add at checkout</dd></div>
                            <div className="flex items-baseline justify-between border-t border-line pt-4"><dt>Due at counter</dt><dd className="display text-5xl tabular">{money(total)}</dd></div>
                        </dl>
                        {items.length > 0 ? (
                            <Link href={route('user.checkout')} className="btn btn-primary btn-lg mt-6 w-full">Continue <Icon name="arrow-right" size={18} className="arrow" /></Link>
                        ) : (
                            <span className="btn btn-primary btn-lg mt-6 w-full" aria-disabled="true">Continue</span>
                        )}
                        <ul className="mt-6 space-y-2 text-xs text-mute">
                            {['Pay at the counter, no card needed', 'Free cancellation until 2 hours before', 'Seats locked while on hold'].map((line) => (
                                <li key={line} className="flex gap-2"><Icon name="check" size={14} className="text-mint" /> {line}</li>
                            ))}
                        </ul>
                    </div>
                </aside>
            </div>
        </>
    );
}
