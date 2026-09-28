import { Link, router } from '@inertiajs/react';
import { AccountHeader, BookingRow } from '@/components/account';
import Icon from '@/components/Icon';
import MovieCard from '@/components/MovieCard';
import Poster from '@/components/Poster';
import { useCountdown } from '@/components/ui';
import { cn, money, plural, route, useShared } from '@/lib/utils';
import type { BookingSummary, MovieCard as Movie } from '@/types';

interface Props {
    greeting: string;
    memberSince: string | null;
    stats: { bookings: number; upcoming: number; spent: number; tickets: number; wishlist: number; cart: number; reviews: number };
    next: BookingSummary | null;
    upcoming: BookingSummary[];
    cartExpiresAt: string | null;
    recommended: Movie[];
    notifications: { id: number; title: string; message: string; read: boolean; ago: string }[];
}

export default function Dashboard({ greeting, memberSince, stats, next, upcoming, cartExpiresAt, recommended, notifications }: Props) {
    const { auth } = useShared();
    const hold = useCountdown(stats.cart > 0 ? cartExpiresAt : null, () => router.reload());

    return (
        <>
            <AccountHeader label={greeting} title={`${auth.user?.first_name ?? 'Hello'}.`}
                action={<Link href={route('movies.index')} className="btn btn-primary">Book a film <Icon name="arrow-right" size={16} className="arrow" /></Link>}>
                Member since {memberSince} · {plural(stats.tickets, 'ticket')} booked
            </AccountHeader>

            <div className="shell space-y-20">
                {stats.cart > 0 && cartExpiresAt && hold.remaining > 0 && (
                    <div className="alert alert-info items-center justify-between">
                        <span className="flex items-center gap-3"><Icon name="clock" size={18} /> You have {plural(stats.cart, 'seat')} on hold for <strong className="num">{hold.label}</strong>.</span>
                        <Link href={route('user.checkout')} className="btn btn-primary btn-sm">Finish checkout</Link>
                    </div>
                )}

                <section className="grid gap-6 lg:grid-cols-12" aria-labelledby="next-show">
                    <div className="lg:col-span-8">
                        <h2 id="next-show" className="label">Your next show</h2>
                        {next ? (
                            <Link href={route('user.booking.show', next.number)} className="ticket group mt-4 grid gap-6 p-7 transition-colors hover:border-accent sm:grid-cols-[9rem_1fr]" style={{ ['--tear' as string]: '50%' }}>
                                <Poster movie={next.movie} size="sm" />
                                <div className="flex flex-col">
                                    <p className="label label-accent">{next.relative}</p>
                                    <h3 className="display mt-3 text-7xl">{next.title}</h3>
                                    <dl className="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                                        <div><dt className="label">When</dt><dd className="num mt-1">{next.starts}</dd></div>
                                        <div><dt className="label">Cinema</dt><dd className="mt-1">{next.theater}</dd></div>
                                        <div><dt className="label">Seats</dt><dd className="num mt-1">{next.seats.join(' ')}</dd></div>
                                    </dl>
                                    <p className={cn('mt-auto pt-6 text-sm', next.paid ? 'text-mint' : 'text-accent')}>
                                        {next.paid ? 'Paid. Just show your e-ticket at the door.' : `Pay ${money(next.total)} at the counter, 20 minutes before.`}
                                    </p>
                                </div>
                            </Link>
                        ) : (
                            <div className="panel mt-4 flex flex-col items-start gap-5 p-10">
                                <p className="headline text-4xl">No shows on the calendar</p>
                                <p className="text-mute">Something good is always playing. Pick a film and a seat in under a minute.</p>
                                <Link href={route('movies.index')} className="btn btn-light">See what’s on</Link>
                            </div>
                        )}
                    </div>

                    <dl className="grid-lines grid-cols-2 self-end lg:col-span-4">
                        {([['Upcoming', stats.upcoming, route('user.bookings')], ['All bookings', stats.bookings, route('user.bookings')], ['Watchlist', stats.wishlist, route('user.wishlist')], ['Reviews', stats.reviews, null]] as [string, number, string | null][]).map(([label, value, href]) => (
                            <div key={label} className="p-6">
                                <dt className="label">{label}</dt>
                                <dd className="display mt-2 text-6xl">{href ? <Link href={href} className="hover:text-accent">{value}</Link> : value}</dd>
                            </div>
                        ))}
                        <div className="col-span-2 p-6">
                            <dt className="label">Spent on cinema</dt>
                            <dd className="display mt-2 text-6xl tabular">{money(stats.spent)}</dd>
                        </div>
                    </dl>
                </section>

                {upcoming.length > 0 && (
                    <section aria-labelledby="upcoming">
                        <div className="flex items-end justify-between">
                            <h2 id="upcoming" className="headline text-4xl">Also coming up</h2>
                            <Link href={route('user.bookings')} className="link text-sm text-paper-2">All upcoming</Link>
                        </div>
                        <div className="mt-6 grid gap-3">{upcoming.map((booking) => <BookingRow key={booking.number} booking={booking} />)}</div>
                    </section>
                )}

                <section className="grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-4" aria-labelledby="inbox">
                        <h2 id="inbox" className="headline text-4xl">Inbox</h2>
                        <ul className="mt-6 space-y-2">
                            {notifications.length ? notifications.map((note) => (
                                <li key={note.id} className="panel flex gap-4 p-5">
                                    <span className={cn('mt-1.5 h-2 w-2 shrink-0', note.read ? 'bg-dim' : 'bg-volt')} aria-label={note.read ? 'Read' : 'Unread'} />
                                    <div>
                                        <p className="font-semibold">{note.title}</p>
                                        <p className="mt-1 text-sm text-mute">{note.message}</p>
                                        <p className="num mt-2 text-[10px] text-dim">{note.ago}</p>
                                    </div>
                                </li>
                            )) : <li className="text-sm text-mute">No messages. We only write when it matters.</li>}
                        </ul>
                    </div>
                    <div className="lg:col-span-8" aria-labelledby="recommended">
                        <h2 id="recommended" className="headline text-4xl">Picked for you</h2>
                        <div className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">{recommended.map((movie) => <MovieCard key={movie.id} movie={movie} />)}</div>
                    </div>
                </section>
            </div>
        </>
    );
}
