import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/components/Icon';
import AdminLayout from '@/layouts/AdminLayout';
import { cn, money, route } from '@/lib/utils';

type Day = { day: string; label: string; revenue: number; tickets: number; bookings: number };
type Film = { title: string; slug: string; sold: number; capacity: number; shows: number; rate: number };
type Cinema = { name: string; sold: number; capacity: number; rate: number };

interface Props {
    days: number;
    series: Day[];
    occupancy: Film[];
    cinemas: Cinema[];
    totals: { revenue: number; revenue_change: number | null; tickets: number; bookings: number; average_ticket: number; occupancy: number };
}

/** Rounds a max up to a tidy axis ceiling (1, 2, 2.5 or 5 × 10ⁿ). */
function niceCeil(value: number): number {
    if (value <= 0) return 1;
    const power = 10 ** Math.floor(Math.log10(value));
    const step = [1, 2, 2.5, 5, 10].find((candidate) => candidate * power >= value) ?? 10;
    return step * power;
}

const compact = (value: number) => (value >= 1_000_000 ? `${(value / 1_000_000).toFixed(1)}M` : value >= 1000 ? `${Math.round(value / 1000)}k` : String(Math.round(value)));

export default function AdminAnalytics({ days, series, occupancy, cinemas, totals }: Props) {
    const [table, setTable] = useState(false);

    return (
        <AdminLayout label="Numbers" title="Revenue & occupancy" lede="Money taken and seats filled. Cancelled bookings are excluded; revenue includes gift card spend."
            actions={
                <div className="flex gap-1" role="group" aria-label="Date range">
                    {[7, 30, 90].map((range) => (
                        <button key={range} type="button" aria-pressed={days === range} onClick={() => router.get(route('admin.analytics'), { days: range }, { preserveScroll: true, preserveState: true })}
                            className={cn('chip', days === range && '!border-accent !text-accent')}>{range} days</button>
                    ))}
                </div>
            }>
            {/* Headline numbers: tiles, not charts. */}
            <div className="grid-lines grid-cols-2 lg:grid-cols-4">
                <Tile label={`Revenue, last ${days} days`} value={money(totals.revenue)}
                    note={totals.revenue_change === null ? 'No earlier period to compare' : `${totals.revenue_change >= 0 ? '▲' : '▼'} ${Math.abs(totals.revenue_change)}% vs the ${days} days before`}
                    tone={totals.revenue_change === null ? undefined : totals.revenue_change >= 0 ? 'good' : 'bad'} />
                <Tile label="Tickets sold" value={totals.tickets.toLocaleString('en-PK')} note={`${totals.bookings.toLocaleString('en-PK')} bookings`} />
                <Tile label="Average ticket" value={money(totals.average_ticket)} note="Revenue ÷ tickets" />
                <Tile label="Seats filled" value={`${totals.occupancy}%`} note="Shows in range and the next 14 days" />
            </div>

            <div className="flex justify-end">
                <button type="button" onClick={() => setTable(!table)} aria-pressed={table} className="btn btn-ghost btn-sm"><Icon name={table ? 'chart' : 'grid'} size={14} /> {table ? 'Show charts' : 'Show as tables'}</button>
            </div>

            <section className="border border-line p-5 sm:p-6" aria-labelledby="revenue-title">
                <h2 id="revenue-title" className="label label-accent">Revenue per day</h2>
                {table ? <DailyTable series={series} /> : <RevenueBars series={series} />}
            </section>

            <section className="grid gap-6 xl:grid-cols-2">
                <div className="border border-line p-5 sm:p-6">
                    <h2 className="label label-accent">Seats filled by film</h2>
                    {table ? <RateTable rows={occupancy.map((film) => ({ name: film.title, sold: film.sold, capacity: film.capacity, rate: film.rate }))} /> : <RateBars rows={occupancy.map((film) => ({ name: film.title, sold: film.sold, capacity: film.capacity, rate: film.rate, href: route('movies.show', film.slug) }))} />}
                </div>
                <div className="border border-line p-5 sm:p-6">
                    <h2 className="label label-accent">Seats filled by cinema</h2>
                    {table ? <RateTable rows={cinemas} /> : <RateBars rows={cinemas} />}
                </div>
            </section>
        </AdminLayout>
    );
}

