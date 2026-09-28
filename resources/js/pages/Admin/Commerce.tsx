import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/components/Icon';
import { Checkbox, Field, Select } from '@/components/ui';
import AdminLayout from '@/layouts/AdminLayout';
import { cn, money, route } from '@/lib/utils';

type Coupon = {
    id: number; code: string; description: string | null; type: 'percentage' | 'fixed'; value: number; cap: number | null; min_order: number;
    used: number; max_uses: number | null; per_user: number; from: string | null; until: string | null; active: boolean;
    state: 'live' | 'paused' | 'expired' | 'scheduled' | 'used up'; given: number;
};
type Snack = { id: number; name: string; category: string; icon: string; price: number; stock: number | null; low_at: number; active: boolean; sold_7d: number };
type Film = { id: number; title: string; slug: string; status: 'now_showing' | 'coming_soon' | 'ended'; bookings: boolean; carousel: boolean };

const STATE_TONE: Record<Coupon['state'], string> = { live: 'tag-mint', paused: '', expired: 'tag-signal', scheduled: 'tag-volt', 'used up': 'tag-signal' };
const inAMonth = () => new Date(Date.now() + 30 * 864e5).toISOString().slice(0, 16);
const now = () => new Date().toISOString().slice(0, 16);

export default function AdminCommerce({ coupons, snacks, films }: { coupons: Coupon[]; snacks: Snack[]; films: Film[] }) {
    const [tab, setTab] = useState<'coupons' | 'snacks' | 'films'>('coupons');
    const live = coupons.filter((coupon) => coupon.state === 'live').length;
    const low = snacks.filter((snack) => snack.stock !== null && snack.stock <= snack.low_at).length;
    const paused = films.filter((film) => !film.bookings).length;

    return (
        <AdminLayout label="Commerce" title="Coupons, stock & sales" lede="Run offers, keep the snack counter stocked and switch ticket sales on or off per film. Limits are enforced at checkout with atomic updates, so a rush can never oversell.">
            <div className="grid-lines grid-cols-2 lg:grid-cols-4">
                {[['Live coupons', live], ['Discount given', money(coupons.reduce((sum, coupon) => sum + coupon.given, 0))], ['Low-stock snacks', low], ['Films with sales paused', paused]].map(([label, value]) => (
                    <div key={label} className="p-6"><p className="label">{label}</p><p className="display mt-3 text-4xl sm:text-5xl">{value}</p></div>
                ))}
            </div>

            <div className="flex gap-1 border-b border-line" role="tablist">
                {([['coupons', 'Coupons', 'tag'], ['snacks', 'Snack stock', 'popcorn'], ['films', 'Film sales', 'film']] as const).map(([key, label, icon]) => (
                    <button key={key} type="button" role="tab" aria-selected={tab === key} onClick={() => setTab(key)}
                        className={cn('-mb-px flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition', tab === key ? 'border-accent text-accent' : 'border-transparent text-mute hover:text-paper')}>
                        <Icon name={icon} size={15} /> {label}
                    </button>
                ))}
            </div>

            {tab === 'coupons' && <Coupons coupons={coupons} />}
            {tab === 'snacks' && <Snacks snacks={snacks} />}
            {tab === 'films' && <Films films={films} />}
        </AdminLayout>
    );
}

AdminCommerce.layout = null;

