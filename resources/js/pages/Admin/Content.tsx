import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/components/Icon';
import { Field, TextArea } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { cn, route } from '@/lib/utils';

type Settings = Record<'site_name' | 'canonical_base_url' | 'default_meta_title' | 'default_meta_description' | 'support_email' | 'home_ticker_messages', string>;
type Page = { id: number; slug: string; title: string; meta_title: string; meta_description: string; canonical_path: string; excerpt: string };

export default function AdminContent({ settings, contentPages, members }: { settings: Settings; contentPages: Page[]; members: number }) {
    return (
        <AdminLayout label="Content" title="SEO & broadcasts" lede="How each page appears in Google, the scrolling ticker on the home page, and messages to every member."
            guide={
                <Guide id="content" steps={[
                    ['Pick a page', 'Choose a page on the left. The Google preview updates as you type.'],
                    ['Stay inside the limits', 'Titles up to 60 characters and descriptions up to 150. The counters turn red past the limit.'],
                    ['Edit the ticker', 'Separate each message with a | (pipe). They scroll across the home page in order.'],
                    ['Broadcast with care', 'A broadcast lands in every member’s notifications at once and cannot be recalled.'],
                ]} tip="Brand name, logo, contact details and social links live under Site & brand." />
            }>
            <SeoEditor pages={contentPages} canonical={settings.canonical_base_url} />
            <div className="grid gap-6 xl:grid-cols-2">
                <Ticker settings={settings} />
                <Broadcast members={members} />
            </div>
        </AdminLayout>
    );
}

AdminContent.layout = null;

function Counter({ value, max }: { value: string; max: number }) {
    return <span className={cn('num text-[11px]', value.length > max ? 'text-signal' : value.length > max * 0.9 ? 'text-accent' : 'text-dim')}>{value.length}/{max}</span>;
}

function SeoEditor({ pages, canonical }: { pages: Page[]; canonical: string }) {
    const form = useForm({ _action: 'update_pages', pages });
    const [index, setIndex] = useState(0);
    const errors = form.errors as Record<string, string>;
    const page = form.data.pages[index];
    const update = (key: keyof Page, value: string) => form.setData('pages', form.data.pages.map((row, position) => (position === index ? { ...row, [key]: value } : row)));
    const bind = (key: keyof Page) => ({ value: String(page[key] ?? ''), onChange: (event: { target: { value: string } }) => update(key, event.target.value), error: errors[`pages.${index}.${key}`] });

    if (!page) return <p className="border border-dashed border-line-2 p-10 text-center text-sm text-mute">No content pages exist yet.</p>;

    return (
        <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.dashboard'), { preserveScroll: true }); }}
            className="grid border border-line bg-ink-2 lg:grid-cols-[14rem_minmax(0,1fr)]">
            <nav className="no-scrollbar flex overflow-x-auto border-b border-line lg:flex-col lg:border-b-0 lg:border-r" aria-label="Pages">
                {form.data.pages.map((row, position) => {
                    const warn = row.meta_title.length === 0 || row.meta_description.length === 0;
                    return (
                        <button key={row.id} type="button" onClick={() => setIndex(position)} aria-current={index === position || undefined}
                            className={cn('flex shrink-0 items-center justify-between gap-3 px-4 py-3 text-left text-sm transition', index === position ? 'bg-volt text-noir' : 'hover:bg-ink-3')}>
                            <span className="truncate font-semibold capitalize">{row.slug.replace(/-/g, ' ')}</span>
                            {warn && <span title="Missing search title or description" className={cn('h-2 w-2 shrink-0 rounded-full', index === position ? 'bg-noir' : 'bg-signal')} />}
                        </button>
                    );
                })}
            </nav>
            <div className="space-y-5 p-5 sm:p-6">
                <div className="grid gap-5 lg:grid-cols-2">
                    <Field label="Page heading" placeholder="e.g. Refund policy" {...bind('title')} />
                    <Field label="Canonical path" placeholder="/about" {...bind('canonical_path')} hint="The one address Google should index for this page." />
                    <Field label="Search title" placeholder="Under 60 characters, e.g. Refunds & cancellations | BookMyMovie" {...bind('meta_title')} aside={<Counter value={page.meta_title} max={60} />} />
                    <Field label="Search description" placeholder="Under 150 characters: what the page answers" {...bind('meta_description')} aside={<Counter value={page.meta_description} max={150} />} />
                    <TextArea className="lg:col-span-2" label="Short summary" placeholder="One sentence shown on cards that link here" rows={2} {...bind('excerpt')} hint="Used on cards that link to this page." />
                </div>
                <div className="border border-line bg-ink p-4" aria-label="Google preview">
                    <p className="label mb-2 flex items-center gap-2"><Icon name="search" size={12} /> Google preview</p>
                    <p className="truncate text-lg text-[#8ab4f8]">{page.meta_title || page.title}</p>
                    <p className="num truncate text-xs text-mint">{canonical.replace(/\/$/, '')}{page.canonical_path || `/${page.slug}`}</p>
                    <p className="mt-1 line-clamp-2 text-sm text-paper-2">{page.meta_description || 'No description yet: Google will pick some text from the page itself.'}</p>
                </div>
                <div className="flex items-center justify-between gap-3">
                    <p className="text-xs text-mute">{form.isDirty ? <span className="text-accent">Unsaved changes on one or more pages.</span> : 'Saves every page at once.'}</p>
                    <button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary">{form.processing ? 'Saving…' : 'Save SEO'}</button>
                </div>
            </div>
        </form>
    );
}

