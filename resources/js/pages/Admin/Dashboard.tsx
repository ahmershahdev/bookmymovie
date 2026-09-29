import { Link } from '@inertiajs/react';
import { animate, motion, useInView, useReducedMotion } from 'motion/react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import Icon from '@/components/Icon';
import Tilt from '@/components/Tilt';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { cn, money, route, useShared } from '@/lib/utils';

type Day = { day: string; label: string; revenue: number; tickets: number };
type Show = { id: number; film: string; slug: string; screen: string; start: string; minutes: number; sold: number; seats: number };
type Heat = { weekday: number; hour: number; rate: number; sold: number; seats: number };
type Tone = 'volt' | 'signal' | 'mint';

interface Props {
    admin: { name: string; role: string; twoFactor: boolean };
    setup: { key: string; label: string; detail: string; done: boolean; href: string }[];
    kpis: { revenue_today: number; revenue_change: number | null; tickets_today: number; tonight_rate: number; tonight_shows: number; members_week: number; members_total: number };
    series: Day[];
    memberSeries: number[];
    tonight: Show[];
    heat: Heat[];
    top: { title: string; slug: string; poster: string | null; tickets: number; revenue: number }[];
    methods: { key: string; label: string; count: number }[];
    screens: { screen: string; shows: number; rate: number }[];
    attention: { label: string; count: number; href: string; tone: Tone }[];
    activity: { what: string; who: string; ago: string }[];
}

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const compact = (value: number) => (value >= 1_000_000 ? `${(value / 1_000_000).toFixed(1)}M` : value >= 1000 ? `${(value / 1000).toFixed(value >= 10_000 ? 0 : 1)}k` : String(Math.round(value)));

function greeting(): string {
    const hour = new Date().getHours();
    return hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
}

export default function AdminDashboard(props: Props) {
    const { auth } = useShared();
    const can = auth.adminRole?.can;
    const firstName = props.admin.name.split(' ')[0];
    const done = props.setup.filter((step) => step.done).length;
    const busiest = props.series.reduce((best, day) => (day.revenue > best.revenue ? day : best), props.series[0] ?? { label: '-', revenue: 0 });
    const fortnight = props.series.reduce((sum, day) => sum + day.revenue, 0);

    return (
        <AdminLayout label={`${greeting()}, ${firstName}`} title="Overview"
            lede={<>Everything happening at the box office on one screen: today&rsquo;s money, tonight&rsquo;s shows and what needs you. Numbers refresh every minute.</>}
            actions={
                <div className="flex flex-wrap gap-2">
                    <Link href={route('admin.analytics')} className="btn btn-ghost btn-sm"><Icon name="chart" size={14} /> Full report</Link>
                    <Link href={route('home')} className="btn btn-ghost btn-sm">Live site <Icon name="arrow-up-right" size={14} /></Link>
                </div>
            }
            guide={
                <Guide id="overview" steps={[
                    ['Read the four cards', 'Today’s takings, tickets, tonight’s seats and new members. Hover a card’s ⓘ to see exactly what it counts.'],
                    ['Clear the “Needs you” list', 'Each line links straight to the page where you fix it. The list is empty when there is nothing to do.'],
                    ['Watch tonight’s timeline', 'One lane per screen. The lighter part of each block is the share of seats already sold.'],
                    ['Plan with the heatmap', 'Brighter squares are the day and hour combinations that sell best: schedule big films there.'],
                ]} tip="Tip: every card and chart links to the page with the full detail. Nothing here can change data, so click around freely." />
            }>

            {/* Hero: 3D skyline of the last 14 days. */}
            <section className="dash-hero relative grid overflow-hidden border border-line bg-ink-2 lg:grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)]">
                <div className="relative z-10 flex flex-col justify-between gap-8 p-6 sm:p-8">
                    <div>
                        <p className="label label-accent">Last 14 days</p>
                        <p className="display mt-3 text-[clamp(2.75rem,6vw,5rem)] leading-none"><Counter value={fortnight} format={money} /></p>
                        <p className="mt-3 max-w-sm text-sm text-mute">Taken across every cinema, gift card spend included and cancelled bookings left out. Best day: <strong className="text-paper">{busiest.label}</strong> at {money(busiest.revenue)}.</p>
                    </div>
                    <dl className="grid grid-cols-3 gap-px border border-line bg-line text-center">
                        {([
                            ['Tickets', props.series.reduce((sum, day) => sum + day.tickets, 0).toLocaleString('en-PK')],
                            ['Shows today', String(props.kpis.tonight_shows)],
                            ['Members', props.kpis.members_total.toLocaleString('en-PK')],
                        ] as const).map(([label, value]) => (
                            <div key={label} className="bg-ink-2 px-2 py-3"><dt className="label">{label}</dt><dd className="num mt-1 text-lg text-paper">{value}</dd></div>
                        ))}
                    </dl>
                </div>
                <Skyline series={props.series} />
            </section>

            {/* KPI cards. */}
            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key numbers">
                <KpiCard index={0} icon="wallet" label="Revenue today" value={props.kpis.revenue_today} format={money} href={route('admin.analytics')}
                    info="Money from bookings made today (tickets, snacks, gift card spend). Cancelled bookings are not counted."
                    delta={props.kpis.revenue_change} deltaNote="vs yesterday" spark={props.series.map((day) => day.revenue)} />
                <KpiCard index={1} icon="ticket" label="Tickets sold today" value={props.kpis.tickets_today} href={route('admin.activity')}
                    info="Seats booked today, for any show date. Open Bookings & refunds to see each one."
                    note={`${props.series.at(-2)?.tickets ?? 0} yesterday`} spark={props.series.map((day) => day.tickets)} />
                <KpiCard index={2} icon="seat" label="Tonight’s seats filled" value={props.kpis.tonight_rate} format={(value) => `${Math.round(value)}%`} href={can?.manage ? route('admin.movies') : route('admin.analytics')}
                    info="Share of seats sold across every show on today’s date. Above 70% is a strong night."
                    note={`${props.kpis.tonight_shows} ${props.kpis.tonight_shows === 1 ? 'show' : 'shows'} on today`} ring={props.kpis.tonight_rate} />
                <KpiCard index={3} icon="user" label="New members this week" value={props.kpis.members_week} href={route('admin.users')}
                    info="Accounts created in the last 7 days. Bars show sign-ups per day for two weeks."
                    note={`${props.kpis.members_total.toLocaleString('en-PK')} members in total`} spark={props.memberSeries} bars />
            </section>

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                <TrendChart series={props.series} members={props.memberSeries} />
                <div className="grid gap-6">
                    <Attention items={props.attention} />
                    {done < props.setup.length && <Setup steps={props.setup} done={done} />}
                </div>
            </div>

            <Tonight shows={props.tonight} screens={props.screens} />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]">
                <Heatmap cells={props.heat} />
                <TopFilms films={props.top} />
            </div>

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] xl:grid-cols-[minmax(0,.9fr)_minmax(0,1fr)_minmax(0,1fr)]">
                <PaymentMix methods={props.methods} />
                <Activity rows={props.activity} />
                <QuickActions manage={Boolean(can?.manage ?? true)} own={Boolean(can?.own ?? true)} />
            </div>

            {done === props.setup.length && (
                <p className="flex items-center justify-center gap-2 text-xs text-mute"><Icon name="check" size={13} className="text-mint" /> Setup checklist complete. Nicely done.</p>
            )}
        </AdminLayout>
    );
}

