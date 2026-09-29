import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import Icon from '@/components/Icon';
import ProgressBar from '@/components/shell/ProgressBar';
import ScrollIndicator from '@/components/shell/ScrollIndicator';
import Toaster from '@/components/shell/Toaster';
import { Alert } from '@/components/ui';
import { Meta } from '@/layouts/SiteLayout';
import { cn, route, useShared } from '@/lib/utils';

/** Grouped by the job at hand, so the list reads as "what do I need to do?". */
export const ADMIN_NAV = [
    { group: 'Today', label: 'Overview', hint: 'Sales, shows and alerts at a glance', icon: 'grid', route: 'admin.dashboard', match: '/admin/dashboard', need: 'view' },
    { group: 'Today', label: 'Bookings & refunds', hint: 'Refunds, gift cards, audit log', icon: 'ticket', route: 'admin.activity', match: '/admin/activity', need: 'view' },
    { group: 'Catalogue', label: 'Movies & shows', hint: 'Films, artwork, showtimes', icon: 'film', route: 'admin.movies', match: '/admin/movies', need: 'manage' },
    { group: 'Catalogue', label: 'Coupons & stock', hint: 'Offers, snacks, ticket sales on/off', icon: 'tag', route: 'admin.commerce', match: '/admin/commerce', need: 'manage' },
    { group: 'Catalogue', label: 'Reviews', hint: 'Approve, hide or flag reviews', icon: 'star', route: 'admin.reviews', match: '/admin/reviews', need: 'view' },
    { group: 'People', label: 'Members & bans', hint: 'Look up customers, stop abuse', icon: 'user', route: 'admin.users', match: '/admin/users', need: 'view' },
    { group: 'People', label: 'Staff & roles', hint: 'Who can use this back office', icon: 'shield', route: 'admin.staff', match: '/admin/staff', need: 'own' },
    { group: 'Reports', label: 'Revenue & occupancy', hint: 'Money and seats filled over time', icon: 'chart', route: 'admin.analytics', match: '/admin/analytics', need: 'view' },
    { group: 'Settings', label: 'Site & brand', hint: 'Name, logo, contact, socials', icon: 'settings', route: 'admin.settings', match: '/admin/settings', need: 'own' },
    { group: 'Settings', label: 'SEO & broadcasts', hint: 'Page titles, ticker, notices', icon: 'megaphone', route: 'admin.content', match: '/admin/content', need: 'own' },
    { group: 'Settings', label: 'My security', hint: 'Password and two-step sign-in', icon: 'lock', route: 'admin.security', match: '/admin/security', need: 'view' },
] as const;

/** Read-only notice for the public demo admin. */
export function DemoBanner() {
    const { auth } = useShared();
    if (!auth.adminReadOnly) return null;
    return (
        <div className="flex items-center gap-3 border border-volt/40 bg-volt/10 px-4 py-3 text-sm">
            <Icon name="eye" size={16} className="shrink-0 text-accent" />
            <p><strong className="font-semibold">Demo admin, read-only.</strong> <span className="text-paper-2">You can open every screen; saving is switched off on the live site.</span></p>
        </div>
    );
}

export function AdminSidebar({ children }: { children?: ReactNode }) {
    const { url } = usePage();
    const { site, auth } = useShared();
    const path = url.split('?')[0].split('#')[0];
    const role = auth.adminRole;
    const visible = ADMIN_NAV.filter((item) => item.need === 'view' || (role ? role.can[item.need] : true));
    const nav = useRef<HTMLElement>(null);

    // The list scrolls on its own; keep the current page in view after each visit.
    useEffect(() => {
        nav.current?.querySelector<HTMLElement>('[aria-current="page"]')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }, [path]);

    return (
        <aside className="z-20 flex flex-col border-b border-line bg-ink-2 p-4 lg:sticky lg:top-0 lg:h-screen lg:border-b-0 lg:border-r lg:p-5">
            <div className="flex items-center justify-between gap-3">
                <Link href={route('home')} className="flex items-center gap-3" aria-label={`${site.name} home`}>
                    <img src={site.logo_url} alt="" width={64} height={48} className="h-10 w-auto" />
                    <span className="label text-paper">Back office</span>
                </Link>
                <Link href={route('admin.logout')} method="post" as="button" className="btn btn-ghost btn-sm lg:hidden">Logout</Link>
            </div>
            {children}
            {role && (
                <div className="mt-4 hidden items-center justify-between gap-2 border border-line px-3 py-2 lg:flex">
                    <span className="min-w-0"><span className="block truncate text-xs font-semibold">{role.name}</span><span className="label text-accent">{role.label}</span></span>
                    <Link href={route('admin.security')} title={role.twoFactor ? 'Two-step sign-in is on' : 'Turn on two-step sign-in'}
                        className={cn('grid h-7 w-7 place-items-center border', role.twoFactor ? 'border-mint/50 text-mint' : 'border-signal/50 text-signal')}>
                        <Icon name={role.twoFactor ? 'shield' : 'alert'} size={13} />
                    </Link>
                </div>
            )}
            <nav ref={nav} className="no-scrollbar mt-5 flex gap-1 overflow-x-auto lg:flex-1 lg:flex-col lg:gap-0 lg:overflow-y-auto" data-lenis-prevent aria-label="Admin">
                {visible.map((item, index) => {
                    const active = item.match === '/admin/dashboard' ? path === '/admin/dashboard' : path.startsWith(item.match);
                    const firstOfGroup = index === 0 || visible[index - 1].group !== item.group;
                    return (
                        <div key={item.label} className="contents">
                            {firstOfGroup && <p className={cn('label hidden px-3 pb-1.5 text-[10px] text-dim lg:block', index > 0 && 'mt-4')}>{item.group}</p>}
                            <Link href={route(item.route)} aria-current={active ? 'page' : undefined} title={item.hint}
                                className={cn('group flex min-h-11 shrink-0 items-center gap-3 border-l-2 px-3 py-2 transition lg:mb-0.5',
                                    active ? 'border-noir bg-volt text-noir' : 'border-transparent text-paper-2 hover:border-line-2 hover:bg-ink-3 hover:text-paper')}>
                                <Icon name={item.icon} size={16} className={active ? '' : 'text-mute group-hover:text-accent'} />
                                <span className="min-w-0">
                                    <span className="block text-[12px] font-semibold uppercase tracking-[.06em] [font-stretch:112%]">{item.label}</span>
                                    <span className={cn('hidden truncate text-[11px] normal-case lg:block', active ? 'text-noir/70' : 'text-mute')}>{item.hint}</span>
                                </span>
                            </Link>
                        </div>
                    );
                })}
            </nav>
            <div className="mt-4 hidden gap-2 border-t border-line pt-4 lg:grid">
                <Link href={route('home')} className="btn btn-ghost btn-sm">Public site <Icon name="arrow-up-right" size={14} /></Link>
                <Link href={route('admin.logout')} method="post" as="button" className="btn btn-primary btn-sm"><Icon name="logout" size={14} /> Logout</Link>
            </div>
        </aside>
    );
}

