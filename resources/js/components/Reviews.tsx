import { Link, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import Avatar from '@/components/Avatar';
import Icon from '@/components/Icon';
import { SectionHeading, StarRating } from '@/components/ui';
import { lockScroll } from '@/lib/scroll';
import { cn, plural, route, useShared } from '@/lib/utils';

export type Photo = { id: number; url: string; thumb: string; width: number; height: number };

export type ReviewItem = {
    id: number; author: string; username: string | null; avatar: string | null; city: string | null; member_since: string | null;
    rating: number; title: string | null; text: string; spoilers: boolean; verified: boolean; watched: string | null; watched_on: string | null;
    helpful: number; voted: boolean; ago: string | null; date: string | null;
    photos?: Photo[];
};

export type ReviewPage = { items: ReviewItem[]; next: number | null; total: number };

export type ReviewSummary = { average: number; total: number; verified_pct: number; recommend_pct: number; distribution: { stars: number; count: number }[] };

const SORTS = [['recent', 'Newest'], ['helpful', 'Most helpful'], ['highest', 'Highest'], ['lowest', 'Lowest']] as const;
const ease = [0.16, 1, 0.3, 1] as const;

/** One review card: who, where they watched it, what they said. */
export function ReviewCard({ review, film }: { review: ReviewItem; film?: { title: string; slug: string } }) {
    const { auth } = useShared();
    const [revealed, setRevealed] = useState(!review.spoilers);
    const [expanded, setExpanded] = useState(false);
    const [helpful, setHelpful] = useState({ count: review.helpful, voted: review.voted, busy: false });
    const long = review.text.length > 320;
    const own = auth.user?.username && auth.user.username === review.username;

    const vote = () => {
        if (!auth.user) { router.visit(route('user.login')); return; }
        setHelpful((state) => ({ ...state, busy: true, voted: !state.voted, count: state.count + (state.voted ? -1 : 1) }));
        axios.post(route('reviews.helpful', review.id))
            .then(({ data }) => setHelpful({ count: data.helpful, voted: data.voted, busy: false }))
            .catch(() => setHelpful({ count: review.helpful, voted: review.voted, busy: false }));
    };

    return (
        <motion.article layout initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, ease }} className="border-t border-line py-8">
            <header className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex min-w-0 items-center gap-3">
                    <Avatar name={review.author} seed={review.username} src={review.avatar} size={44} />
                    <div className="min-w-0">
                        <p className="flex flex-wrap items-center gap-x-2 text-sm">
                            {review.username ? <Link href={route('profile.show', review.username)} className="link font-semibold">{review.author}</Link> : <span className="font-semibold">{review.author}</span>}
                            {review.username && <span className="num text-xs text-mute">@{review.username}</span>}
                        </p>
                        <p className="mt-0.5 text-xs text-mute">{[review.city, review.member_since && `member since ${review.member_since}`].filter(Boolean).join(' · ')}</p>
                    </div>
                </div>
                <div className="text-right">
                    <StarRating rating={review.rating} />
                    <p className="mt-1 text-xs text-mute"><time dateTime={review.date ?? undefined}>{review.ago}</time></p>
                </div>
            </header>

            <div className="mt-4 flex flex-wrap gap-1.5">
                {review.verified
                    ? <span className="tag tag-mint" title="Written after a BookMyMovie booking for this film that has already been screened"><Icon name="check" size={11} /> Verified booking</span>
                    : <span className="tag" title="Posted before verified reviews, or not linked to a booking">Not verified</span>}
                {review.watched && <span className="tag">{review.watched}{review.watched_on && <> · {review.watched_on}</>}</span>}
                {film && <Link href={route('movies.show', film.slug)} className="tag transition hover:border-accent hover:text-accent">{film.title}</Link>}
            </div>

            {review.title && <h3 className="headline mt-5 text-2xl">{review.title}</h3>}
            <div className="relative mt-3">
                <p className={cn('max-w-2xl text-lg leading-relaxed text-paper-2 transition-[filter] duration-500', !revealed && 'select-none blur-md', long && !expanded && 'line-clamp-4')}>{review.text}</p>
                {!revealed && (
                    <button type="button" onClick={() => setRevealed(true)} className="absolute inset-0 grid place-items-center">
                        <span className="btn btn-ghost btn-sm bg-ink/80 backdrop-blur"><Icon name="eye" size={14} /> Contains spoilers · show</span>
                    </button>
                )}
            </div>
            {long && revealed && <button type="button" onClick={() => setExpanded(!expanded)} className="link mt-2 text-sm text-accent">{expanded ? 'Show less' : 'Read the whole review'}</button>}
            {review.photos && review.photos.length > 0 && <PhotoStrip photos={review.photos} blurred={!revealed} author={review.author} />}

            <footer className="mt-5 flex items-center gap-3 text-xs text-mute">
                {!own && (
                    <button type="button" onClick={vote} disabled={helpful.busy} aria-pressed={helpful.voted}
                        className={cn('inline-flex items-center gap-2 border px-3 py-1.5 transition', helpful.voted ? 'border-accent text-accent' : 'border-line-2 hover:border-paper hover:text-paper')}>
                        <motion.span key={String(helpful.voted)} initial={{ scale: 0.6 }} animate={{ scale: 1 }} transition={{ type: 'spring', stiffness: 500, damping: 18 }}><Icon name="check" size={13} /></motion.span>
                        Helpful
                    </button>
                )}
                <span className="num">{helpful.count > 0 ? `${helpful.count} ${helpful.count === 1 ? 'person' : 'people'} found this helpful` : ''}</span>
            </footer>
        </motion.article>
    );
}