function Coupons({ coupons }: { coupons: Coupon[] }) {
    const [editing, setEditing] = useState<Coupon | null>(null);

    return (
        <section className="grid gap-6 xl:grid-cols-12">
            <CouponForm key={editing?.id ?? 'new'} coupon={editing} onDone={() => setEditing(null)} />
            <div className="space-y-3 xl:col-span-7">
                {coupons.map((coupon) => {
                    const pct = coupon.max_uses ? Math.min(100, (coupon.used / coupon.max_uses) * 100) : 0;
                    return (
                        <article key={coupon.id} className={cn('border p-5 transition', editing?.id === coupon.id ? 'border-accent' : 'border-line')}>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="num text-2xl font-semibold tracking-wider">{coupon.code} <span className={cn('tag ms-2 align-middle capitalize', STATE_TONE[coupon.state])}>{coupon.state}</span></p>
                                    <p className="mt-1 text-sm text-paper-2">
                                        {coupon.type === 'percentage' ? `${coupon.value}% off` : `${money(coupon.value)} off`}
                                        {coupon.cap && coupon.type === 'percentage' ? `, up to ${money(coupon.cap)}` : ''}
                                        {coupon.min_order > 0 ? ` · min ${money(coupon.min_order)}` : ''} · {coupon.per_user}× per member
                                    </p>
                                    {coupon.description && <p className="mt-1 text-xs text-mute">{coupon.description}</p>}
                                </div>
                                <div className="flex gap-1">
                                    <button type="button" onClick={() => setEditing(coupon)} className="btn btn-ghost btn-sm">Edit</button>
                                    <button type="button" onClick={() => router.post(route('admin.coupons.toggle', coupon.id), {}, { preserveScroll: true })} className="btn btn-ghost btn-sm">{coupon.active ? 'Pause' : 'Resume'}</button>
                                    <button type="button" onClick={() => router.delete(route('admin.coupons.destroy', coupon.id), { preserveScroll: true })} className="btn btn-danger btn-sm" aria-label={`Delete ${coupon.code}`}><Icon name="trash" size={14} /></button>
                                </div>
                            </div>
                            <div className="mt-4">
                                <div className="flex justify-between text-xs text-mute">
                                    <span className="num">{coupon.used.toLocaleString('en-PK')} used{coupon.max_uses ? ` of ${coupon.max_uses.toLocaleString('en-PK')}` : ' · no limit'}</span>
                                    <span className="num">{money(coupon.given)} given · until {coupon.until?.replace('T', ' ')}</span>
                                </div>
                                {coupon.max_uses && <div className="mt-2 h-1 bg-line"><div className={cn('h-full', pct >= 100 ? 'bg-signal' : pct > 80 ? 'bg-volt' : 'bg-mint')} style={{ width: `${pct}%` }} /></div>}
                            </div>
                        </article>
                    );
                })}
                {coupons.length === 0 && <p className="border border-line p-8 text-sm text-mute">No coupons yet. Create one on the left.</p>}
            </div>
        </section>
    );
}