function Ticker({ settings }: { settings: Settings }) {
    const form = useForm({ _action: 'update_settings', ...settings });
    const messages = form.data.home_ticker_messages.split('|').map((message) => message.trim()).filter(Boolean);

    return (
        <form onSubmit={(event) => { event.preventDefault(); form.post(route('admin.dashboard'), { preserveScroll: true }); }} className="flex flex-col border border-line bg-ink-2 p-5 sm:p-6">
            <p className="label label-accent">Home page ticker</p>
            <p className="mt-1.5 text-xs text-mute">Short lines that scroll across the home page. Separate them with |</p>
            <TextArea className="mt-5" label="Messages" placeholder="One message per line, e.g. No booking fees on any show" rows={3} maxLength={1000} value={form.data.home_ticker_messages} onChange={(event) => form.setData('home_ticker_messages', event.target.value)} error={form.errors.home_ticker_messages} />
            <div className="mt-4 overflow-hidden border-y border-line bg-ink py-2" aria-label="Ticker preview">
                <div className="flex w-max animate-[marquee_18s_linear_infinite] motion-reduce:animate-none gap-8 whitespace-nowrap text-[11px] font-semibold uppercase tracking-[.12em] [font-stretch:115%]">
                    {[...messages, ...messages].map((message, index) => <span key={index} className="flex items-center gap-8">{message} <span className="text-accent">✦</span></span>)}
                    {messages.length === 0 && <span className="text-mute">Nothing to show</span>}
                </div>
            </div>
            <Field className="mt-5" label="Canonical site address" placeholder="https://bookmymovie.ahmershah.dev" value={form.data.canonical_base_url} onChange={(event) => form.setData('canonical_base_url', event.target.value)} error={form.errors.canonical_base_url} hint="The main https:// address. Used for SEO links and the sitemap." />
            <button type="submit" disabled={form.processing} aria-busy={form.processing} className="btn btn-primary mt-5 self-start">{form.processing ? 'Saving…' : 'Save ticker'}</button>
        </form>
    );
}

function Broadcast({ members }: { members: number }) {
    const form = useForm({ _action: 'send_notification', message: '' });
    const [confirm, setConfirm] = useState(false);

    return (
        <form onSubmit={(event) => { event.preventDefault(); if (!confirm) { setConfirm(true); return; } form.post(route('admin.dashboard'), { preserveScroll: true, onSuccess: () => { form.reset('message'); setConfirm(false); } }); }}
            className="flex flex-col border border-line bg-ink-2 p-5 sm:p-6">
            <p className="label label-accent">Broadcast to members</p>
            <p className="mt-1.5 text-xs text-mute">Appears in the bell menu of all {members.toLocaleString('en-PK')} active members. HTML is stripped.</p>
            <TextArea className="mt-5" label="Message" rows={5} maxLength={1000} value={form.data.message} onChange={(event) => { form.setData('message', event.target.value); setConfirm(false); }} error={form.errors.message}
                aside={<Counter value={form.data.message} max={1000} />} placeholder="e.g. Midnight premiere of Dune on Friday. Booking opens at 6 pm." />
            {confirm && (
                <p className="mt-4 flex items-start gap-2 border border-volt/40 bg-volt/10 p-3 text-xs"><Icon name="alert" size={14} className="mt-0.5 shrink-0 text-accent" /> This goes to {members.toLocaleString('en-PK')} people and cannot be undone. Press send again to confirm.</p>
            )}
            <div className="mt-5 flex gap-2">
                <button type="submit" disabled={form.processing || form.data.message.trim() === ''} className="btn btn-primary"><Icon name="megaphone" size={14} /> {confirm ? 'Yes, send it' : 'Send to everyone'}</button>
                {confirm && <button type="button" onClick={() => setConfirm(false)} className="btn btn-ghost">Cancel</button>}
            </div>
        </form>
    );
}