export default function ReviewsSection({ movie, initial, summary, canReview, userReview }: {
    movie: { slug: string; title: string; status_key: string };
    initial: ReviewPage;
    summary: ReviewSummary;
    canReview: boolean;
    userReview: { rating: number; title: string; text: string; spoilers: boolean } | null;
}) {
    const { auth } = useShared();
    const [page, setPage] = useState(initial);
    const [sort, setSort] = useState<(typeof SORTS)[number][0]>('recent');
    const [stars, setStars] = useState<number | null>(null);
    const [verified, setVerified] = useState(false);
    const [loading, setLoading] = useState(false);
    const first = useRef(true);

    const load = (pageNumber: number, append: boolean) => {
        setLoading(true);
        axios.get(route('movies.reviews', movie.slug), { params: { sort, stars: stars ?? undefined, verified: verified ? 1 : undefined, page: pageNumber } })
            .then(({ data }: { data: ReviewPage }) => setPage((current) => (append ? { ...data, items: [...current.items, ...data.items] } : data)))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        if (first.current) { first.current = false; return; }
        load(1, false);
    }, [sort, stars, verified]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <section id="reviews" className="scroll-mt-40 pt-28" aria-labelledby="reviews-title">
            <div className="shell grid gap-12 lg:grid-cols-12">
                <aside className="lg:col-span-4">
                    <div className="lg:sticky lg:top-40">
                        <SectionHeading label="From people who were there" title="Reviews" id="reviews-title" />
                        {summary.total > 0 && (
                            <div className="panel mt-10 p-7">
                                <div className="flex items-end gap-4">
                                    <span className="display text-8xl tabular">{summary.average.toFixed(1)}</span>
                                    <div className="pb-2">
                                        <StarRating rating={summary.average} size={16} />
                                        <p className="mt-1 text-sm text-mute">{plural(summary.total, 'review')}</p>
                                    </div>
                                </div>
                                <ul className="mt-6 space-y-2" aria-label="Filter by rating">
                                    {summary.distribution.map(({ stars: level, count }) => (
                                        <li key={level}>
                                            <button type="button" onClick={() => setStars(stars === level ? null : level)} aria-pressed={stars === level}
                                                className={cn('flex w-full items-center gap-3 text-xs transition', stars === level ? 'text-accent' : 'text-mute hover:text-paper')}>
                                                <span className="num w-3">{level}</span>
                                                <span className="h-1.5 flex-1 overflow-hidden bg-line"><motion.span className="block h-full bg-volt" initial={{ width: 0 }} whileInView={{ width: `${summary.total ? (count / summary.total) * 100 : 0}%` }} viewport={{ once: true }} transition={{ duration: 1.2, ease }} /></span>
                                                <span className="num w-8 text-right">{count}</span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                                <dl className="mt-6 grid grid-cols-2 gap-px border border-line bg-line text-center">
                                    <div className="bg-ink-2 p-4"><dt className="label">Would recommend</dt><dd className="display mt-2 text-4xl">{summary.recommend_pct}%</dd></div>
                                    <div className="bg-ink-2 p-4"><dt className="label">Verified</dt><dd className="display mt-2 text-4xl">{summary.verified_pct}%</dd></div>
                                </dl>
                            </div>
                        )}
                        <details className="mt-6 text-sm text-mute">
                            <summary className="cursor-pointer text-paper-2 hover:text-paper">What does “Verified booking” mean?</summary>
                            <p className="mt-3 leading-relaxed">The reviewer booked this film on BookMyMovie and the show had already started when they wrote it. We link the review to that booking, so the badge can't be added by hand. Reviews without it were posted before we started linking bookings.</p>
                        </details>
                    </div>
                </aside>

                <div className="lg:col-span-8">
                    {canReview ? <ReviewForm slug={movie.slug} existing={userReview} /> : auth.user && movie.status_key !== 'coming_soon' && (
                        <p className="panel mb-10 flex items-center gap-3 p-5 text-sm text-mute"><Icon name="ticket" size={18} className="text-accent" /> You can review this film after your show has started.</p>
                    )}

                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4">
                        <div className="flex flex-wrap gap-1" role="group" aria-label="Sort reviews">
                            {SORTS.map(([key, label]) => (
                                <button key={key} type="button" onClick={() => setSort(key)} aria-pressed={sort === key} className={'chip'}>{label}</button>
                            ))}
                        </div>
                        <label className="flex cursor-pointer items-center gap-2 text-sm text-mute">
                            <input type="checkbox" className="checkbox" checked={verified} onChange={(event) => setVerified(event.target.checked)} /> Verified only
                        </label>
                    </div>
                    <p className="num mt-4 text-xs text-mute" aria-live="polite">{loading ? 'Loading…' : `${page.total} ${page.total === 1 ? 'review' : 'reviews'}${stars ? ` with ${stars} stars` : ''}`}</p>

                    <div className={cn('transition-opacity', loading && 'opacity-50')}>
                        <AnimatePresence initial={false}>
                            {page.items.map((review) => <ReviewCard key={review.id} review={review} />)}
                        </AnimatePresence>
                    </div>

                    {page.items.length === 0 && !loading && (
                        <div className="panel mt-6 p-10 text-center">
                            <Icon name="quote" size={32} className="mx-auto text-dim" />
                            <p className="headline mt-4 text-3xl">{summary.total ? 'Nothing matches those filters' : 'No reviews yet'}</p>
                            <p className="mt-2 text-sm text-mute">{summary.total ? 'Try another star rating or sort order.' : movie.status_key === 'coming_soon' ? 'Reviews open after the first screenings.' : 'Book a seat and be the first to tell everyone what you thought.'}</p>
                        </div>
                    )}

                    {page.next && (
                        <button type="button" disabled={loading} onClick={() => load(page.next ?? 1, true)} className="btn btn-ghost mt-6 w-full">
                            {loading ? 'Loading…' : `Show more reviews (${page.total - page.items.length} left)`}
                        </button>
                    )}
                </div>
            </div>
        </section>
    );
}

function ReviewForm({ slug, existing }: { slug: string; existing: { rating: number; title: string; text: string; spoilers: boolean; photos?: Photo[] } | null }) {
    const form = useForm<{ rating: number; title: string; review_text: string; contains_spoilers: boolean; photos: File[]; remove_photos: number[] }>({ rating: existing?.rating ?? 0, title: existing?.title ?? '', review_text: existing?.text ?? '', contains_spoilers: existing?.spoilers ?? false, photos: [], remove_photos: [] });
    const [hover, setHover] = useState(0);
    const words = ['', 'Poor', 'Not great', 'Good', 'Great', 'Loved it'];

    return (
        <form onSubmit={(event) => { event.preventDefault(); form.post(route('movies.reviews.store', slug), { preserveScroll: true, forceFormData: true, onSuccess: () => form.setData({ ...form.data, photos: [], remove_photos: [] }) }); }} className="panel mb-10 p-7">
            <p className="headline text-3xl">{existing ? 'Update your review' : 'You saw it. What did you think?'}</p>
            <fieldset className="mt-5">
                <legend className="label">Your rating</legend>
                <div className="mt-2 flex items-center gap-1" onMouseLeave={() => setHover(0)}>
                    {[1, 2, 3, 4, 5].map((star) => (
                        <label key={star} className="cursor-pointer" onMouseEnter={() => setHover(star)}>
                            <input type="radio" name="rating" value={star} className="sr-only" checked={form.data.rating === star} onChange={() => form.setData('rating', star)} />
                            <motion.span className="block" whileHover={{ scale: 1.15 }} whileTap={{ scale: 0.9 }}>
                                <Icon name="star" size={30} className={cn('transition', (hover || form.data.rating) >= star ? 'fill-accent text-accent' : 'text-dim')} />
                            </motion.span>
                            <span className="sr-only">{plural(star, 'star')}</span>
                        </label>
                    ))}
                    <span className="label ms-3 text-paper">{words[hover || form.data.rating]}</span>
                </div>
                {form.errors.rating && <p className="field-error mt-2">{form.errors.rating}</p>}
            </fieldset>
            <div className="field mt-5">
                <label htmlFor="review_title" className="label">Headline</label>
                <input id="review_title" className="input" maxLength={90} placeholder="Sum it up in a few words" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} />
            </div>
            <div className="field mt-5">
                <label htmlFor="review_text" className="label">Your review</label>
                <textarea id="review_text" className="input min-h-36" minLength={20} maxLength={1200} required placeholder="What stayed with you? The acting, the sound, the ending?"
                    value={form.data.review_text} onChange={(event) => form.setData('review_text', event.target.value)} />
                <p className="field-hint num text-right">{form.data.review_text.length}/1200</p>
                {form.errors.review_text && <p className="field-error">{form.errors.review_text}</p>}
            </div>
            <PhotoPicker existing={(existing?.photos ?? []).filter((photo) => !form.data.remove_photos.includes(photo.id))} files={form.data.photos}
                onFiles={(files) => form.setData('photos', files)} onRemoveExisting={(id) => form.setData('remove_photos', [...form.data.remove_photos, id])}
                error={Object.entries(form.errors).find(([key]) => key.startsWith('photos'))?.[1]} />
            <label className="mt-4 flex cursor-pointer items-center gap-3 text-sm text-paper-2">
                <input type="checkbox" className="checkbox" checked={form.data.contains_spoilers} onChange={(event) => form.setData('contains_spoilers', event.target.checked)} />
                My review gives away the plot (it will be blurred until someone taps it)
            </label>
            <button type="submit" disabled={form.processing} className="btn btn-primary mt-6">{form.processing ? 'Publishing…' : existing ? 'Update review' : 'Publish review'}</button>
        </form>
    );
}

/** Thumbnails under a review; tap opens a full-screen viewer (arrows, Esc). */
function PhotoStrip({ photos, blurred, author }: { photos: Photo[]; blurred: boolean; author: string }) {
    const [open, setOpen] = useState<number | null>(null);

    useEffect(() => {
        if (open === null) return;
        lockScroll(true);
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpen(null);
            if (event.key === 'ArrowRight') setOpen((index) => (index === null ? null : (index + 1) % photos.length));
            if (event.key === 'ArrowLeft') setOpen((index) => (index === null ? null : (index - 1 + photos.length) % photos.length));
        };
        window.addEventListener('keydown', onKey);
        return () => { window.removeEventListener('keydown', onKey); lockScroll(false); };
    }, [open, photos.length]);

    return (
        <>
            <div className="mt-4 flex gap-2">
                {photos.map((photo, index) => (
                    <button key={photo.id} type="button" onClick={() => !blurred && setOpen(index)} className="group relative h-24 w-24 overflow-hidden border border-line sm:h-28 sm:w-28" aria-label={`Open photo ${index + 1} of ${photos.length} from ${author}`}>
                        <img src={photo.thumb} alt="" loading="lazy" decoding="async" className={cn('h-full w-full object-cover transition duration-500 group-hover:scale-105', blurred && 'blur-md')} />
                    </button>
                ))}
            </div>
            {createPortal(
                <AnimatePresence>
                    {open !== null && (
                        <motion.div role="dialog" aria-modal="true" aria-label={`Photo from ${author}`} data-lenis-prevent initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
                            className="fixed inset-0 z-[120] grid place-items-center bg-ink/95 p-4 backdrop-blur" onClick={() => setOpen(null)}>
                            <motion.img key={photos[open].id} src={photos[open].url} alt={`Photo ${open + 1} from ${author}`} initial={{ scale: 0.96, opacity: 0 }} animate={{ scale: 1, opacity: 1 }}
                                className="max-h-[85svh] max-w-full object-contain" onClick={(event) => event.stopPropagation()} />
                            <button type="button" className="btn btn-ghost btn-icon absolute right-4 top-4" onClick={() => setOpen(null)} aria-label="Close"><Icon name="close" size={18} /></button>
                            {photos.length > 1 && <p className="num absolute bottom-6 text-xs text-mute">{open + 1} / {photos.length} · ← →</p>}
                        </motion.div>
                    )}
                </AnimatePresence>,
                document.body,
            )}
        </>
    );
}

