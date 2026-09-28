import { Link, router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'motion/react';
import { lazy, Suspense, useMemo, useState } from 'react';
import Icon from '@/components/Icon';
import Poster from '@/components/Poster';
import { Alert, Breadcrumbs } from '@/components/ui';
import { SplitHeading } from '@/components/motion';
import { useT } from '@/lib/i18n';
import { cn, money, route, useShared } from '@/lib/utils';
import type { MovieCard } from '@/types';

const CinemaHall = lazy(() => import('@/three/CinemaHall'));

type Seat = { id: number; number: number; label: string; tier: string; tier_name: string; status: string; available: boolean; adult: number; kid: number | null; price_label: string };
type Tier = { rows: string[]; tier: string; category: string; benefits: string | null; price: number; sale_price: number | null; kids_price: number | null; kids_sale_price: number | null; available: number };

interface Props {
    movie: { id: number; title: string; slug: string; certificate: string; card: MovieCard };
    show: { id: number; theater: string; theater_slug: string; screen: string; format: string; day_label: string; time: string; starts: string; ends: string };
    onSale: boolean;
    aisles: number[];
    rows: { label: string; seats: Seat[] }[];
    seatSummary: { available: number; booked: number; reserved: number };
    pricingTiers: Tier[];
    otherTimes: { id: number; time: string; format: string }[];
    maxSeats: number;
    holdMinutes: number;
}

/**
 * The best block of `count` free seats side by side in one row, never across
 * an aisle. Scores favour rows about 60% of the way back and blocks centred on
 * the screen, the way projectionists and sound engineers sit.
 */
function bestSeats(rows: Props['rows'], aisles: number[], count: number): Seat[] | null {
    const ideal = (rows.length - 1) * 0.6;
    let best: { seats: Seat[]; score: number } | null = null;

    rows.forEach((row, rowIndex) => {
        const numbers = row.seats.map((seat) => seat.number);
        const middle = (Math.min(...numbers) + Math.max(...numbers)) / 2;
        const width = Math.max(1, Math.max(...numbers) - Math.min(...numbers));

        for (let start = 0; start + count <= row.seats.length; start++) {
            const block = row.seats.slice(start, start + count);
            const together = block.every((seat, index) => seat.available && (index === 0 || (seat.number === block[index - 1].number + 1 && !aisles.includes(block[index - 1].number))));
            if (!together) continue;

            const centre = (block[0].number + block[block.length - 1].number) / 2;
            const score = Math.abs(rowIndex - ideal) / Math.max(1, rows.length) + (Math.abs(centre - middle) / width) * 1.4;
            if (!best || score < best.score) best = { seats: block, score };
        }
    });

    return best ? (best as { seats: Seat[] }).seats : null;
}

export default function Seats({ movie, show, onSale, aisles, rows, seatSummary, pricingTiers, otherTimes, maxSeats, holdMinutes }: Props) {
    const t = useT();
    const { auth, errors } = useShared();
    const [selected, setSelected] = useState<Seat[]>([]);
    const [ticketType, setTicketType] = useState<'adult' | 'kid'>('adult');
    const [message, setMessage] = useState('');
    const [processing, setProcessing] = useState(false);
    const [view, setView] = useState<'map' | 'hall'>('map');
    const [camera, setCamera] = useState<'overview' | 'seat'>('overview');
    const [focus, setFocus] = useState<number | null>(null);
    const [hoverSeat, setHoverSeat] = useState<Seat | null>(null);
    const seatById = useMemo(() => new Map(rows.flatMap((row) => row.seats).map((seat) => [seat.id, seat])), [rows]);
    const lastPicked = selected[selected.length - 1];
    const focusSeat = focus !== null ? seatById.get(focus) : undefined;

    const priceFor = (seat: Seat) => (ticketType === 'kid' && seat.kid !== null ? seat.kid : seat.adult);
    const total = useMemo(() => selected.reduce((sum, seat) => sum + priceFor(seat), 0), [selected, ticketType]); // eslint-disable-line react-hooks/exhaustive-deps

    const toggle = (seat: Seat) => {
        if (selected.some((item) => item.id === seat.id)) {
            setSelected(selected.filter((item) => item.id !== seat.id));
            if (focus === seat.id) setCamera('overview');
            setMessage('');
            return;
        }
        if (selected.length >= maxSeats) {
            setMessage(`You can select up to ${maxSeats} seats in one booking.`);
            return;
        }
        setSelected([...selected, seat]);
        setFocus(seat.id);
        setMessage('');
    };

    const hold = () => {
        if (!selected.length) return;
        router.post(route('user.cart'), { show_id: show.id, ticket_type: ticketType, seats: selected.map((seat) => seat.id) }, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: () => setSelected([]),
        });
    };

    const recommend = (count: number) => {
        const block = bestSeats(rows, aisles, count);
        if (!block) {
            setMessage(t('No :count seats together are left for this show. Try a smaller group or another time.', { count }));
            return;
        }
        setSelected(block);
        setFocus(block[Math.floor(block.length / 2)].id);
        setMessage('');
    };

    const lastRow = rows[rows.length - 1]?.label;
    const serverError = errors.seats || errors.show_id || errors.cart;

    return (
        <>
            <section className="pb-8 pt-[calc(var(--header)+2.5rem)]">
                <div className="shell">
                    <Breadcrumbs className="mb-8" />
                    <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="label label-accent">{show.theater} · {show.screen} · {show.format}</p>
                            <SplitHeading as="h1" text={movie.title} className="mt-4 text-[clamp(3.5rem,9vw,8rem)]" />
                            <p className="lede mt-3">{show.day_label} at <span className="num text-paper">{show.time}</span></p>
                        </div>
                        {otherTimes.length > 1 && (
                            <nav aria-label="Other times today" className="flex flex-wrap gap-1">
                                {otherTimes.map((other) => (
                                    <Link key={other.id} href={route('movies.seats', { slug: movie.slug, show: other.id })} aria-current={other.id === show.id ? 'page' : undefined} className="chip">
                                        <span className="num">{other.time}</span> <span className="opacity-60">{other.format}</span>
                                    </Link>
                                ))}
                            </nav>
                        )}
                    </div>
                </div>
            </section>

            <section className="pb-28 xl:pb-10">
                <div className="shell grid gap-10 xl:grid-cols-12">
                    <div className="xl:col-span-8">
                        {serverError && <div className="mb-6"><Alert tone="error">{serverError}</Alert></div>}
                        {!onSale && <div className="mb-6"><Alert icon="clock">This show has started or is no longer on sale. Choose another time from the film page.</Alert></div>}

                        {onSale && seatSummary.available > 0 && (
                            <div className="panel mb-6 flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-start gap-3">
                                    <Icon name="star" size={18} className="mt-0.5 shrink-0 text-accent" />
                                    <div>
                                        <p className="font-semibold">{t('Let us pick the best seats')}</p>
                                        <p className="text-xs text-mute">{t('Side by side, near the centre, about two thirds of the way back.')}</p>
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-1" aria-label={t('Recommend seats together')}>
                                    {Array.from({ length: maxSeats }, (_, index) => index + 1).map((count) => (
                                        <button key={count} type="button" onClick={() => recommend(count)} className="chip">
                                            {count === 1 ? t('Best seat') : t('Best :count together', { count })}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Map or 3D hall ---------------------------------------------- */}
                        <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <div className="relative grid grid-cols-2 border border-line-2 p-1" role="tablist" aria-label="Seat view">
                                {([['map', 'Seat map', 'grid'], ['hall', '3D hall', 'screen']] as const).map(([key, label, icon]) => (
                                    <button key={key} type="button" role="tab" aria-selected={view === key} onClick={() => setView(key)}
                                        className={cn('relative z-10 flex items-center justify-center gap-2 px-4 py-2 text-[11px] font-bold uppercase tracking-[.08em] transition-colors [font-stretch:115%]', view === key ? 'text-noir' : 'text-mute hover:text-paper')}>
                                        {view === key && <motion.span layoutId="seat-view" className="absolute inset-0 -z-10 bg-volt" transition={{ type: 'spring', stiffness: 400, damping: 34 }} />}
                                        <Icon name={icon} size={14} /> {label}
                                    </button>
                                ))}
                            </div>
                            {view === 'hall' && (
                                <div className="flex flex-wrap gap-1" aria-label="Camera">
                                    <button type="button" onClick={() => setCamera('overview')} aria-pressed={camera === 'overview'} className="chip">Overview</button>
                                    {selected.map((seat) => (
                                        <button key={seat.id} type="button" onClick={() => { setFocus(seat.id); setCamera('seat'); }} aria-pressed={camera === 'seat' && focus === seat.id} className="chip">
                                            <Icon name="eye" size={12} /> From {seat.label}
                                        </button>
                                    ))}
                                </div>
                            )}
                            {view === 'map' && lastPicked && (
                                <button type="button" onClick={() => { setFocus(lastPicked.id); setCamera('seat'); setView('hall'); }} className="btn btn-ghost btn-sm">
                                    <Icon name="eye" size={14} /> See the view from {lastPicked.label}
                                </button>
                            )}
                        </div>

                        {view === 'hall' && (
                            <div className="panel relative overflow-hidden">
                                <Suspense fallback={<div className="grid aspect-[16/10] place-items-center text-sm text-mute">Building the auditorium…</div>}>
                                    <CinemaHall className="aspect-[4/5] w-full touch-none sm:aspect-[16/10]" rows={rows} aisles={aisles}
                                        selected={selected.map((seat) => seat.id)} focus={focus} mode={camera}
                                        onToggle={(hallSeat) => { const seat = seatById.get(hallSeat.id); if (seat) toggle(seat); }}
                                        onHover={(hallSeat) => setHoverSeat(hallSeat ? seatById.get(hallSeat.id) ?? null : null)}
                                        screenImage={movie.card.hero_image_url} title={movie.title} palette={movie.card.palette} />
                                </Suspense>
                                <div className="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-4 p-4">
                                    <p className="label rounded-none bg-ink/70 px-2 py-1 backdrop-blur">
                                        {camera === 'seat' && focusSeat ? `Your view from ${focusSeat.label} · ${focusSeat.tier_name}` : 'Drag to look around · tap a seat to pick it'}
                                    </p>
                                    {hoverSeat && (
                                        <p className="num bg-volt px-2 py-1 text-xs font-semibold text-noir">{hoverSeat.label} · {hoverSeat.available ? hoverSeat.price_label : hoverSeat.status}</p>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className={cn('panel overflow-hidden p-5 sm:p-10', view === 'hall' && 'hidden')}>
                            {/* The screen: a curved, glowing arc. */}
                            <div className="relative mx-auto mb-12 max-w-2xl" aria-hidden="true">
                                <svg viewBox="0 0 600 70" className="w-full text-accent" fill="none">
                                    <defs>
                                        <linearGradient id="screen-glow" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0" stopColor="currentColor" stopOpacity=".45" />
                                            <stop offset="1" stopColor="currentColor" stopOpacity="0" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M10 40 Q300 -8 590 40 L570 70 Q300 30 30 70 Z" fill="url(#screen-glow)" />
                                    <path d="M10 40 Q300 -8 590 40" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                                </svg>
                                <p className="label -mt-3 text-center tracking-[.5em]">Screen</p>
                            </div>

                            <div className="overflow-x-auto pb-2" data-lenis-prevent>
                                <div className="mx-auto grid w-max gap-2 [perspective:1400px]" role="group" aria-label={`Seat map. Rows run from A at the front to ${lastRow} at the back.`}>
                                    <div className="grid gap-2 [transform:rotateX(14deg)] [transform-origin:50%_0]">
                                        {rows.map((row) => (
                                            <div key={row.label} className="flex items-center gap-2 sm:gap-3">
                                                <span className="num w-5 text-center text-[11px] text-dim">{row.label}</span>
                                                <div className="flex gap-1.5 sm:gap-2">
                                                    {row.seats.map((seat) => {
                                                        const isSelected = selected.some((item) => item.id === seat.id);
                                                        return (
                                                            <span key={seat.id} className="flex">
                                                                <button type="button" className="seat w-7 sm:w-8" data-tier={seat.tier} data-status={seat.status} disabled={!seat.available}
                                                                    onClick={() => seat.available && toggle(seat)} aria-pressed={seat.available ? isSelected : undefined}
                                                                    aria-label={`Seat ${seat.label}, ${seat.tier_name}, ${seat.price_label}${seat.available ? '' : `, ${seat.status}`}`}
                                                                    title={`${seat.label} · ${seat.tier_name} · ${seat.price_label}`}>
                                                                    {seat.number}
                                                                </button>
                                                                {aisles.includes(seat.number) && <span className="w-4 sm:w-6" aria-hidden="true" />}
                                                            </span>
                                                        );
                                                    })}
                                                </div>
                                                <span className="num w-5 text-center text-[11px] text-dim">{row.label}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <ul className="mt-10 flex flex-wrap justify-center gap-x-6 gap-y-3 text-xs text-mute" aria-label="Legend">
                                <li className="flex items-center gap-2"><span className="seat pointer-events-none w-4" data-tier="Gold" /> Classic</li>
                                <li className="flex items-center gap-2"><span className="seat pointer-events-none w-4" data-tier="Platinum" /> Prime</li>
                                <li className="flex items-center gap-2"><span className="seat pointer-events-none w-4" data-tier="Box" /> Recliner</li>
                                <li className="flex items-center gap-2"><span className="seat pointer-events-none w-4" aria-pressed="true" /> Your pick</li>
                                <li className="flex items-center gap-2"><button type="button" disabled className="seat pointer-events-none w-4" data-status="reserved" /> Held</li>
                                <li className="flex items-center gap-2"><button type="button" disabled className="seat pointer-events-none w-4" /> Sold</li>
                            </ul>
                            <p className="num mt-4 text-center text-[11px] text-mute">{seatSummary.available} available · {seatSummary.booked} sold · {seatSummary.reserved} held</p>
                        </div>

                        <div className="mt-10">
                            <p className="label">Row pricing for this show</p>
                            <div className="mt-4 overflow-x-auto border border-line" data-lenis-prevent>
                                <table className="w-full min-w-[34rem] text-left text-sm">
                                    <thead className="bg-ink-2">
                                        <tr className="label text-[10px]">
                                            <th className="px-5 py-3 font-semibold">Rows</th><th className="px-5 py-3 font-semibold">Tier</th>
                                            <th className="hidden px-5 py-3 font-semibold md:table-cell">What you get</th>
                                            <th className="px-5 py-3 text-right font-semibold">Adult</th><th className="px-5 py-3 text-right font-semibold">Child</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-line">
                                        {pricingTiers.map((tier) => (
                                            <tr key={tier.rows.join()}>
                                                <td className="num px-5 py-4 text-paper-2">{tier.rows.join(', ')}</td>
                                                <td className="px-5 py-4">{tier.tier}<span className="block text-xs text-mute">{tier.available} free</span></td>
                                                <td className="hidden max-w-xs px-5 py-4 text-mute md:table-cell">{tier.benefits}</td>
                                                <td className="num px-5 py-4 text-right">
                                                    {tier.sale_price !== null ? (
                                                        <><span className="text-signal">{money(tier.sale_price)}</span><s className="block text-xs text-dim">{money(tier.price)}</s></>
                                                    ) : money(tier.price)}
                                                </td>
                                                <td className="num px-5 py-4 text-right text-paper-2">{tier.kids_price !== null ? money(tier.kids_sale_price ?? tier.kids_price) : '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {/* Summary ---------------------------------------------------- */}
                    <aside className="xl:col-span-4">
                        <div className="ticket sticky top-24 p-7" style={{ ['--tear' as string]: '60%' }}>
                            <div className="grid grid-cols-[4.5rem_1fr] gap-4">
                                <Poster movie={movie.card} size="sm" meta={false} />
                                <div>
                                    <p className="label">Your selection</p>
                                    <p className="headline mt-2 text-2xl">{movie.title}</p>
                                    <p className="num mt-1 text-xs text-mute">{show.starts}</p>
                                </div>
                            </div>

                            <fieldset className="mt-6">
                                <legend className="sr-only">Ticket type</legend>
                                <div className="grid grid-cols-2 border border-line-2 p-1">
                                    {(['adult', 'kid'] as const).map((type) => (
                                        <button key={type} type="button" onClick={() => setTicketType(type)} aria-pressed={ticketType === type}
                                            className={cn('py-2.5 text-[11px] font-bold uppercase tracking-[.08em] transition [font-stretch:115%]', ticketType === type ? 'bg-paper text-ink' : 'text-mute hover:text-paper')}>
                                            {type === 'adult' ? 'Adult' : 'Child (3–12)'}
                                        </button>
                                    ))}
                                </div>
                                {ticketType === 'kid' && <p className="field-hint mt-2">Recliner rows have no child price and are charged as adult.</p>}
                            </fieldset>

                            <div className="mt-6 min-h-[5.5rem]">
                                {selected.length === 0 ? (
                                    <p className="text-sm text-mute">Tap up to {maxSeats} seats on the map. They are held for {holdMinutes} minutes while you check out.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        <AnimatePresence initial={false}>
                                            {selected.map((seat) => (
                                                <motion.li key={seat.id} initial={{ opacity: 0, x: -12 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 12 }} className="flex items-center justify-between text-sm">
                                                    <button type="button" onClick={() => toggle(seat)} className="group flex items-center gap-2 hover:text-signal" aria-label={`Remove seat ${seat.label}`}>
                                                        <span className="num grid h-7 w-10 place-items-center bg-volt text-[11px] font-bold text-noir group-hover:bg-signal">{seat.label}</span>
                                                        <span>{ticketType === 'kid' && seat.kid !== null ? 'Child' : 'Adult'}</span>
                                                    </button>
                                                    <span className="num text-paper-2">{money(priceFor(seat))}</span>
                                                </motion.li>
                                            ))}
                                        </AnimatePresence>
                                    </ul>
                                )}
                            </div>

                            {message && <p className="mt-3 text-xs text-signal" role="alert">{message}</p>}

                            <div className="perforation -mx-7 my-6" />

                            <div className="flex items-baseline justify-between">
                                <span className="text-sm text-mute">Total <span className="num text-dim">({selected.length}/{maxSeats})</span></span>
                                <span className="display text-5xl tabular">{money(total)}</span>
                            </div>

                            {auth.user ? (
                                <button type="button" onClick={hold} disabled={!selected.length || !onSale || processing} className="btn btn-primary btn-lg mt-6 w-full">
                                    {processing ? 'Holding seats…' : 'Hold seats'} <Icon name="arrow-right" size={18} className="arrow" />
                                </button>
                            ) : (
                                <>
                                    <Link href={route('user.login')} className="btn btn-primary btn-lg mt-6 w-full">Sign in to hold seats</Link>
                                    <p className="mt-3 text-center text-xs text-mute">You will come straight back to this seat map.</p>
                                </>
                            )}

                            <dl className="mt-8 space-y-3 border-t border-line pt-6 text-sm">
                                {[
                                    ['Cinema', <Link key="c" className="link" href={route('cinemas.show', show.theater_slug)}>{show.theater}</Link>],
                                    ['Screen', `${show.screen} · ${show.format}`],
                                    ['Starts', show.starts],
                                    ['Ends around', show.ends],
                                    ['Certificate', movie.certificate],
                                ].map(([label, value]) => (
                                    <div key={label as string} className="flex justify-between gap-4"><dt className="text-mute">{label}</dt><dd className="text-right">{value}</dd></div>
                                ))}
                            </dl>
                            <p className="mt-6 flex items-start gap-2 text-xs text-mute"><Icon name="shield" size={16} /> Seats are locked the moment you hold them. Nobody else can buy them until your hold ends.</p>
                        </div>
                    </aside>
                </div>
            </section>

            {/* Mobile: the total and the button stay within thumb reach. */}
            <AnimatePresence>
                {selected.length > 0 && (
                    <motion.div initial={{ y: '100%' }} animate={{ y: 0 }} exit={{ y: '100%' }} transition={{ duration: 0.45, ease: [0.16, 1, 0.3, 1] }}
                        className="glass fixed inset-x-0 bottom-0 z-[65] flex items-center justify-between gap-4 px-4 py-3 xl:hidden">
                        <div>
                            <p className="num text-xs text-mute">{selected.map((seat) => seat.label).join(' ')}</p>
                            <p className="display text-3xl">{money(total)}</p>
                        </div>
                        {auth.user ? (
                            <button type="button" onClick={hold} disabled={!onSale || processing} className="btn btn-primary">Hold seats <Icon name="arrow-right" size={16} /></button>
                        ) : (
                            <Link href={route('user.login')} className="btn btn-primary">Sign in</Link>
                        )}
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
}