export type GuideStep = [title: string, detail: ReactNode];

/**
 * "How this page works": numbered steps every admin screen opens with.
 * Collapsed state is remembered per page, so regulars can tuck it away.
 */
export function Guide({ id, steps, tip }: { id: string; steps: GuideStep[]; tip?: ReactNode }) {
    const key = `admin-guide:${id}`;
    const [open, setOpen] = useState(() => {
        try { return window.localStorage.getItem(key) !== 'closed'; } catch { return true; }
    });
    const toggle = () => {
        setOpen(!open);
        try { window.localStorage.setItem(key, open ? 'closed' : 'open'); } catch { /* storage blocked */ }
    };

    return (
        <section className="guide border border-line bg-ink-2" aria-label="How this page works">
            <button type="button" onClick={toggle} aria-expanded={open} className="flex w-full items-center justify-between gap-4 px-5 py-3.5 text-left">
                <span className="flex items-center gap-3">
                    <span className="grid h-7 w-7 place-items-center bg-volt text-noir"><Icon name="info" size={14} /></span>
                    <span className="label text-paper">How this page works</span>
                    <span className="hidden text-xs text-mute sm:inline">{open ? 'Tap to hide' : `Show ${steps.length} steps`}</span>
                </span>
                <Icon name="chevron-down" size={16} className={cn('text-mute transition-transform', open && 'rotate-180')} />
            </button>
            {open && (
                <div className="border-t border-line px-5 pb-5 pt-4">
                    <ol className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {steps.map(([title, detail], index) => (
                            <li key={title} className="flex gap-3">
                                <span className="num grid h-6 w-6 shrink-0 place-items-center border border-line-2 text-[11px] text-accent">{index + 1}</span>
                                <span><span className="block text-sm font-semibold">{title}</span><span className="mt-1 block text-xs leading-relaxed text-mute">{detail}</span></span>
                            </li>
                        ))}
                    </ol>
                    {tip && <p className="mt-4 flex items-start gap-2 border-t border-line pt-3 text-xs text-paper-2"><Icon name="sparkle" size={13} className="mt-0.5 shrink-0 text-accent" /> <span>{tip}</span></p>}
                </div>
            )}
        </section>
    );
}

export default function AdminLayout({ title, label, lede, actions, guide, children }: { title: string; label: string; lede?: ReactNode; actions?: ReactNode; guide?: ReactNode; children: ReactNode }) {
    const { errors } = useShared();
    const firstError = Object.values(errors)[0];

    return (
        <>
            <Meta />
            <ProgressBar />
            <div className="min-h-screen lg:grid lg:grid-cols-[18rem_1fr]">
                <AdminSidebar />
                <main id="main" className="min-w-0 space-y-6 p-4 sm:p-6 lg:p-10">
                    <header className="grid gap-5 border-b border-line pb-6 xl:grid-cols-[1fr_auto] xl:items-end">
                        <div className="min-w-0">
                            <p className="label label-accent">{label}</p>
                            <h1 className="display mt-2 text-[clamp(2.25rem,4.5vw,3.75rem)]">{title}</h1>
                            {lede && <p className="mt-2 max-w-3xl text-[0.9375rem] leading-relaxed text-paper-2">{lede}</p>}
                        </div>
                        {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
                    </header>
                    <DemoBanner />
                    {guide}
                    {firstError && <Alert tone="error">{firstError}</Alert>}
                    {children}
                </main>
            </div>
            <Toaster />
            <ScrollIndicator />
        </>
    );
}
