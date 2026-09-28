import { Link, router } from '@inertiajs/react';
import { AccountHeader } from '@/components/account';
import Icon from '@/components/Icon';
import MovieCard from '@/components/MovieCard';
import PushToggle from '@/components/PushToggle';
import { EmptyState } from '@/components/ui';
import { plural, route } from '@/lib/utils';
import type { MovieCard as Movie } from '@/types';

export default function Wishlist({ movies }: { movies: Movie[] }) {
    return (
        <>
            <AccountHeader title="Your watchlist" accent={['watchlist']}>{plural(movies.length, 'film')} saved. Films that are playing show their next showtime.</AccountHeader>
            <div className="shell">
                <div className="mb-10 flex flex-col gap-3 border border-line p-5 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-paper-2"><Icon name="megaphone" size={15} className="me-2 inline text-accent" />We tell you the moment a saved film opens for booking, here in your inbox and, if you like, as a browser alert.</p>
                    <PushToggle className="shrink-0" />
                </div>
                {movies.length === 0 ? (
                    <EmptyState icon="heart" title="Nothing saved yet" action={<Link href={route('movies.status', 'coming-soon')} className="btn btn-primary">See what’s coming</Link>}>
                        Tap “Add to watchlist” on any film page and it will wait for you here.
                    </EmptyState>
                ) : (
                    <div className="grid grid-cols-2 gap-x-4 gap-y-14 sm:grid-cols-3 lg:grid-cols-4">
                        {movies.map((movie) => (
                            <div key={movie.id} className="flex flex-col">
                                <MovieCard movie={movie} className="flex-1" />
                                <div className="mt-4 flex gap-2">
                                    {movie.first_show_id && <Link href={`${route('movies.show', movie.slug)}#showtimes`} className="btn btn-light btn-sm flex-1">Book</Link>}
                                    <button type="button" onClick={() => router.delete(route('user.wishlist.remove', movie.id), { preserveScroll: true })}
                                        className="btn btn-ghost btn-sm flex-1" aria-label={`Remove ${movie.title} from watchlist`}>
                                        <Icon name="trash" size={15} /> Remove
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