function CouponForm({ coupon, onDone }: { coupon: Coupon | null; onDone: () => void }) {
    const form = useForm({
        code: coupon?.code ?? '', description: coupon?.description ?? '', type: coupon?.type ?? 'percentage', value: String(coupon?.value ?? ''),
        cap: coupon?.cap ? String(coupon.cap) : '', min_order: coupon?.min_order ? String(coupon.min_order) : '', max_uses: coupon?.max_uses ? String(coupon.max_uses) : '',
        per_user: String(coupon?.per_user ?? 1), from: coupon?.from ?? now(), until: coupon?.until ?? inAMonth(), active: coupon?.active ?? true,
    });
    const errors = form.errors as Record<string, string>;
    const save = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => { form.reset(); onDone(); } };
        if (coupon) form.put(route('admin.coupons.update', coupon.id), options);
        else form.post(route('admin.coupons.store'), options);
    };

    return (
        <form onSubmit={save} className="panel h-fit space-y-4 p-6 xl:sticky xl:top-6 xl:col-span-5" noValidate>
            <div className="flex items-center justify-between">
                <p className="label label-accent">{coupon ? `Edit ${coupon.code}` : 'New coupon'}</p>
                {coupon && <button type="button" onClick={onDone} className="text-xs text-mute hover:text-paper">Cancel</button>}
            </div>
            {!coupon && <Field label="Code" value={form.data.code} onChange={(event) => form.setData('code', event.target.value.toUpperCase().replace(/[^A-Z0-9_-]/g, ''))} error={errors.code} required maxLength={30} placeholder="MONSOON20" inputClassName="num tracking-widest" />}
            <Field label="Description" value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} error={errors.description} maxLength={255} placeholder="Shown on the offers page" />
            <div className="grid grid-cols-2 gap-3">
                <Select label="Type" value={form.data.type} onChange={(event) => form.setData('type', event.target.value as 'percentage' | 'fixed')} options={[['percentage', 'Percent off'], ['fixed', 'Fixed PKR off']]} />
                <Field label={form.data.type === 'percentage' ? 'Percent' : 'Amount (PKR)'} type="number" min={1} max={form.data.type === 'percentage' ? 100 : 100000} value={form.data.value} onChange={(event) => form.setData('value', event.target.value)} error={errors.value} required />
                <Field label="Max discount (PKR)" type="number" min={1} value={form.data.cap} onChange={(event) => form.setData('cap', event.target.value)} error={errors.cap} placeholder="No cap" />
                <Field label="Min order (PKR)" type="number" min={0} value={form.data.min_order} onChange={(event) => form.setData('min_order', event.target.value)} error={errors.min_order} placeholder="0" />
                <Field label="Total uses" type="number" min={1} value={form.data.max_uses} onChange={(event) => form.setData('max_uses', event.target.value)} error={errors.max_uses} placeholder="Unlimited" hint="Hard stop, even under a rush." />
                <Field label="Per member" type="number" min={1} max={100} value={form.data.per_user} onChange={(event) => form.setData('per_user', event.target.value)} error={errors.per_user} required />
                <Field label="Starts" type="datetime-local" value={form.data.from} onChange={(event) => form.setData('from', event.target.value)} error={errors.from} required />
                <Field label="Ends" type="datetime-local" value={form.data.until} onChange={(event) => form.setData('until', event.target.value)} error={errors.until} required />
            </div>
            <Checkbox checked={form.data.active} onChange={(event) => form.setData('active', event.target.checked)}>Live as soon as it starts</Checkbox>
            <button type="submit" disabled={form.processing} className="btn btn-primary w-full"><Icon name={coupon ? 'check' : 'plus'} size={16} /> {coupon ? 'Save coupon' : 'Create coupon'}</button>
        </form>
    );
}

function Snacks({ snacks }: { snacks: Snack[] }) {
    return (
        <section className="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
            {snacks.map((snack) => <SnackCard key={snack.id} snack={snack} />)}
        </section>
    );
}