AdminDashboard.layout = null;

/* ------------------------------------------------------------------ */

/** Tweens a number up once it scrolls into view. */
const plainNumber = (value: number) => Math.round(value).toLocaleString('en-PK');

function Counter({ value, format = plainNumber }: { value: number; format?: (value: number) => string }) {
    const ref = useRef<HTMLSpanElement>(null);
    const formatRef = useRef(format);
    formatRef.current = format;
    const inView = useInView(ref, { once: true });
    const reduce = useReducedMotion();

    useEffect(() => {
        if (!inView || reduce || !ref.current) return;
        const node = ref.current;
        const controls = animate(0, value, { duration: 1.4, ease: [0.16, 1, 0.3, 1], onUpdate: (latest) => { node.textContent = formatRef.current(latest); } });
        return () => controls.stop();
    }, [inView, reduce, value]);

    return <span ref={ref}>{format(value)}</span>;
}

function Panel({ title, info, action, className, children }: { title: string; info?: string; action?: ReactNode; className?: string; children: ReactNode }) {
    return (
        <section className={cn('flex min-w-0 flex-col border border-line bg-ink-2 p-5 sm:p-6', className)}>
            <header className="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 className="label label-accent">{title}</h2>
                    {info && <p className="mt-1.5 text-xs text-mute">{info}</p>}
                </div>
                {action}
            </header>
            {children}
        </section>
    );
}

function InfoDot({ text }: { text: string }) {
    return (
        <span className="group/info relative inline-flex">
            <button type="button" aria-label={text} className="grid h-5 w-5 place-items-center rounded-full border border-line-2 text-[10px] text-mute transition hover:border-accent hover:text-accent">i</button>
            <span role="tooltip" className="pointer-events-none absolute right-0 top-7 z-30 w-56 border border-line-2 bg-ink p-3 text-xs normal-case tracking-normal text-paper-2 opacity-0 shadow-xl transition group-focus-within/info:opacity-100 group-hover/info:opacity-100 [font-stretch:100%]">{text}</span>
        </span>
    );
}

/* ------------------------------------------------------------------ */

/**
 * The last 14 days as an isometric city of columns built from CSS 3D
 * faces. The plane leans toward the pointer; bars rise on first view.
 */