/** Up to three photos: drop or pick, previewed before upload, removable. */
function PhotoPicker({ existing, files, onFiles, onRemoveExisting, error }: {
    existing: Photo[]; files: File[]; onFiles: (files: File[]) => void; onRemoveExisting: (id: number) => void; error?: string;
}) {
    const [drag, setDrag] = useState(false);
    const room = 3 - existing.length - files.length;
    const previews = useMemo(() => files.map((file) => URL.createObjectURL(file)), [files]);
    useEffect(() => () => previews.forEach((url) => URL.revokeObjectURL(url)), [previews]);

    const add = (list: FileList | null) => {
        if (!list) return;
        const images = Array.from(list).filter((file) => /^image\/(jpeg|png|webp)$/.test(file.type) && file.size <= 5 * 1024 * 1024);
        onFiles([...files, ...images].slice(0, 3 - existing.length));
    };

    return (
        <div className="field mt-5">
            <span className="label">Photos <span className="text-dim">(optional, up to 3)</span></span>
            <div className="flex flex-wrap gap-2">
                {existing.map((photo) => (
                    <span key={photo.id} className="relative h-24 w-24 border border-line">
                        <img src={photo.thumb} alt="" className="h-full w-full object-cover" />
                        <button type="button" onClick={() => onRemoveExisting(photo.id)} className="absolute right-1 top-1 grid h-6 w-6 place-items-center bg-ink/80 text-paper hover:bg-signal" aria-label="Remove photo"><Icon name="close" size={12} /></button>
                    </span>
                ))}
                {previews.map((url, index) => (
                    <span key={url} className="relative h-24 w-24 border border-accent">
                        <img src={url} alt="" className="h-full w-full object-cover" />
                        <button type="button" onClick={() => onFiles(files.filter((_, position) => position !== index))} className="absolute right-1 top-1 grid h-6 w-6 place-items-center bg-ink/80 text-paper hover:bg-signal" aria-label="Remove photo"><Icon name="close" size={12} /></button>
                    </span>
                ))}
                {room > 0 && (
                    <label onDragOver={(event) => { event.preventDefault(); setDrag(true); }} onDragLeave={() => setDrag(false)}
                        onDrop={(event) => { event.preventDefault(); setDrag(false); add(event.dataTransfer.files); }}
                        className={cn('grid h-24 w-24 cursor-pointer place-items-center border border-dashed text-center text-[10px] uppercase tracking-[.08em] transition', drag ? 'border-accent text-accent' : 'border-line-2 text-mute hover:border-paper hover:text-paper')}>
                        <span><Icon name="plus" size={16} className="mx-auto mb-1" />Add photo</span>
                        <input type="file" accept="image/jpeg,image/png,image/webp" multiple className="sr-only" onChange={(event) => { add(event.target.files); event.target.value = ''; }} />
                    </label>
                )}
            </div>
            <p className="field-hint">JPG, PNG or WebP, 5 MB each. Location data is removed before anything is saved.</p>
            {error && <p className="field-error">{error}</p>}
        </div>
    );
}
