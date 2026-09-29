import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/components/Icon';
import { Field, Pagination } from '@/components/ui';
import AdminLayout, { Guide } from '@/layouts/AdminLayout';
import { cn, route } from '@/lib/utils';
import type { PageLink } from '@/types';

type Row = {
    id: number; film: string | null; film_slug: string | null; author: string | null; username: string | null; user_id: number;
    author_banned: boolean; rating: number; title: string | null; text: string; approved: boolean; flagged: boolean; verified: boolean; helpful: number; at: string | null;
};

interface Props {
    reviews: { data: Row[]; links: PageLink[] };
    filters: { q: string; state: 'all' | 'pending' | 'flagged' | 'hidden'; stars: number | null };
    counts: { total: number; pending: number; flagged: number; verified: number };
}

export default function AdminReviews({ reviews, filters, counts }: Props) {
    const [q, setQ] = useState(filters.q);
    const go = (next: Partial<Props['filters']>) => router.get(route('admin.reviews'), { ...filters, q, ...next }, { preserveState: true, preserveScroll: true, replace: true });
    const act = (id: number, action: string) => router.post(route('admin.reviews.moderate', id), { action }, { preserveScroll: true });

    return (
        <AdminLayout label="Community" title="Reviews" lede="Every review on the site. Hidden reviews stop counting towards a film's rating straight away."
            guide={<Guide id="reviews" steps={[
                ['Start with “Waiting”', 'New reviews wait here until someone approves them.'],
                ['Approve or hide', 'Approved reviews show on the film page and count toward its star rating.'],
                ['Verified badge', 'Reviews from people who actually booked the film carry a verified mark.'],
                ['Ban abusive authors', 'Open the author to ban their account, IP and device in one go.'],
            ]} />}>
            <div className="grid-lines sm:grid-cols-4">
                {[['Reviews', counts.total], ['Verified bookings', counts.verified], ['Waiting', counts.pending], ['Flagged', counts.flagged]].map(([label, value]) => (
                    <div key={label} className="p-6"><p className="label">{label}</p><p className="display mt-3 text-5xl">{Number(value).toLocaleString('en-PK')}</p></div>
                ))}
            </div>

            <form onSubmit={(event) => { event.preventDefault(); go({}); }} className="flex flex-col gap-3 lg:flex-row lg:items-end">
                <Field label="Search" value={q} onChange={(event) => setQ(event.target.value)} placeholder="Words, @username or film" className="lg:max-w-md lg:flex-1" />
                <button type="submit" className="btn btn-primary">Search</button>
                <div className="flex flex-wrap gap-1 lg:ms-auto">
                    {(['all', 'pending', 'flagged', 'hidden'] as const).map((state) => (
                        <button key={state} type="button" onClick={() => go({ state })} aria-pressed={filters.state === state} className={'chip capitalize'}>{state}</button>
                    ))}
                    {[5, 4, 3, 2, 1].map((stars) => (
                        <button key={stars} type="button" onClick={() => go({ stars: filters.stars === stars ? null : stars })} aria-pressed={filters.stars === stars} className={'chip'}>{stars}★</button>
                    ))}
                </div>
            </form>

            <ul className="space-y-3">
                {reviews.data.map((review) => (
                    <li key={review.id} className={cn('panel p-5', !review.approved && 'opacity-70')}>
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div className="min-w-0 flex-1">
                                <p className="flex flex-wrap items-center gap-2 text-xs text-mute">
                                    <span className="num text-sm text-accent">{'★'.repeat(review.rating)}<span className="text-dim">{'★'.repeat(5 - review.rating)}</span></span>
                                    {review.film_slug ? <Link href={route('movies.show', review.film_slug)} className="link font-semibold text-paper">{review.film}</Link> : review.film}
                                    <span>· <Link href={route('admin.users.show', review.user_id)} className="link">@{review.username}</Link></span>
                                    {review.author_banned && <span className="tag tag-signal">Author banned</span>}
                                    <span>· {review.at}</span>
                                    {review.verified ? <span className="tag tag-mint">Verified booking</span> : <span className="tag">Unverified</span>}
                                    {review.flagged && <span className="tag tag-signal">Flagged</span>}
                                    {!review.approved && !review.flagged && <span className="tag tag-volt">Hidden</span>}
                                    <span>· {review.helpful} found helpful</span>
                                </p>
                                {review.title && <p className="headline mt-2 text-2xl">{review.title}</p>}
                                <p className="mt-2 text-sm leading-relaxed text-paper-2">{review.text}</p>
                            </div>
                            <div className="flex shrink-0 flex-wrap gap-1">
                                {review.approved
                                    ? <button type="button" onClick={() => act(review.id, 'hide')} className="btn btn-ghost btn-sm"><Icon name="eye-off" size={14} /> Hide</button>
                                    : <button type="button" onClick={() => act(review.id, 'approve')} className="btn btn-primary btn-sm"><Icon name="check" size={14} /> Approve</button>}
                                {review.flagged
                                    ? <button type="button" onClick={() => act(review.id, 'unflag')} className="btn btn-ghost btn-sm">Unflag</button>
                                    : <button type="button" onClick={() => act(review.id, 'flag')} className="btn btn-ghost btn-sm"><Icon name="alert" size={14} /> Flag</button>}
                                <button type="button" onClick={() => act(review.id, 'delete')} className="btn btn-ghost btn-sm hover:!border-signal hover:!text-signal"><Icon name="trash" size={14} /> Delete</button>
                            </div>
                        </div>
                    </li>
                ))}
                {reviews.data.length === 0 && <li className="panel p-10 text-center text-mute">No reviews match.</li>}
            </ul>
            <Pagination links={reviews.links} />
        </AdminLayout>
    );
}

AdminReviews.layout = null;