function Skyline({ series }: { series: Day[] }) {
    const reduce = useReducedMotion();
    const stage = useRef<HTMLDivElement>(null);
    const plane = useRef<HTMLDivElement>(null);
    const inView = useInView(stage, { once: true, amount: 0.3 });
    const [hover, setHover] = useState<number | null>(null);
    const max = Math.max(...series.map((day) => day.revenue), 1);
    const grown = inView || reduce;
    const cell = 22, gap = 12, tall = 170;

    const lean = (event: React.PointerEvent) => {
        if (reduce || event.pointerType === 'touch' || !stage.current || !plane.current) return;
        const box = stage.current.getBoundingClientRect();
        const x = (event.clientX - box.left) / box.width - 0.5;
        const y = (event.clientY - box.top) / box.height - 0.5;
        plane.current.style.transform = `rotateX(${58 - y * 10}deg) rotateZ(${-42 + x * 16}deg)`;
    };
    const rest = () => { if (plane.current) plane.current.style.transform = ''; };
    const active = hover !== null ? series[hover] : null;

    return (
        <div ref={stage} onPointerMove={lean} onPointerLeave={() => { rest(); setHover(null); }}
            className="skyline relative min-h-[18rem] overflow-hidden border-t border-line lg:border-l lg:border-t-0" aria-label="Revenue per day, last 14 days" role="img">
            <div className="absolute inset-0 grid place-items-center [perspective:1400px]">
                <div ref={plane} className="skyline-plane relative" style={{ width: series.length * (cell + gap), height: cell * 3 }}>
                    <div className="skyline-floor absolute -inset-6" aria-hidden="true" />
                    {series.map((day, index) => {
                        const h = grown ? Math.max(3, (day.revenue / max) * tall) : 0;
                        const lit = hover === index;
                        const ms = reduce ? 0 : 900;
                        const t = `${ms}ms cubic-bezier(.16,1,.3,1) ${index * 45}ms`;
                        return (
                            <div key={day.day} className="skyline-bar absolute" style={{ left: index * (cell + gap), top: cell, width: cell, height: cell }}
                                onPointerEnter={() => setHover(index)}>
                                <span className="face top" style={{ transform: `translateZ(${h}px)`, transition: `transform ${t}`, background: lit ? 'var(--color-paper)' : undefined }} />
                                <span className="face south" style={{ height: h, transition: `height ${t}` }} />
                                <span className="face north" style={{ height: h, transition: `height ${t}` }} />
                                <span className="face east" style={{ width: h, transition: `width ${t}` }} />
                                <span className="face west" style={{ width: h, transition: `width ${t}` }} />
                            </div>
                        );
                    })}
                </div>
            </div>
            <div className="pointer-events-none absolute bottom-4 left-4 right-4 flex items-end justify-between gap-4 text-xs">
                <span className="label">{active ? active.label : 'Hover a column'}</span>
                <span className="num text-paper">{active ? `${money(active.revenue)} · ${active.tickets} tickets` : `${series[0]?.label ?? ''} → ${series.at(-1)?.label ?? ''}`}</span>
            </div>
        </div>
    );
}

/* ------------------------------------------------------------------ */

function Sparkline({ values, bars }: { values: number[]; bars?: boolean }) {
    const W = 120, H = 36;
    const max = Math.max(...values, 1);
    if (bars) {
        const slot = W / values.length;
        return (
            <svg viewBox={`0 0 ${W} ${H}`} className="h-9 w-full" aria-hidden="true">
                {values.map((value, index) => {
                    const h = Math.max(1.5, (value / max) * (H - 2));
                    return <rect key={index} x={index * slot + 1} y={H - h} width={slot - 2} height={h} rx={1} fill={index >= values.length - 7 ? 'var(--color-accent)' : 'var(--color-ink-5)'} />;
                })}
            </svg>
        );
    }
    const points = values.map((value, index) => [(index / Math.max(1, values.length - 1)) * W, H - 3 - (value / max) * (H - 6)] as const);
    const line = points.map(([x, y], index) => `${index ? 'L' : 'M'}${x.toFixed(1)},${y.toFixed(1)}`).join(' ');
    const last = points.at(-1);
    return (
        <svg viewBox={`0 0 ${W} ${H}`} className="h-9 w-full overflow-visible" aria-hidden="true">
            <path d={`${line} L${W},${H} L0,${H} Z`} fill="var(--color-accent)" opacity={0.1} />
            <path d={line} fill="none" stroke="var(--color-accent)" strokeWidth={1.75} strokeLinejoin="round" vectorEffect="non-scaling-stroke" />
            {last && <circle cx={last[0]} cy={last[1]} r={2.5} fill="var(--color-accent)" />}
        </svg>
    );
}

function Ring({ value }: { value: number }) {
    const r = 15.5, c = 2 * Math.PI * r;
    return (
        <svg viewBox="0 0 36 36" className="h-12 w-12 -rotate-90" aria-hidden="true">
            <circle cx={18} cy={18} r={r} fill="none" stroke="var(--color-ink-4)" strokeWidth={4} />
            <motion.circle cx={18} cy={18} r={r} fill="none" stroke="var(--color-accent)" strokeWidth={4} strokeDasharray={c}
                initial={{ strokeDashoffset: c }} whileInView={{ strokeDashoffset: c * (1 - Math.min(100, value) / 100) }} viewport={{ once: true }} transition={{ duration: 1.4, ease: [0.16, 1, 0.3, 1] }} />
        </svg>
    );
}

