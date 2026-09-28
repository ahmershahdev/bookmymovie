import { Link, router } from '@inertiajs/react';
import { AnimatePresence, motion } from 'motion/react';
import { lazy, Suspense, useMemo, useState } from 'react';
import Icon from '@/components/Icon';
import Poster from '@/components/Poster';
import PushToggle from '@/components/PushToggle';
import { Alert, Breadcrumbs } from '@/components/ui';
import { SplitHeading } from '@/components/motion';
import { useT } from '@/lib/i18n';
import { computeLayout, viewStats } from '@/three/hallLayout';
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
    waitlist: { joined: boolean; count: number };
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

export default function Seats({ movie, show, onSale, aisles, rows, seatSummary, pricingTiers, otherTimes, maxSeats, holdMinutes, waitlist }: Props) {
    const t = useT();
    const { auth, errors } = useShared();
    const [selected, setSelected] = useState<Seat[]>([]);
    // Each seat has its own ticket type, so a family can book adults and children together.
    const [types, setTypes] = useState<Record<number, 'adult' | 'kid'>>({});
    const [message, setMessage] = useState('');
    const [processing, setProcessing] = useState(false);
    const [view, setView] = useState<'map' | 'hall'>('map');
    const [camera, setCamera] = useState<'overview' | 'seat'>('overview');
    const [focus, setFocus] = useState<number | null>(null);
    const [hoverSeat, setHoverSeat] = useState<Seat | null>(null);
    const seatById = useMemo(() => new Map(rows.flatMap((row) => row.seats).map((seat) => [seat.id, seat])), [rows]);
    const lastPicked = selected[selected.length - 1];
    const focusSeat = focus !== null ? seatById.get(focus) : undefined;
    const layout = useMemo(() => computeLayout(rows, aisles), [rows, aisles]);
    const stats = useMemo(() => (focus !== null ? viewStats(layout, focus) : null), [layout, focus]);
    // Neighbours in the same row, for "try the seat next door" in seat view.
    const neighbour = (step: -1 | 1) => {
        const row = rows.find((candidate) => candidate.seats.some((seat) => seat.id === focus));
        if (!row) return undefined;
        const index = row.seats.findIndex((seat) => seat.id === focus);
        return row.seats[index + step];
    };
    const rowStep = (step: -1 | 1) => {
        const rowIndex = rows.findIndex((candidate) => candidate.seats.some((seat) => seat.id === focus));
        const current = focusSeat;
        const next = rows[rowIndex + step];
        if (!next || !current) return undefined;
        return next.seats.reduce((best, seat) => (Math.abs(seat.number - current.number) < Math.abs(best.number - current.number) ? seat : best), next.seats[0]);
    };
    const trailer = movie.card.trailers?.[0];

    const typeOf = (seat: Seat): 'adult' | 'kid' => (types[seat.id] === 'kid' && seat.kid !== null ? 'kid' : 'adult');
    const priceFor = (seat: Seat) => (typeOf(seat) === 'kid' ? (seat.kid as number) : seat.adult);
    const total = useMemo(() => selected.reduce((sum, seat) => sum + priceFor(seat), 0), [selected, types]); // eslint-disable-line react-hooks/exhaustive-deps
    const kids = selected.filter((seat) => typeOf(seat) === 'kid').length;
    const adults = selected.length - kids;
    const setType = (seat: Seat, type: 'adult' | 'kid') => setTypes((current) => ({ ...current, [seat.id]: type }));

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
        if (adults === 0) {
            setMessage('Children must be accompanied: make at least one seat an adult ticket.');
            return;
        }
        const ticketTypes = Object.fromEntries(selected.map((seat) => [seat.id, typeOf(seat)]));
        router.post(route('user.cart'), { show_id: show.id, seats: selected.map((seat) => seat.id), ticket_types: ticketTypes }, {
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

                        {onSale && seatSummary.available === 0 && <Waitlist showId={show.id} maxSeats={maxSeats} waitlist={waitlist} signedIn={Boolean(auth.user)} />}

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
                                    {selected.length === 0 && focus === null && <span className="text-xs text-mute">Tap a seat, then “From …” to sit in it{trailer ? ' and watch the trailer' : ''}.</span>}
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
                                        screenImage={movie.card.hero_image_url} trailer={trailer ? { webm: trailer.webm, mp4: trailer.src } : undefined} title={movie.title} palette={movie.card.palette} />
                                </Suspense>
                                <div className="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-4 p-4">
                                    <p className="label rounded-none bg-ink/70 px-2 py-1 backdrop-blur">
                                        {camera === 'seat' && focusSeat ? `Your view from ${focusSeat.label} · ${focusSeat.tier_name}` : 'Drag to look around · tap a seat to pick it'}
                                    </p>
                                    {hoverSeat && (
                                        <p className="num bg-volt px-2 py-1 text-xs font-semibold text-noir">{hoverSeat.label} · {hoverSeat.available ? hoverSeat.price_label : hoverSeat.status}</p>
                                    )}
                                </div>
                                {camera === 'seat' && focusSeat && stats && (
                                    <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="absolute inset-x-3 bottom-3 border border-line-2 bg-ink/80 p-4 backdrop-blur-md sm:inset-x-auto sm:left-4 sm:w-80">
                                        <div className="flex items-center justify-between gap-3">
                                            <p className="label">View from {focusSeat.label}</p>
                                            <p className="flex items-baseline gap-1.5"><span className="display text-3xl text-accent">{stats.score}</span><span className="text-xs font-semibold uppercase">{stats.verdict}</span></p>
                                        </div>
                                        <div className="mt-2 h-1 bg-line"><motion.div className="h-full bg-volt" initial={{ width: 0 }} animate={{ width: `${stats.score}%` }} transition={{ duration: 0.8 }} /></div>
                                        <dl className="num mt-3 grid grid-cols-3 gap-2 text-center text-[11px]">
                                            <div><dt className="text-mute">Distance</dt><dd className="text-paper">{stats.distance.toFixed(1)} m</dd></div>
                                            <div><dt className="text-mute">Screen fills</dt><dd className="text-paper">{Math.round(stats.fill)}°</dd></div>
                                            <div><dt className="text-mute">Off-centre</dt><dd className="text-paper">{Math.round(stats.offAxis)}°</dd></div>
                                        </dl>
                                        <ul className="mt-3 space-y-1 text-xs text-paper-2">{stats.notes.map((note) => <li key={note}>· {note}</li>)}</ul>
                                        <div className="mt-3 grid grid-cols-4 gap-1" aria-label="Try another seat">
                                            {([['←', neighbour(-1)], ['→', neighbour(1)], ['Closer', rowStep(-1)], ['Back', rowStep(1)]] as const).map(([label, seat]) => (
                                                <button key={label} type="button" disabled={!seat} onClick={() => seat && setFocus(seat.id)} title={seat ? `View from ${seat.label}` : undefined}
                                                    className="border border-line-2 py-1.5 text-[10px] font-bold uppercase tracking-[.06em] text-paper transition hover:border-accent hover:text-accent disabled:opacity-30">
                                                    {label}{seat && <span className="num block text-[9px] font-normal text-mute">{seat.label}</span>}
                                                </button>
                                            ))}
                                        </div>
                                        {!selected.some((seat) => seat.id === focusSeat.id) && focusSeat.available && (
                                            <button type="button" onClick={() => toggle(focusSeat)} className="btn btn-primary btn-sm mt-3 w-full">Pick {focusSeat.label} · {focusSeat.price_label}</button>
                                        )}
                                    </motion.div>
                                )}
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

                            <div className="mt-6 min-h-[5.5rem]">
                                {selected.length === 0 ? (
                                    <p className="text-sm text-mute">Tap up to {maxSeats} seats on the map, then choose Adult or Child for each one. Seats are held for {holdMinutes} minutes while you check out.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        <AnimatePresence initial={false}>
                                            {selected.map((seat) => (
                                                <motion.li key={seat.id} initial={{ opacity: 0, x: -12 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: 12 }} className="flex items-center justify-between gap-3 text-sm">
                                                    <button type="button" onClick={() => toggle(seat)} className="group shrink-0" aria-label={`Remove seat ${seat.label}`} title="Remove seat">
                                                        <span className="num grid h-8 w-11 place-items-center bg-volt text-[11px] font-bold text-noir transition group-hover:bg-signal">{seat.label}</span>
                                                    </button>
                                                    <div className="grid flex-1 grid-cols-2 border border-line-2 p-0.5" role="radiogroup" aria-label={`Ticket for seat ${seat.label}`}>
                                                        {(['adult', 'kid'] as const).map((type) => {
                                                            const unavailable = type === 'kid' && seat.kid === null;
                                                            return (
                                                                <button key={type} type="button" role="radio" aria-checked={typeOf(seat) === type} disabled={unavailable} onClick={() => setType(seat, type)}
                                                                    title={unavailable ? 'Recliner seats have no child price' : undefined}
                                                                    className={cn('py-1.5 text-[10px] font-bold uppercase tracking-[.08em] transition [font-stretch:115%] disabled:cursor-not-allowed disabled:opacity-30', typeOf(seat) === type ? 'bg-paper text-ink' : 'text-mute hover:text-paper')}>
                                                                    {type === 'adult' ? 'Adult' : 'Child'}
                                                                </button>
                                                            );
                                                        })}
                                                    </div>
                                                    <motion.span key={priceFor(seat)} initial={{ opacity: 0, y: -4 }} animate={{ opacity: 1, y: 0 }} className="num w-20 shrink-0 text-right text-paper-2">{money(priceFor(seat))}</motion.span>
                                                </motion.li>
                                            ))}
                                        </AnimatePresence>
                                    </ul>
                                )}
                            </div>

                            {selected.length > 0 && (
                                <p className="mt-3 text-xs text-mute">
                                    {adults} {adults === 1 ? 'adult' : 'adults'}{kids > 0 && <> · {kids} {kids === 1 ? 'child (3–12)' : 'children (3–12)'}</>}
                                    {kids > 0 && adults === 0 && <span className="block text-signal">Add at least one adult: children must be accompanied.</span>}
                                </p>
                            )}
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

/** Sold out: join a first-come waitlist and get told when seats come back. */
function Waitlist({ showId, maxSeats, waitlist, signedIn }: { showId: number; maxSeats: number; waitlist: Props['waitlist']; signedIn: boolean }) {
    const [seats, setSeats] = useState(2);

    return (
        <div className="panel mb-6 border-signal/40 p-5 sm:p-6">
            <p className="label text-signal">Sold out</p>
            <p className="headline mt-2 text-2xl">{waitlist.joined ? 'You are on the waitlist' : 'Join the waitlist'}</p>
            <p className="mt-2 text-sm text-paper-2">
                Seats come back when someone cancels or a hold runs out. We alert the waitlist in the order people joined{waitlist.count > 0 ? `; ${waitlist.count} ${waitlist.count === 1 ? 'person is' : 'people are'} waiting` : ''}.
            </p>
            {signedIn ? (
                <div className="mt-5 flex flex-wrap items-center gap-3">
                    {waitlist.joined ? (
                        <button type="button" className="btn btn-ghost" onClick={() => router.delete(route('waitlist.leave', showId), { preserveScroll: true })}>Leave waitlist</button>
                    ) : (
                        <>
                            <div className="flex border border-line-2" role="radiogroup" aria-label="Seats needed">
                                {Array.from({ length: maxSeats }, (_, index) => index + 1).map((count) => (
                                    <button key={count} type="button" role="radio" aria-checked={seats === count} onClick={() => setSeats(count)}
                                        className={cn('num h-11 w-11 text-sm transition', seats === count ? 'bg-paper text-ink' : 'text-mute hover:text-paper')}>{count}</button>
                                ))}
                            </div>
                            <button type="button" className="btn btn-primary" onClick={() => router.post(route('waitlist.join', showId), { seats }, { preserveScroll: true })}>
                                <Icon name="megaphone" size={16} /> Notify me
                            </button>
                        </>
                    )}
                    <PushToggle className="ms-auto" />
                </div>
            ) : (
                <Link href={route('user.login')} className="btn btn-primary mt-5">Sign in to join the waitlist</Link>
            )}
        </div>
    );
}