AdminAnalytics.layout = null;

function Tile({ label, value, note, tone }: { label: string; value: string; note: string; tone?: 'good' | 'bad' }) {
    return (
        <div className="p-6">
            <p className="label">{label}</p>
            <p className="display mt-3 text-4xl sm:text-5xl">{value}</p>
            <p className={cn('mt-2 text-xs', tone === 'good' ? 'text-mint' : tone === 'bad' ? 'text-signal' : 'text-mute')}>{note}</p>
        </div>
    );
}

/** Column chart: one series, so no legend; the title names it. */
function RevenueBars({ series }: { series: Day[] }) {
    const [hover, setHover] = useState<number | null>(null);
    const max = niceCeil(Math.max(...series.map((day) => day.revenue), 1));
    const ticks = [0, 0.25, 0.5, 0.75, 1].map((fraction) => fraction * max);
    const W = 760, H = 260, left = 48, bottom = 26, top = 10;
    const plotW = W - left - 8, plotH = H - bottom - top;
    const slot = plotW / series.length;
    const barW = Math.max(2, Math.min(22, slot - 2));
    const peak = series.reduce((best, day, index) => (day.revenue > series[best].revenue ? index : best), 0);
    const active = hover !== null ? series[hover] : null;
    const labelEvery = Math.ceil(series.length / 8);

    return (
        <div className="relative mt-5">
            <svg viewBox={`0 0 ${W} ${H}`} className="w-full" role="img" aria-label={`Revenue per day over ${series.length} days. Peak ${money(series[peak]?.revenue ?? 0)} on ${series[peak]?.label}.`}
                onMouseLeave={() => setHover(null)}>
                {ticks.map((tick) => {
                    const y = top + plotH - (tick / max) * plotH;
                    return (
                        <g key={tick}>
                            <line x1={left} x2={W - 8} y1={y} y2={y} stroke="var(--color-line)" strokeWidth={1} />
                            <text x={left - 8} y={y + 4} textAnchor="end" className="fill-[var(--color-mute)] text-[10px]">{compact(tick)}</text>
                        </g>
                    );
                })}
                {series.map((day, index) => {
                    const h = (day.revenue / max) * plotH;
                    const x = left + index * slot + (slot - barW) / 2;
                    const y = top + plotH - h;
                    return (
                        <g key={day.day}>
                            {h > 0 && (
                                <path d={`M${x},${top + plotH} V${y + Math.min(4, h)} Q${x},${y} ${x + Math.min(4, barW / 2)},${y} H${x + barW - Math.min(4, barW / 2)} Q${x + barW},${y} ${x + barW},${y + Math.min(4, h)} V${top + plotH} Z`}
                                    fill={hover === index ? 'var(--color-accent)' : 'var(--color-chart)'} />
                            )}
                            {/* Hit target: the whole column, bigger than the bar. */}
                            <rect x={left + index * slot} y={top} width={slot} height={plotH} fill="transparent" onMouseEnter={() => setHover(index)} onFocus={() => setHover(index)} tabIndex={0} aria-label={`${day.label}: ${money(day.revenue)}, ${day.tickets} tickets`} />
                            {index % labelEvery === 0 && <text x={left + index * slot + slot / 2} y={H - 8} textAnchor="middle" className="fill-[var(--color-mute)] text-[10px]">{day.label.split(' ').slice(1).join(' ')}</text>}
                        </g>
                    );
                })}
                <line x1={left} x2={W - 8} y1={top + plotH} y2={top + plotH} stroke="var(--color-line-2)" />
                {series[peak]?.revenue > 0 && hover === null && (
                    <text x={left + peak * slot + slot / 2} y={top + plotH - (series[peak].revenue / max) * plotH - 6} textAnchor="middle" className="fill-[var(--color-paper)] text-[10px] font-semibold">{compact(series[peak].revenue)}</text>
                )}
            </svg>
            {active && hover !== null && (
                <div className="pointer-events-none absolute top-0 z-10 w-44 border border-line-2 bg-ink-2 p-3 text-xs shadow-xl"
                    style={{ left: `clamp(0px, calc(${((left + hover * slot + slot / 2) / W) * 100}% - 5.5rem), calc(100% - 11rem))` }}>
                    <p className="font-semibold text-paper">{active.label}</p>
                    <p className="num mt-1 text-paper-2">{money(active.revenue)}</p>
                    <p className="text-mute">{active.tickets} tickets · {active.bookings} bookings</p>
                </div>
            )}
        </div>
    );
}