function KpiCard({ index, icon, label, value, format, info, href, delta, deltaNote, note, spark, bars, ring }: {
    index: number; icon: string; label: string; value: number; format?: (value: number) => string; info: string; href: string;
    delta?: number | null; deltaNote?: string; note?: string; spark?: number[]; bars?: boolean; ring?: number;
}) {
    return (
        <motion.div initial={{ opacity: 0, y: 24, rotateX: -12 }} animate={{ opacity: 1, y: 0, rotateX: 0 }} transition={{ delay: 0.08 * index, duration: 0.7, ease: [0.16, 1, 0.3, 1] }} className="[perspective:900px]">
            <Tilt max={8} scale={1.015} className="h-full">
                <div className="kpi-card relative flex h-full flex-col border border-line bg-ink-2 p-5">
                    <div className="flex items-start justify-between gap-3 [transform:translateZ(18px)]">
                        <span className="flex items-center gap-2.5">
                            <span className="grid h-8 w-8 place-items-center border border-line-2 text-accent"><Icon name={icon} size={15} /></span>
                            <span className="label">{label}</span>
                        </span>
                        <InfoDot text={info} />
                    </div>
                    <div className="mt-5 flex items-end justify-between gap-3 [transform:translateZ(42px)]">
                        <p className="display truncate text-[clamp(2.25rem,3.4vw,3.25rem)] leading-none"><Counter value={value} format={format} /></p>
                        {ring !== undefined && <Ring value={ring} />}
                    </div>
                    <p className="mt-2 flex items-center gap-2 text-xs [transform:translateZ(24px)]">
                        {delta !== undefined && (delta === null
                            ? <span className="text-mute">No sales yesterday to compare</span>
                            : <span className={cn('num font-semibold', delta >= 0 ? 'text-mint' : 'text-signal')}>{delta >= 0 ? '▲' : '▼'} {Math.abs(delta)}% <span className="font-normal text-mute">{deltaNote}</span></span>)}
                        {note && <span className="text-mute">{note}</span>}
                    </p>
                    {spark && <div className="mt-auto pt-4 [transform:translateZ(14px)]"><Sparkline values={spark} bars={bars} /></div>}
                    <Link href={href} className="absolute inset-0 z-10" aria-label={`Open details for ${label}`}><span className="sr-only">Details</span></Link>
                </div>
            </Tilt>
        </motion.div>
    );
}

/* ------------------------------------------------------------------ */

function niceCeil(value: number): number {
    if (value <= 0) return 1;
    const power = 10 ** Math.floor(Math.log10(value));
    return ([1, 2, 2.5, 5, 10].find((step) => step * power >= value) ?? 10) * power;
}