function SnackCard({ snack }: { snack: Snack }) {
    const tracked = snack.stock !== null;
    const form = useForm({ price: String(snack.price), stock: tracked ? String(snack.stock) : '', restock: '', low_at: String(snack.low_at), active: snack.active });
    const out = tracked && snack.stock === 0;
    const low = tracked && !out && (snack.stock ?? 0) <= snack.low_at;

    return (
        <form onSubmit={(event) => {
            event.preventDefault();
            form.transform((data) => ({ ...data, stock: data.stock === '' ? null : Number(data.stock), restock: data.restock === '' ? null : Number(data.restock) }));
            form.put(route('admin.snacks.update', snack.id), { preserveScroll: true, onSuccess: () => form.setData('restock', '') });
        }} className={cn('border p-5', out ? 'border-signal/60' : low ? 'border-volt/60' : 'border-line')} noValidate>
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <span className="grid h-10 w-10 place-items-center bg-ink-3 text-accent"><Icon name={snack.icon || 'popcorn'} size={18} /></span>
                    <div>
                        <p className="font-semibold">{snack.name}</p>
                        <p className="text-xs capitalize text-mute">{snack.category} · {snack.sold_7d} sold this week</p>
                    </div>
                </div>
                <span className={cn('tag', out ? 'tag-signal' : low ? 'tag-volt' : !snack.active ? '' : 'tag-mint')}>{out ? 'Sold out' : low ? `${snack.stock} left` : !snack.active ? 'Hidden' : tracked ? `${snack.stock} in stock` : 'Untracked'}</span>
            </div>
            <div className="mt-4 grid grid-cols-2 gap-3">
                <Field label="Price (PKR)" type="number" min={0} value={form.data.price} onChange={(event) => form.setData('price', event.target.value)} error={form.errors.price} />
                <Field label="Stock" type="number" min={0} value={form.data.stock} onChange={(event) => form.setData('stock', event.target.value)} error={form.errors.stock} placeholder="Untracked" />
                <Field label="Add units" type="number" min={1} value={form.data.restock} onChange={(event) => form.setData('restock', event.target.value)} error={form.errors.restock} placeholder="+0" hint="Adds to live stock." />
                <Field label="Warn at" type="number" min={0} value={form.data.low_at} onChange={(event) => form.setData('low_at', event.target.value)} error={form.errors.low_at} />
            </div>
            <div className="mt-4 flex items-center justify-between gap-3">
                <Checkbox checked={form.data.active} onChange={(event) => form.setData('active', event.target.checked)}>On sale</Checkbox>
                <button type="submit" disabled={form.processing || !form.isDirty} className="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    );
}

function Films({ films }: { films: Film[] }) {
    const update = (film: Film, data: Record<string, string | boolean>) => router.put(route('admin.films.access', film.id), data, { preserveScroll: true, preserveState: true });

    return (
        <div className="overflow-x-auto border border-line" data-lenis-prevent>
            <table className="w-full min-w-[760px] text-left text-sm">
                <thead className="bg-ink-2">
                    <tr className="label">{['Film', 'Status', 'Ticket sales', 'Home carousel', ''].map((heading) => <th key={heading} scope="col" className="px-4 py-3 font-normal">{heading}</th>)}</tr>
                </thead>
                <tbody>
                    {films.map((film) => (
                        <tr key={film.id} className="border-t border-line align-middle hover:bg-ink-2">
                            <td className="px-4 py-3 font-semibold">{film.title}</td>
                            <td className="px-4 py-3">
                                <select value={film.status} onChange={(event) => update(film, { status: event.target.value })} className="input !min-h-9 !py-1 text-sm" aria-label={`Status of ${film.title}`}>
                                    <option value="now_showing">Now showing</option>
                                    <option value="coming_soon">Coming soon</option>
                                    <option value="ended">Ended</option>
                                </select>
                            </td>
                            <td className="px-4 py-3"><Toggle on={film.bookings} label={film.bookings ? 'Open' : 'Paused'} onChange={() => update(film, { bookings: !film.bookings })} /></td>
                            <td className="px-4 py-3"><Toggle on={film.carousel} label={film.carousel ? 'Featured' : 'Off'} onChange={() => update(film, { carousel: !film.carousel })} /></td>
                            <td className="px-4 py-3 text-right"><Link href={route('movies.show', film.slug)} className="btn btn-ghost btn-sm">View <Icon name="arrow-up-right" size={14} /></Link></td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function Toggle({ on, label, onChange }: { on: boolean; label: string; onChange: () => void }) {
    return (
        <button type="button" role="switch" aria-checked={on} onClick={onChange} className="flex items-center gap-2.5 text-xs font-semibold uppercase tracking-[.08em] [font-stretch:115%]">
            <span className={cn('relative h-5 w-9 border transition-colors', on ? 'border-volt bg-volt' : 'border-line-2 bg-ink-3')}>
                <span className={cn('absolute top-0.5 h-3.5 w-3.5 transition-all', on ? 'left-[1.1rem] bg-noir' : 'left-0.5 bg-mute')} />
            </span>
            <span className={on ? 'text-paper' : 'text-mute'}>{label}</span>
        </button>
    );
}
