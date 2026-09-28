import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { Breadcrumbs } from '@/components/ui';
import { cn, money, plural, route } from '@/lib/utils';
import type { BookingSummary } from '@/types';

const tabs: [string, string, string, string[]][] = [
    ['Overview', 'user.dashboard', 'grid', ['/account']],
    ['Bookings', 'user.bookings', 'ticket', ['/account/bookings']],
    ['Watchlist', 'user.wishlist', 'heart', ['/account/wishlist']],
    ['Cart', 'user.cart', 'bag', ['/cart', '/checkout']],
    ['Profile & security', 'user.profile', 'user', ['/account/profile']],
];

export function AccountNav({ className }: { className?: string }) {
    const path = usePage().url.split('?')[0];

    return (
        <nav aria-label="Account" className={cn('no-scrollbar flex overflow-x-auto border-b border-line', className)}>
            {tabs.map(([label, name, icon, prefixes]) => {
                const active = prefixes.some((prefix) => (prefix === '/account' ? path === prefix : path.startsWith(prefix)));
                return (
                    <Link key={name} href={route(name)} aria-current={active ? 'page' : undefined}
                        className={cn('relative flex shrink-0 items-center gap-2 px-4 pb-4 pt-2 text-[11px] font-semibold uppercase tracking-[.08em] transition [font-stretch:115%]', active ? 'text-paper' : 'text-mute hover:text-paper')}>
                        <Icon name={icon} size={15} /> {label}
                        {active && <span className="absolute inset-x-3 -bottom-px h-[2px] bg-volt" />}
                    </Link>
                );
            })}
        </nav>
    );
}

/** Account page header: breadcrumbs, title, subtitle and the tab bar. */
export function AccountHeader({ label, title, accent, children, action, nav = true }: { label?: string; title: string; accent?: string[]; children?: ReactNode; action?: ReactNode; nav?: boolean }) {
    return (
        <section className="pb-10 pt-[calc(var(--header)+2.5rem)]">
            <div className="shell">
                <Breadcrumbs className="mb-10" />
                <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        {label && <p className="label label-accent">{label}</p>}
                        <SplitHeading as="h1" text={title} accent={accent} className="mt-4 text-[clamp(3.5rem,10vw,8.5rem)]" />
                        {children && <p className="lede mt-4">{children}</p>}
                    </div>
                    {action}
                </div>
                {nav && <AccountNav className="mt-12" />}
            </div>
        </section>
    );
}

export function BookingRow({ booking }: { booking: BookingSummary }) {
    const tone = { mint: 'tag-mint', volt: 'tag-volt', signal: 'tag-signal', '': '' }[booking.tone];

    return (
        <article className="panel group grid grid-cols-[4.5rem_1fr] gap-5 p-5 transition-colors hover:border-line-2 sm:grid-cols-[5.5rem_1fr_auto] sm:items-center">
            <Poster movie={booking.movie} size="sm" meta={false} />
            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                    <span className={cn('tag', tone)}>{booking.state}</span>
                    <span className="num text-[11px] text-mute">{booking.number}</span>
                </div>
                <h3 className="headline mt-2 truncate text-3xl">{booking.title}</h3>
                <p className="mt-1 text-sm text-mute">{booking.starts} · {booking.theater}</p>
                <p className="num mt-1 text-[11px] text-mute">{plural(booking.seat_count, 'seat')}: {booking.seats.join(', ')} · {money(booking.total)}</p>
            </div>
            <div className="col-span-2 flex gap-2 sm:col-span-1 sm:flex-col">
                <Link href={route('user.booking.show', booking.number)} className="btn btn-light btn-sm flex-1">E-ticket</Link>
                <Link href={route('user.tracking', booking.number)} className="btn btn-ghost btn-sm flex-1">Track</Link>
            </div>
        </article>
    );
}

export function CheckoutSteps({ step }: { step: 1 | 2 | 3 }) {
    return (
        <ol className="label mb-8 flex items-center gap-3" aria-label="Checkout steps">
            {['Seats', 'Review', 'Confirm'].map((name, index) => (
                <li key={name} className="flex items-center gap-3" aria-current={step === index + 1 ? 'step' : undefined}>
                    {index > 0 && <span className="h-px w-8 bg-line-2" aria-hidden="true" />}
                    <span className={cn(step === index + 1 ? 'text-accent' : step > index + 1 ? 'text-paper-2' : 'text-dim')}><span className="num">0{index + 1}</span> {name}</span>
                </li>
            ))}
        </ol>
    );
}