/** Area chart with a metric switch; one series at a time keeps it readable. */
function TrendChart({ series, members }: { series: Day[]; members: number[] }) {
    const [metric, setMetric] = useState<'revenue' | 'tickets' | 'members'>('revenue');
    const [hover, setHover] = useState<number | null>(null);
    const values = metric === 'members' ? members : series.map((day) => day[metric]);
    const fmt = metric === 'revenue' ? money : (value: number) => Math.round(value).toLocaleString('en-PK');
    const max = niceCeil(Math.max(...values, 1));
    const W = 720, H = 260, left = 46, right = 12, top = 14, bottom = 28;
    const plotW = W - left - right, plotH = H - top - bottom;
    const x = (index: number) => left + (index / Math.max(1, values.length - 1)) * plotW;
    const y = (value: number) => top + plotH - (value / max) * plotH;
    const line = values.map((value, index) => `${index ? 'L' : 'M'}${x(index).toFixed(1)},${y(value).toFixed(1)}`).join(' ');
    const total = values.reduce((sum, value) => sum + value, 0);
    const lastWeek = values.slice(-7).reduce((sum, value) => sum + value, 0);
    const weekBefore = values.slice(0, 7).reduce((sum, value) => sum + value, 0);
    const change = weekBefore > 0 ? Math.round(((lastWeek - weekBefore) / weekBefore) * 100) : null;
    const best = values.reduce((top, value, index) => (value > values[top] ? index : top), 0);
    const quietest = values.reduce((low, value, index) => (value < values[low] ? index : low), 0);

    return (
        <Panel title="Two-week trend" info="Switch the metric to compare. The dashed line splits this week from last week."
            action={
                <div className="flex gap-1" role="group" aria-label="Metric">
                    {([['revenue', 'Revenue'], ['tickets', 'Tickets'], ['members', 'Sign-ups']] as const).map(([key, label]) => (
                        <button key={key} type="button" aria-pressed={metric === key} onClick={() => setMetric(key)} className={'chip'}>{label}</button>
                    ))}
                </div>
            }>
            <div className="mb-4 flex flex-wrap items-baseline gap-x-6 gap-y-1">
                <p className="display text-4xl">{fmt(total)}</p>
                {change !== null && <p className={cn('num text-xs', change >= 0 ? 'text-mint' : 'text-signal')}>{change >= 0 ? '▲' : '▼'} {Math.abs(change)}% this week vs last</p>}
            </div>
            <div className="relative mb-5 flex flex-1 flex-col justify-center">
                <svg viewBox={`0 0 ${W} ${H}`} className="w-full" role="img" aria-label={`${metric} per day for 14 days, total ${fmt(total)}`} onMouseLeave={() => setHover(null)}>
                    <defs>
                        <linearGradient id="trend-fill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stopColor="var(--color-accent)" stopOpacity={0.35} />
                            <stop offset="100%" stopColor="var(--color-accent)" stopOpacity={0} />
                        </linearGradient>
                    </defs>
                    {[0, 0.5, 1].map((fraction) => (
                        <g key={fraction}>
                            <line x1={left} x2={W - right} y1={y(fraction * max)} y2={y(fraction * max)} stroke="var(--color-line)" />
                            <text x={left - 8} y={y(fraction * max) + 4} textAnchor="end" className="fill-[var(--color-mute)] text-[10px]">{metric === 'revenue' ? compact(fraction * max) : Math.round(fraction * max)}</text>
                        </g>
                    ))}
                    <line x1={x(6.5)} x2={x(6.5)} y1={top} y2={top + plotH} stroke="var(--color-line-2)" strokeDasharray="4 4" />
                    <motion.path key={`${metric}-area`} d={`${line} L${x(values.length - 1)},${top + plotH} L${left},${top + plotH} Z`} fill="url(#trend-fill)" initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ duration: 0.6 }} />
                    <motion.path key={metric} d={line} fill="none" stroke="var(--color-accent)" strokeWidth={2.25} strokeLinejoin="round" strokeLinecap="round"
                        initial={{ pathLength: 0 }} animate={{ pathLength: 1 }} transition={{ duration: 1.1, ease: [0.16, 1, 0.3, 1] }} />
                    {series.map((day, index) => (
                        <g key={day.day}>
                            {index % 2 === 0 && <text x={x(index)} y={H - 8} textAnchor="middle" className="fill-[var(--color-mute)] text-[10px]">{day.label}</text>}
                            <rect x={x(index) - plotW / values.length / 2} y={top} width={plotW / values.length} height={plotH} fill="transparent" onMouseEnter={() => setHover(index)} />
                        </g>
                    ))}
                    {hover !== null && (
                        <g>
                            <line x1={x(hover)} x2={x(hover)} y1={top} y2={top + plotH} stroke="var(--color-paper)" strokeOpacity={0.35} />
                            <circle cx={x(hover)} cy={y(values[hover])} r={5} fill="var(--color-ink)" stroke="var(--color-accent)" strokeWidth={2.5} />
                        </g>
                    )}
                </svg>
                {hover !== null && (
                    <div className="pointer-events-none absolute top-0 z-10 w-40 border border-line-2 bg-ink p-3 text-xs shadow-xl"
                        style={{ left: `clamp(0px, calc(${(x(hover) / W) * 100}% - 5rem), calc(100% - 10rem))` }}>
                        <p className="font-semibold">{series[hover]?.label}</p>
                        <p className="num mt-1 text-accent">{fmt(values[hover])}</p>
                    </div>
                )}
            </div>
            {/* Pinned to the bottom so the card never shows a blank block when the column beside it is taller. */}
            <dl className="mt-auto grid grid-cols-3 gap-px border border-line bg-line pt-px text-center [&>div]:bg-ink-2 [&>div]:px-3 [&>div]:py-4">
                <div><dt className="label text-[10px]">Daily average</dt><dd className="num mt-1.5 text-sm">{fmt(total / Math.max(1, values.length))}</dd></div>
                <div><dt className="label text-[10px]">Best day</dt><dd className="num mt-1.5 text-sm">{series[best]?.label ?? '—'} · {fmt(values[best] ?? 0)}</dd></div>
                <div><dt className="label text-[10px]">Quietest day</dt><dd className="num mt-1.5 text-sm">{series[quietest]?.label ?? '—'} · {fmt(values[quietest] ?? 0)}</dd></div>
            </dl>
        </Panel>
    );
}

/* ------------------------------------------------------------------ */