/** Horizontal percentage bars: every bar labelled, so colour never carries meaning alone. */
function RateBars({ rows }: { rows: { name: string; sold: number; capacity: number; rate: number; href?: string }[] }) {
    const [hover, setHover] = useState<number | null>(null);

    if (rows.length === 0) return <p className="mt-5 text-sm text-mute">No shows in this range.</p>;

    return (
        <ul className="mt-5 space-y-3" onMouseLeave={() => setHover(null)}>
            {rows.slice(0, 12).map((row, index) => (
                <li key={row.name} className="grid grid-cols-[minmax(0,10rem)_1fr_3.5rem] items-center gap-3 text-sm" onMouseEnter={() => setHover(index)}>
                    {row.href ? <Link href={row.href} className="truncate hover:text-accent">{row.name}</Link> : <span className="truncate">{row.name}</span>}
                    <span className="relative h-3 bg-ink-3" title={`${row.sold.toLocaleString('en-PK')} of ${row.capacity.toLocaleString('en-PK')} seats`}>
                        <span className="absolute inset-y-0 left-0 rounded-r-[4px] transition-colors" style={{ width: `${Math.min(100, row.rate)}%`, background: hover === index ? 'var(--color-accent)' : 'var(--color-chart)' }} />
                    </span>
                    <span className="num text-right text-paper-2">{row.rate}%</span>
                    {hover === index && <span className="num col-span-3 -mt-1 text-xs text-mute">{row.sold.toLocaleString('en-PK')} of {row.capacity.toLocaleString('en-PK')} seats sold</span>}
                </li>
            ))}
        </ul>
    );
}

function DailyTable({ series }: { series: Day[] }) {
    return (
        <div className="mt-4 max-h-96 overflow-auto" data-lenis-prevent>
            <table className="w-full text-left text-sm">
                <thead className="sticky top-0 bg-ink-2"><tr className="label"><th className="px-3 py-2 font-normal">Day</th><th className="px-3 py-2 text-right font-normal">Revenue</th><th className="px-3 py-2 text-right font-normal">Tickets</th><th className="px-3 py-2 text-right font-normal">Bookings</th></tr></thead>
                <tbody>{series.map((day) => <tr key={day.day} className="border-t border-line"><td className="px-3 py-2">{day.label}</td><td className="num px-3 py-2 text-right">{money(day.revenue)}</td><td className="num px-3 py-2 text-right">{day.tickets}</td><td className="num px-3 py-2 text-right">{day.bookings}</td></tr>)}</tbody>
            </table>
        </div>
    );
}

function RateTable({ rows }: { rows: { name: string; sold: number; capacity: number; rate: number }[] }) {
    return (
        <table className="mt-4 w-full text-left text-sm">
            <thead><tr className="label"><th className="py-2 font-normal">Name</th><th className="py-2 text-right font-normal">Sold</th><th className="py-2 text-right font-normal">Seats</th><th className="py-2 text-right font-normal">Filled</th></tr></thead>
            <tbody>{rows.map((row) => <tr key={row.name} className="border-t border-line"><td className="py-2">{row.name}</td><td className="num py-2 text-right">{row.sold}</td><td className="num py-2 text-right">{row.capacity}</td><td className="num py-2 text-right">{row.rate}%</td></tr>)}</tbody>
        </table>
    );
}
