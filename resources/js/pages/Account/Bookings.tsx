import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { AccountHeader, BookingRow } from '@/components/account';
import Icon from '@/components/Icon';
import { EmptyState } from '@/components/ui';
import { plural, route } from '@/lib/utils';
import type { BookingSummary } from '@/types';

type Row = BookingSummary & { group: 'upcoming' | 'past' | 'cancelled' };
const PAGE = 10;

export default function Bookings({ bookings }: { bookings: Row[] }) {
    const [filter, setFilter] = useState<Row['group'] | null>(null);
    const [limit, setLimit] = useState(PAGE);
    const visible = filter ? bookings.filter((booking) => booking.group === filter) : bookings;
    const cancelled = bookings.filter((booking) => booking.group === 'cancelled').length;

    return (
        <>
            <AccountHeader title="Your bookings" accent={['bookings']}>{plural(bookings.length, 'booking')} · {cancelled} cancelled</AccountHeader>
            <div className="shell">
                <nav className="flex flex-wrap gap-1" aria-label="Filter bookings">
                    {([[null, 'All'], ['upcoming', 'Upcoming'], ['past', 'Past'], ['cancelled', 'Cancelled']] as [Row['group'] | null, string][]).map(([key, label]) => (
                        <button key={label} type="button" onClick={() => { setFilter(key); setLimit(PAGE); }} aria-pressed={filter === key} className="chip">
                            {label} <span className="num opacity-60">{key ? bookings.filter((booking) => booking.group === key).length : bookings.length}</span>
                        </button>
                    ))}
                </nav>
                <div className="mt-8 grid gap-3">
                    {visible.length ? visible.slice(0, limit).map((booking) => <BookingRow key={booking.number} booking={booking} />) : (
                        <EmptyState icon="ticket" title={filter ? 'Nothing here' : 'Your first ticket awaits'} action={<Link href={route('movies.index')} className="btn btn-primary">Find a film</Link>}>
                            {filter ? 'No bookings match this filter.' : 'When you book, your e-tickets and receipts will live here.'}
                        </EmptyState>
                    )}
                </div>
                {visible.length > limit && (
                    <div className="mt-10 flex justify-center">
                        <button type="button" onClick={() => setLimit(limit + PAGE)} className="btn btn-ghost">Show more <Icon name="arrow-down" size={16} /></button>
                    </div>
                )}
            </div>
        </>
    );
}
