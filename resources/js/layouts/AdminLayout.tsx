import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Icon from '@/components/Icon';
import ProgressBar from '@/components/shell/ProgressBar';
import ScrollIndicator from '@/components/shell/ScrollIndicator';
import Toaster from '@/components/shell/Toaster';
import { Alert } from '@/components/ui';
import { Meta } from '@/layouts/SiteLayout';
import { cn, route, useShared } from '@/lib/utils';

export const ADMIN_NAV = [
    { label: 'Overview', icon: 'grid', route: 'admin.dashboard', match: '/admin/dashboard' },
    { label: 'Movies & shows', icon: 'film', route: 'admin.dashboard', hash: '#movies', match: '/admin/dashboard/movies' },
    { label: 'Coupons, stock & sales', icon: 'tag', route: 'admin.commerce', match: '/admin/commerce' },
    { label: 'Members & bans', icon: 'user', route: 'admin.users', match: '/admin/users' },
    { label: 'Reviews', icon: 'star', route: 'admin.reviews', match: '/admin/reviews' },
    { label: 'Bookings & refunds', icon: 'ticket', route: 'admin.activity', match: '/admin/activity' },
    { label: 'Site & brand', icon: 'settings', route: 'admin.settings', match: '/admin/settings' },
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
    const { site } = useShared();
    const path = url.split('?')[0].split('#')[0];

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
            <nav className="no-scrollbar mt-5 flex gap-1 overflow-x-auto lg:flex-1 lg:flex-col lg:overflow-y-auto" data-lenis-prevent aria-label="Admin">
                {ADMIN_NAV.map((item) => {
                    const active = item.match === '/admin/dashboard' ? path === '/admin/dashboard' : path.startsWith(item.match);
                    return (
                        <Link key={item.label} href={route(item.route) + ('hash' in item ? item.hash : '')} aria-current={active ? 'page' : undefined}
                            className={cn('flex shrink-0 items-center gap-3 px-3 py-2.5 text-[11px] font-semibold uppercase tracking-[.08em] transition [font-stretch:115%]',
                                active ? 'bg-volt text-noir' : 'text-mute hover:bg-ink-3 hover:text-paper')}>
                            <Icon name={item.icon} size={16} /> {item.label}
                        </Link>
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

export default function AdminLayout({ title, label, lede, actions, children }: { title: string; label: string; lede?: ReactNode; actions?: ReactNode; children: ReactNode }) {
    const { errors } = useShared();
    const firstError = Object.values(errors)[0];

    return (
        <>
            <Meta />
            <ProgressBar />
            <div className="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
                <AdminSidebar />
                <main id="main" className="min-w-0 space-y-8 p-4 sm:p-6 lg:p-10">
                    <header className="grid gap-6 border-b border-line pb-8 xl:grid-cols-[1fr_auto] xl:items-end">
                        <div>
                            <p className="label label-accent">{label}</p>
                            <h1 className="display mt-3 text-[clamp(3rem,6vw,5.5rem)]">{title}</h1>
                            {lede && <p className="mt-3 max-w-2xl text-sm text-mute">{lede}</p>}
                        </div>
                        {actions}
                    </header>
                    <DemoBanner />
                    {firstError && <Alert tone="error">{firstError}</Alert>}
                    {children}
                </main>
            </div>
            <Toaster />
            <ScrollIndicator />
        </>
    );
}