function Attention({ items }: { items: Props['attention'] }) {
    const total = items.reduce((sum, item) => sum + item.count, 0);
    return (
        <Panel title="Needs you" info="Things waiting on a person. Click a line to go and fix it."
            action={<span className={cn('num grid h-7 min-w-7 place-items-center px-2 text-xs font-semibold', total ? 'bg-volt text-noir' : 'border border-line-2 text-mute')}>{total}</span>}>
            {items.length === 0 ? (
                <div className="grid flex-1 place-items-center gap-2 py-6 text-center">
                    <span className="grid h-12 w-12 place-items-center rounded-full border border-mint/40 text-mint"><Icon name="check" size={20} /></span>
                    <p className="text-sm font-semibold">All clear</p>
                    <p className="text-xs text-mute">No reviews, refunds, low stock or messages are waiting.</p>
                </div>
            ) : (
                <ul className="divide-y divide-line border border-line">
                    {items.map((item) => (
                        <li key={item.label}>
                            <Link href={item.href} className="group flex items-center gap-4 p-3.5 transition hover:bg-ink-3">
                                <span className={cn('num grid h-9 min-w-9 place-items-center px-2 text-sm font-semibold', item.tone === 'signal' ? 'bg-signal text-white' : item.tone === 'mint' ? 'bg-mint/15 text-mint' : 'bg-volt/15 text-accent')}>{item.count}</span>
                                <span className="flex-1 text-sm">{item.label}</span>
                                <Icon name="arrow-right" size={14} className="text-mute transition group-hover:translate-x-1 group-hover:text-accent" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </Panel>
    );
}

function Setup({ steps, done }: { steps: Props['setup']; done: number }) {
    const pct = Math.round((done / steps.length) * 100);
    return (
        <Panel title="Setup checklist" info="Finish these once and the box office runs itself." action={<span className="num text-xs text-accent">{done}/{steps.length}</span>}>
            <div className="mb-4 h-1.5 bg-ink-4"><motion.div className="h-full bg-volt" initial={{ width: 0 }} whileInView={{ width: `${pct}%` }} viewport={{ once: true }} transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }} /></div>
            <ol className="space-y-1">
                {steps.map((step) => (
                    <li key={step.key}>
                        <Link href={step.href} className={cn('flex gap-3 p-2.5 transition hover:bg-ink-3', step.done && 'opacity-55')}>
                            <span className={cn('mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border', step.done ? 'border-mint bg-mint text-noir' : 'border-line-2')}>{step.done && <Icon name="check" size={11} />}</span>
                            <span><span className={cn('block text-sm font-semibold', step.done && 'line-through')}>{step.label}</span>{!step.done && <span className="mt-0.5 block text-xs text-mute">{step.detail}</span>}</span>
                        </Link>
                    </li>
                ))}
            </ol>
        </Panel>
    );
}

/* ------------------------------------------------------------------ */

/** Tonight as lanes per screen on a shared clock. */
function Tonight({ shows, screens }: { shows: Show[]; screens: Props['screens'] }) {
    const minutes = (time: string) => { const [h, m] = time.split(':').map(Number); return h * 60 + m; };
    const lanes = useMemo(() => {
        const map = new Map<string, Show[]>();
        shows.forEach((show) => map.set(show.screen, [...(map.get(show.screen) ?? []), show]));
        return [...map.entries()];
    }, [shows]);
    const startHour = shows.length ? Math.floor(Math.min(...shows.map((show) => minutes(show.start))) / 60) : 10;
    const endHour = shows.length ? Math.min(28, Math.ceil(Math.max(...shows.map((show) => minutes(show.start) + show.minutes)) / 60)) : 24;
    const span = Math.max(1, endHour - startHour) * 60;
    const now = new Date();
    const nowAt = now.getHours() * 60 + now.getMinutes() - startHour * 60;
    const pos = (value: number) => `${(value / span) * 100}%`;

    return (
        <Panel title="Tonight on every screen" info="Each block is a show. Its bright part is the share of seats sold; the red line is the time right now."
            action={<span className="hidden text-xs text-mute sm:inline">{shows.length} shows · {screens.length} screens</span>}>
            {shows.length === 0 ? (
                <div className="grid place-items-center gap-2 border border-dashed border-line-2 py-10 text-center">
                    <Icon name="calendar" size={22} className="text-mute" />
                    <p className="text-sm font-semibold">No shows scheduled for today</p>
                    <p className="text-xs text-mute">Open Movies &amp; shows to add showtimes.</p>
                </div>
            ) : (
                <div className="no-scrollbar overflow-x-auto" data-lenis-prevent>
                    <div className="min-w-[46rem]">
                        <div className="relative ml-44 h-6 border-b border-line">
                            {Array.from({ length: endHour - startHour + 1 }, (_, index) => (
                                <span key={index} className="num absolute -translate-x-1/2 text-[10px] text-mute" style={{ left: pos(index * 60) }}>{String((startHour + index) % 24).padStart(2, '0')}:00</span>
                            ))}
                        </div>
                        <div className="relative">
                            {nowAt >= 0 && nowAt <= span && (
                                <span className="pointer-events-none absolute bottom-0 top-0 z-10 ml-44 w-px bg-signal" style={{ left: `calc((100% - 11rem) * ${nowAt / span})` }} aria-label="Now">
                                    <span className="absolute -left-1 -top-1 h-2 w-2 rounded-full bg-signal" />
                                </span>
                            )}
                            {lanes.map(([screen, laneShows]) => {
                                const meta = screens.find((row) => row.screen === screen);
                                return (
                                    <div key={screen} className="flex items-center border-b border-line">
                                        <div className="w-44 shrink-0 py-3 pr-3">
                                            <p className="truncate text-xs font-semibold" title={screen}>{screen}</p>
                                            <p className="num text-[10px] text-mute">{meta?.rate ?? 0}% full</p>
                                        </div>
                                        <div className="relative h-14 flex-1">
                                            {laneShows.map((show) => {
                                                const rate = show.seats ? Math.round((show.sold / show.seats) * 100) : 0;
                                                const past = minutes(show.start) + show.minutes - startHour * 60 < nowAt;
                                                return (
                                                    <Link key={show.id} href={route('movies.show', show.slug)} title={`${show.film} · ${show.start} · ${show.sold}/${show.seats} seats`}
                                                        className={cn('tl-block absolute top-2 bottom-2 overflow-hidden border border-line-2 px-2 py-1 transition hover:z-10 hover:border-accent', past && 'opacity-45')}
                                                        style={{ left: pos(minutes(show.start) - startHour * 60), width: pos(show.minutes), background: `linear-gradient(90deg, color-mix(in oklab, var(--color-accent) 32%, var(--color-ink-3)) ${rate}%, var(--color-ink-3) ${rate}%)` }}>
                                                        <span className="block truncate text-[11px] font-semibold">{show.film}</span>
                                                        <span className="num block truncate text-[10px] text-mute">{show.start} · {rate}%</span>
                                                    </Link>
                                                );
                                            })}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}
        </Panel>
    );
}

/* ------------------------------------------------------------------ */

function Heatmap({ cells }: { cells: Heat[] }) {
    const [hover, setHover] = useState<Heat | null>(null);
    const hours = cells.length ? Array.from({ length: Math.max(...cells.map((c) => c.hour)) - Math.min(...cells.map((c) => c.hour)) + 1 }, (_, index) => Math.min(...cells.map((c) => c.hour)) + index) : [];
    const find = (weekday: number, hour: number) => cells.find((cell) => cell.weekday === weekday && cell.hour === hour);
    const best = cells.reduce<Heat | null>((top, cell) => (cell.seats >= 20 && (!top || cell.rate > top.rate) ? cell : top), null);

    return (
        <Panel title="When seats sell" info="Share of seats filled by day and start time, over the last 30 days and the next 7.">
            {cells.length === 0 ? <p className="py-8 text-center text-sm text-mute">Not enough shows yet to draw the heatmap.</p> : (
                <>
                    <div className="no-scrollbar overflow-x-auto" data-lenis-prevent>
                        <table className="w-full border-separate [border-spacing:3px]" onMouseLeave={() => setHover(null)}>
                            <thead><tr><th /> {hours.map((hour) => <th key={hour} className="num pb-1 text-[10px] font-normal text-mute">{String(hour).padStart(2, '0')}</th>)}</tr></thead>
                            <tbody>
                                {WEEKDAYS.map((day, weekday) => (
                                    <tr key={day}>
                                        <th className="pr-2 text-left text-[10px] font-semibold uppercase tracking-wider text-mute">{day}</th>
                                        {hours.map((hour) => {
                                            const cell = find(weekday, hour);
                                            return (
                                                <td key={hour} onMouseEnter={() => setHover(cell ?? null)} title={cell ? `${day} ${hour}:00 · ${cell.rate}% (${cell.sold}/${cell.seats})` : `${day} ${hour}:00 · no shows`}
                                                    className={cn('heat-cell h-7 min-w-7', cell === hover && cell && 'outline outline-2 outline-paper')}
                                                    style={{ background: cell ? `color-mix(in oklab, var(--color-accent) ${Math.max(8, cell.rate)}%, var(--color-ink-3))` : 'var(--color-ink-3)', opacity: cell ? 1 : 0.4 }} />
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <span className="flex items-center gap-2 text-mute">0% <span className="h-2 w-24" style={{ background: 'linear-gradient(90deg, var(--color-ink-3), var(--color-accent))' }} /> 100%</span>
                        <span className="text-paper-2">{hover ? `${WEEKDAYS[hover.weekday]} ${String(hover.hour).padStart(2, '0')}:00 → ${hover.rate}% full (${hover.sold}/${hover.seats} seats)` : best ? `Best slot: ${WEEKDAYS[best.weekday]} ${String(best.hour).padStart(2, '0')}:00 at ${best.rate}%` : ''}</span>
                    </div>
                </>
            )}
        </Panel>
    );
}

function TopFilms({ films }: { films: Props['top'] }) {
    const max = Math.max(...films.map((film) => film.tickets), 1);
    return (
        <Panel title="Top films, 30 days" info="Ranked by tickets sold. The bar compares each film with the leader.">
            {films.length === 0 ? <p className="py-8 text-center text-sm text-mute">No bookings in the last 30 days.</p> : (
                <ol className="space-y-2">
                    {films.map((film, index) => (
                        <motion.li key={film.slug} initial={{ opacity: 0, x: 16 }} whileInView={{ opacity: 1, x: 0 }} viewport={{ once: true }} transition={{ delay: index * 0.07 }}>
                            <Link href={route('movies.show', film.slug)} className="group grid grid-cols-[1.5rem_3.5rem_1fr] items-center gap-3 p-2 transition hover:bg-ink-3">
                                <span className="display text-2xl text-dim group-hover:text-accent">{index + 1}</span>
                                <span className="block aspect-[4/3] overflow-hidden border border-line bg-ink-4 [perspective:400px]">
                                    {film.poster && <img src={film.poster} alt="" loading="lazy" className="h-full w-full object-cover transition duration-500 group-hover:scale-110 group-hover:[transform:rotateY(-12deg)_scale(1.1)]" />}
                                </span>
                                <span className="min-w-0">
                                    <span className="flex justify-between gap-3 text-sm"><span className="truncate font-semibold">{film.title}</span><span className="num shrink-0 text-xs text-mute">{film.tickets} tickets</span></span>
                                    <span className="mt-1.5 block h-1.5 bg-ink-4"><motion.span className="block h-full bg-[var(--color-chart)] group-hover:bg-volt" initial={{ width: 0 }} whileInView={{ width: `${(film.tickets / max) * 100}%` }} viewport={{ once: true }} transition={{ duration: 0.9, delay: index * 0.07 }} /></span>
                                    <span className="num mt-1 block text-[11px] text-mute">{money(film.revenue)}</span>
                                </span>
                            </Link>
                        </motion.li>
                    ))}
                </ol>
            )}
        </Panel>
    );
}

/* ------------------------------------------------------------------ */

const METHOD_COLORS = ['var(--color-accent)', 'var(--color-mint)', 'var(--color-lilac)', 'var(--color-paper-2)'];

function PaymentMix({ methods }: { methods: Props['methods'] }) {
    const [hover, setHover] = useState<number | null>(null);
    const total = methods.reduce((sum, method) => sum + method.count, 0);
    const r = 40, c = 2 * Math.PI * r;
    let offset = 0;

    return (
        <Panel title="How people pay" info="Bookings by payment method, last 30 days.">
            {total === 0 ? <p className="py-8 text-center text-sm text-mute">No payments yet.</p> : (
                <div className="flex flex-wrap items-center gap-6">
                    <div className="relative h-36 w-36 shrink-0 [perspective:600px]">
                        <svg viewBox="0 0 100 100" className="donut h-full w-full -rotate-90" onMouseLeave={() => setHover(null)}>
                            {methods.map((method, index) => {
                                const length = (method.count / total) * c;
                                const segment = (
                                    <circle key={method.key} cx={50} cy={50} r={r} fill="none" stroke={METHOD_COLORS[index % 4]} strokeWidth={hover === index ? 16 : 12}
                                        strokeDasharray={`${Math.max(0, length - 1.5)} ${c}`} strokeDashoffset={-offset} className="cursor-pointer transition-[stroke-width]" onMouseEnter={() => setHover(index)} />
                                );
                                offset += length;
                                return segment;
                            })}
                        </svg>
                        <div className="pointer-events-none absolute inset-0 grid place-items-center text-center">
                            <span><span className="display block text-3xl">{hover !== null ? `${Math.round((methods[hover].count / total) * 100)}%` : total}</span><span className="label">{hover !== null ? methods[hover].label : 'bookings'}</span></span>
                        </div>
                    </div>
                    <ul className="min-w-[9rem] flex-1 space-y-2 text-sm">
                        {methods.map((method, index) => (
                            <li key={method.key} onMouseEnter={() => setHover(index)} onMouseLeave={() => setHover(null)} className={cn('flex items-center gap-2.5 p-1 transition', hover === index && 'bg-ink-3')}>
                                <span className="h-2.5 w-2.5 shrink-0" style={{ background: METHOD_COLORS[index % 4] }} />
                                <span className="flex-1">{method.label}</span>
                                <span className="num text-xs text-mute">{method.count}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </Panel>
    );
}

function Activity({ rows }: { rows: Props['activity'] }) {
    return (
        <Panel title="Recent activity" info="The last ten entries in the audit log: who changed what.">
            {rows.length === 0 ? <p className="py-8 text-center text-sm text-mute">Nothing logged yet.</p> : (
                <ol className="relative space-y-3 border-l border-line pl-4">
                    {rows.map((row, index) => (
                        <li key={index} className="relative">
                            <span className={cn('absolute -left-[1.3rem] top-1.5 h-2 w-2 rounded-full', index === 0 ? 'bg-volt' : 'bg-ink-5')} />
                            <p className="text-sm">{row.what}</p>
                            <p className="num text-[11px] text-mute">{row.who} · {row.ago}</p>
                        </li>
                    ))}
                </ol>
            )}
        </Panel>
    );
}

function QuickActions({ manage, own }: { manage: boolean; own: boolean }) {
    const actions = ([
        ['film', 'Add a film', 'Artwork, trailer, price', 'admin.movies', manage],
        ['tag', 'New coupon', 'With a hard use limit', 'admin.commerce', manage],
        ['ticket', 'Find a booking', 'Refund or resend', 'admin.activity', true],
        ['star', 'Moderate reviews', 'Approve or hide', 'admin.reviews', true],
        ['megaphone', 'Message members', 'In-app broadcast', 'admin.content', own],
        ['chart', 'Revenue report', '7, 30 or 90 days', 'admin.analytics', true],
    ] as const).filter((action) => action[4]);

    return (
        <Panel title="Quick actions" info="The jobs people do most, one click away." className="lg:col-span-2 xl:col-span-1">
            <div className="grid grid-cols-2 gap-2">
                {actions.map(([icon, label, detail, name]) => (
                    <Link key={label} href={route(name)} className="quick-action group relative overflow-hidden border border-line p-3.5 transition hover:-translate-y-0.5 hover:border-accent">
                        <Icon name={icon} size={18} className="text-accent transition group-hover:scale-110" />
                        <span className="mt-3 block text-sm font-semibold">{label}</span>
                        <span className="block text-[11px] text-mute">{detail}</span>
                    </Link>
                ))}
            </div>
        </Panel>
    );
}
