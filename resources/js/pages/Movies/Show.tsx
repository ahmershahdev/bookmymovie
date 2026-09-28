import { Link, router, useForm } from '@inertiajs/react';
import { motion, useScroll, useTransform } from 'motion/react';
import { useRef, useState } from 'react';
import Icon from '@/components/Icon';
import MovieCard from '@/components/MovieCard';
import { Reveal, SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { Breadcrumbs, SectionHeading, StarRating } from '@/components/ui';
import { useCompare } from '@/lib/compare';
import { scrollToTarget } from '@/lib/scroll';
import { cn, money, plural, route, useShared } from '@/lib/utils';
import type { MovieCard as Movie } from '@/types';

type Credit = { name: string; initials: string; role: string; character: string | null };
type ShowSlot = { id: number; time: string; meridiem: string; format: string; screen: string; from_price: number; on_sale: boolean; fast: boolean };
type ShowDay = { date: string; weekday: string; day: string; month: string; venues: { city: string; name: string; slug: string; address: string; shows: ShowSlot[] }[] };

interface Props {
    movie: Movie & {
        description: string;
        content_advisory: string | null;
        certificate_label: string;
        release_long: string | null;
        release_short: string | null;
        studio: string;
        country: string;
        average_rating: number;
        genre_links: { name: string; slug: string }[];
    };
    showDays: ShowDay[];
    cities: string[];
    showCount: number;
    venueCount: number;
    reviews: { id: number; author: string; initial: string; rating: number; text: string; ago: string }[];
    totalReviews: number;
    distribution: { stars: number; count: number }[];
    similar: Movie[];
    primaryGenre: { name: string; slug: string } | null;
    directors: Credit[];
    writers: Credit[];
    crew: Credit[];
    cast: Credit[];
    inWishlist: boolean;
    canReview: boolean;
    userReview: { rating: number; text: string } | null;
}

export default function MovieShow(props: Props) {
    const { movie, showCount, totalReviews } = props;
    const { auth } = useShared();
    const compare = useCompare();
    const hero = useRef<HTMLElement>(null);
    const { scrollYProgress } = useScroll({ target: hero, offset: ['start start', 'end start'] });
    const posterY = useTransform(scrollYProgress, [0, 1], ['0%', '18%']);
    const titleY = useTransform(scrollYProgress, [0, 1], ['0%', '-30%']);
    const [ground, accent] = movie.palette;

    const toggleWishlist = () => {
        if (props.inWishlist) {
            router.delete(route('user.wishlist.remove', movie.id), { preserveScroll: true });
        } else {
            router.post(route('user.wishlist'), { movie_id: movie.id }, { preserveScroll: true });
        }
    };

    const sections: [string, string][] = [['showtimes', 'Showtimes'], ['story', 'Story'], ['cast', 'Cast & crew'], ['details', 'Details'], ['reviews', `Reviews (${totalReviews})`]];
    if (props.similar.length) sections.push(['similar', 'More like this']);

    return (
        <>
            {/* Hero ------------------------------------------------------------ */}
            <section ref={hero} className="relative isolate overflow-hidden pb-16 pt-[calc(var(--header)+2.5rem)]">
                <div className="absolute inset-0 -z-10" aria-hidden="true"
                    style={{ background: `radial-gradient(55% 70% at 22% 35%, color-mix(in oklab, ${accent} 20%, transparent), transparent 70%), radial-gradient(60% 60% at 90% 0%, color-mix(in oklab, ${ground} 80%, transparent), transparent 70%), var(--color-ink)` }} />
                {movie.hero_image_url && movie.hero_image_url !== movie.poster_url && (
                    <img src={movie.hero_image_url} alt="" className="absolute inset-0 -z-10 h-full w-full object-cover opacity-20 mix-blend-luminosity" aria-hidden="true" />
                )}
                <div className="absolute inset-x-0 bottom-0 -z-10 h-48 bg-gradient-to-b from-transparent to-ink" aria-hidden="true" />

                <div className="shell">
                    <Breadcrumbs className="mb-10" />
                    <div className="grid gap-10 lg:grid-cols-12 lg:gap-14">
                        <motion.div style={{ y: posterY }} className="mx-auto w-full max-w-xs lg:col-span-4 lg:max-w-none">
                            <Poster movie={movie} size="lg" eager className="shadow-[0_60px_120px_-40px_rgba(0,0,0,.95)]" />
                        </motion.div>

                        <motion.div style={{ y: titleY }} className="flex flex-col justify-end lg:col-span-8">
                            <div className="flex flex-wrap gap-1.5">
                                <span className={cn('tag', movie.status_key === 'now_showing' ? 'tag-mint' : 'tag-volt')}>{movie.status}</span>
                                <span className="tag" title={movie.certificate_label}>{movie.certificate}</span>
                                {movie.genre_links.map((genre) => (
                                    <Link key={genre.slug} href={route('movies.genre', genre.slug)} className="tag transition hover:border-accent hover:text-accent">{genre.name}</Link>
                                ))}
                            </div>

                            <SplitHeading as="h1" text={movie.title} className="mt-6 text-[clamp(4rem,12vw,11.5rem)] leading-[.8]" />
                            {movie.tagline && <p className="lede mt-6 max-w-2xl text-xl sm:text-2xl">{movie.tagline}</p>}

                            <dl className="grid-lines mt-10 max-w-3xl grid-cols-2 sm:grid-cols-4">
                                {[
                                    ['Runtime', movie.duration],
                                    ['Language', movie.language],
                                    [movie.status_key === 'coming_soon' ? 'Opens' : 'Released', movie.release_date ?? '—'],
                                    ['Rating', totalReviews ? `${movie.average_rating.toFixed(1)} / 5` : 'No reviews yet'],
                                ].map(([label, value]) => (
                                    <div key={label} className="!bg-ink/70 px-5 py-4">
                                        <dt className="label">{label}</dt>
                                        <dd className="num mt-2 text-[0.95rem]">{value}</dd>
                                    </div>
                                ))}
                            </dl>

                            <div className="mt-10 flex flex-wrap gap-2">
                                {showCount > 0 ? (
                                    <button type="button" onClick={() => scrollToTarget('#showtimes')} className="btn btn-primary btn-lg">Choose a showtime <Icon name="arrow-down" size={18} /></button>
                                ) : (
                                    <span className="btn btn-ghost btn-lg" aria-disabled="true">{movie.status_key === 'coming_soon' ? 'Tickets on sale soon' : 'No upcoming shows'}</span>
                                )}
                                {auth.user ? (
                                    <button type="button" onClick={toggleWishlist} className={cn('btn btn-ghost btn-lg', props.inWishlist && '!border-accent !text-accent')} aria-pressed={props.inWishlist}>
                                        <Icon name="heart" size={18} className={props.inWishlist ? 'fill-accent' : ''} /> {props.inWishlist ? 'On your watchlist' : 'Add to watchlist'}
                                    </button>
                                ) : (
                                    <Link href={route('user.login')} className="btn btn-ghost btn-lg"><Icon name="heart" size={18} /> Add to watchlist</Link>
                                )}
                                <button type="button" onClick={() => compare.toggle(movie)} aria-pressed={compare.has(movie.id)} className={cn('btn btn-ghost btn-lg', compare.has(movie.id) && '!border-accent !text-accent')}>
                                    <Icon name="compare" size={18} /> {compare.has(movie.id) ? 'In compare' : 'Compare'}
                                </button>
                            </div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* Section nav ----------------------------------------------------------- */}
            <nav className="sticky top-0 z-40 border-y border-line bg-ink/90 backdrop-blur-xl" aria-label="On this page">
                <ul className="shell no-scrollbar flex overflow-x-auto">
                    {sections.map(([anchor, label]) => (
                        <li key={anchor}>
                            <button type="button" onClick={() => scrollToTarget(`#${anchor}`, -64)} className="block whitespace-nowrap px-4 py-4 text-[11px] font-semibold uppercase tracking-[.08em] text-mute transition hover:text-accent [font-stretch:115%]">
                                {label}
                            </button>
                        </li>
                    ))}
                </ul>
            </nav>

            <Showtimes {...props} />

            {/* Story ------------------------------------------------------------------ */}
            <section id="story" className="scroll-mt-24 pt-28" aria-labelledby="story-title">
                <div className="shell grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-4">
                        <p className="label label-accent">Synopsis</p>
                        <h2 id="story-title" className="display mt-4 text-7xl">The story</h2>
                    </div>
                    <div className="lg:col-span-8">
                        <Reveal>
                            <p className="text-2xl font-medium leading-[1.35] [font-stretch:92%] sm:text-[2rem] first-letter:float-left first-letter:mr-3 first-letter:text-[6.5rem] first-letter:font-black first-letter:leading-[.78] first-letter:text-accent first-letter:[font-stretch:62%]">
                                {movie.description}
                            </p>
                        </Reveal>
                        {movie.content_advisory && (
                            <div className="alert alert-info mt-10 max-w-2xl">
                                <Icon name="info" size={18} className="mt-0.5" />
                                <p><strong className="font-semibold">{movie.certificate} · {movie.certificate_label}.</strong> {movie.content_advisory}</p>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            <Cast {...props} />

            {/* Details --------------------------------------------------------------- */}
            <section id="details" className="scroll-mt-24 pt-28" aria-labelledby="details-title">
                <div className="shell">
                    <SectionHeading label="Fact sheet" title="Details" id="details-title" />
                    <dl className="grid-lines mt-12 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            ['Original title', movie.title],
                            ['Release date', movie.release_long ?? '—'],
                            ['Running time', `${movie.duration_minutes} minutes (${movie.duration})`],
                            ['Certificate', `${movie.certificate} · ${movie.certificate_label}`],
                            ['Language', movie.language + (movie.language !== 'English' ? ', English subtitles' : '')],
                            ['Genres', movie.genres.join(', ')],
                            ['Studio', movie.studio],
                            ['Country', movie.country],
                        ].map(([label, value]) => (
                            <div key={label} className="p-6">
                                <dt className="label">{label}</dt>
                                <dd className="mt-2">{value}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            <ReviewsSection {...props} />

            {props.similar.length > 0 && (
                <section id="similar" className="scroll-mt-24 pt-28" aria-labelledby="similar-title">
                    <div className="shell">
                        <div className="flex items-end justify-between gap-6">
                            <SectionHeading label="If you liked this" title="More like this" accent={['this']} id="similar-title" />
                            {props.primaryGenre && (
                                <Link href={route('movies.genre', props.primaryGenre.slug)} className="btn btn-ghost hidden shrink-0 sm:inline-flex">More {props.primaryGenre.name.toLowerCase()} <Icon name="arrow-right" size={16} className="arrow" /></Link>
                            )}
                        </div>
                        <div className="mt-12 grid grid-cols-2 gap-x-4 gap-y-12 lg:grid-cols-4">
                            {props.similar.map((item) => <MovieCard key={item.id} movie={item} />)}
                        </div>
                    </div>
                </section>
            )}
        </>
    );
}

function Showtimes({ movie, showDays: allDays, cities, showCount, venueCount }: Props) {
    const [city, setCity] = useState<string | null>(null);
    const showDays = city ? allDays.map((item) => ({ ...item, venues: item.venues.filter((venue) => venue.city === city) })).filter((item) => item.venues.length) : allDays;
    const [day, setDay] = useState(allDays[0]?.date ?? '');
    const current = showDays.find((item) => item.date === day) ?? showDays[0];

    return (
        <section id="showtimes" className="scroll-mt-24 pt-20" aria-labelledby="showtimes-title">
            <div className="shell">
                <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <SectionHeading label="Book seats" title="Showtimes" id="showtimes-title"
                        description={showCount ? `${plural(showCount, 'upcoming show')} across ${plural(venueCount, 'cinema')}. Prices shown are the lowest seat for that show.` : undefined} />
                    {cities.length > 1 && (
                        <div className="flex flex-wrap gap-1" aria-label="Filter by city">
                            <button type="button" onClick={() => setCity(null)} aria-pressed={!city} className="chip">All cities</button>
                            {cities.map((name) => (
                                <button key={name} type="button" onClick={() => setCity(name)} aria-pressed={city === name} className="chip">{name}</button>
                            ))}
                        </div>
                    )}
                </div>

                {showDays.length === 0 ? (
                    <div className="panel mt-12 flex flex-col items-start gap-6 p-8 sm:flex-row sm:items-center sm:justify-between sm:p-10">
                        <div>
                            <p className="headline text-4xl">{movie.status_key === 'coming_soon' ? `Opens ${movie.release_short}` : 'No shows scheduled right now'}</p>
                            <p className="mt-2 text-mute">{movie.status_key === 'coming_soon' ? 'Add it to your watchlist and we will keep it at the top of your account.' : 'Check back soon, or explore what else is playing this week.'}</p>
                        </div>
                        <Link href={route('movies.index')} className="btn btn-ghost">Now showing <Icon name="arrow-right" size={16} className="arrow" /></Link>
                    </div>
                ) : (
                    <div className="mt-12">
                        <div className="no-scrollbar flex gap-1 overflow-x-auto pb-1" role="tablist" aria-label="Choose a day">
                            {showDays.map((item) => (
                                <button key={item.date} type="button" role="tab" aria-selected={item.date === current.date} onClick={() => setDay(item.date)}
                                    className={cn('flex min-w-[5.5rem] flex-col items-center border px-4 py-3 transition', item.date === current.date ? 'border-accent bg-volt text-noir' : 'border-line-2 text-paper-2 hover:border-paper')}>
                                    <span className="text-[10px] font-bold uppercase tracking-[.1em] [font-stretch:115%]">{item.weekday}</span>
                                    <span className="display mt-1 text-5xl !text-inherit">{item.day}</span>
                                    <span className="text-[11px] opacity-70">{item.month}</span>
                                </button>
                            ))}
                        </div>

                        <div role="tabpanel" className="mt-8 border-t border-line">
                            {current.venues.map((venue) => (
                                <div key={venue.slug} className="grid gap-6 border-b border-line py-8 lg:grid-cols-12">
                                    <div className="lg:col-span-4">
                                        <Link href={route('cinemas.show', venue.slug)} className="group inline-flex items-center gap-2">
                                            <h3 className="headline text-3xl transition group-hover:text-accent">{venue.name}</h3>
                                            <Icon name="arrow-up-right" size={16} className="text-dim transition group-hover:text-accent" />
                                        </Link>
                                        <p className="mt-2 flex items-start gap-2 text-sm text-mute"><Icon name="pin" size={16} className="mt-0.5" /> {venue.address}</p>
                                    </div>
                                    <ul className="flex flex-wrap gap-2 lg:col-span-8">
                                        {venue.shows.map((show) => (
                                            <li key={show.id}>
                                                <Link href={route('movies.seats', { slug: movie.slug, show: show.id })}
                                                    aria-label={`${show.time} ${show.meridiem}, ${show.format}, ${show.screen}, from ${money(show.from_price)}${show.fast ? ', selling fast' : ''}`}
                                                    className="group relative isolate flex min-w-[9.5rem] flex-col overflow-hidden border border-line-2 px-4 py-3 transition hover:border-accent">
                                                    <span className="absolute inset-0 -z-10 origin-bottom scale-y-0 bg-volt transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:scale-y-100" />
                                                    <span className="flex items-center justify-between gap-3">
                                                        <span className="num text-2xl font-semibold transition-colors group-hover:text-noir">{show.time}<span className="ml-1 text-xs text-mute group-hover:text-noir/60">{show.meridiem}</span></span>
                                                        {show.fast && <span className="h-2 w-2 animate-blink bg-signal" title="Selling fast" />}
                                                    </span>
                                                    <span className="label mt-1 text-[9px] group-hover:text-noir/70">{show.format}</span>
                                                    <span className={cn('num mt-2 text-xs transition-colors group-hover:text-noir', show.on_sale ? 'text-signal' : 'text-mute')}>from {money(show.from_price)}</span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                        <p className="mt-5 flex items-center gap-2 text-xs text-mute"><span className="h-2 w-2 bg-signal" /> Over 60% of seats sold · Prices in <span className="text-signal">red</span> include a matinée discount</p>
                    </div>
                )}
            </div>
        </section>
    );
}

function Cast({ cast, directors, writers, crew, movie }: Props) {
    const [all, setAll] = useState(false);
    const limit = 8;

    return (
        <section id="cast" className="scroll-mt-24 pt-28" aria-labelledby="cast-title">
            <div className="shell">
                <SectionHeading label="On screen and behind it" title="Cast & crew" accent={['crew']} id="cast-title" />
                <div className="mt-12 grid gap-12 lg:grid-cols-12">
                    <ul className="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-4 lg:col-span-8">
                        {cast.slice(0, all ? cast.length : limit).map((credit) => (
                            <li key={`${credit.name}-${credit.character}`}>
                                <span className="grid aspect-square w-full place-items-center border border-line text-5xl font-black uppercase [font-stretch:62%]"
                                    style={{ background: `linear-gradient(160deg, color-mix(in oklab, ${movie.palette[1]} 22%, var(--color-ink-3)), var(--color-ink))` }}>{credit.initials}</span>
                                <p className="mt-3 font-semibold">{credit.name}</p>
                                {credit.character && <p className="text-sm text-mute">as {credit.character}</p>}
                            </li>
                        ))}
                    </ul>
                    <dl className="space-y-6 lg:col-span-4">
                        {([['Directed by', directors], ['Written by', writers]] as [string, Credit[]][]).filter(([, credits]) => credits.length).map(([label, credits]) => (
                            <div key={label} className="border-t border-line pt-5">
                                <dt className="label">{label}</dt>
                                <dd className="headline mt-2 text-3xl">{credits.map((credit) => credit.name).join(', ')}</dd>
                            </div>
                        ))}
                        {crew.map((credit) => (
                            <div key={`${credit.role}-${credit.name}`} className="border-t border-line pt-5">
                                <dt className="label">{credit.role}</dt>
                                <dd className="mt-2 text-lg">{credit.name}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
                {cast.length > limit && (
                    <button type="button" className="btn btn-ghost btn-sm mt-8" onClick={() => setAll(!all)}>{all ? 'Show fewer' : `Full cast (${cast.length})`}</button>
                )}
            </div>
        </section>
    );
}

function ReviewsSection({ movie, reviews, totalReviews, distribution, canReview, userReview }: Props) {
    const form = useForm({ rating: userReview?.rating ?? 0, review_text: userReview?.text ?? '' });
    const [hover, setHover] = useState(0);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(route('movies.reviews.store', movie.slug), { preserveScroll: true });
    };

    return (
        <section id="reviews" className="scroll-mt-24 pt-28" aria-labelledby="reviews-title">
            <div className="shell grid gap-12 lg:grid-cols-12">
                <div className="lg:col-span-4">
                    <SectionHeading label="Verified audience" title="Reviews" id="reviews-title" />
                    {totalReviews > 0 && (
                        <div className="panel mt-10 p-7">
                            <div className="flex items-end gap-4">
                                <span className="display text-8xl tabular">{movie.average_rating.toFixed(1)}</span>
                                <div className="pb-2">
                                    <StarRating rating={movie.average_rating} size={16} />
                                    <p className="mt-1 text-sm text-mute">{plural(totalReviews, 'review')}</p>
                                </div>
                            </div>
                            <ul className="mt-6 space-y-2">
                                {distribution.map(({ stars, count }) => (
                                    <li key={stars} className="flex items-center gap-3 text-xs text-mute">
                                        <span className="num w-3">{stars}</span>
                                        <span className="h-1.5 flex-1 overflow-hidden bg-line"><motion.span className="block h-full bg-volt" initial={{ width: 0 }} whileInView={{ width: `${totalReviews ? (count / totalReviews) * 100 : 0}%` }} viewport={{ once: true }} transition={{ duration: 1.2, ease: [0.16, 1, 0.3, 1] }} /></span>
                                        <span className="num w-5 text-right">{count}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                    <p className="mt-6 text-sm text-mute">Only guests who booked this film on BookMyMovie can review it.</p>
                </div>

                <div className="lg:col-span-8">
                    {canReview && (
                        <form onSubmit={submit} className="panel mb-10 p-7">
                            <p className="headline text-3xl">{userReview ? 'Update your review' : 'You saw it. What did you think?'}</p>
                            <fieldset className="mt-5">
                                <legend className="field-label">Your rating</legend>
                                <div className="mt-2 flex gap-1" onMouseLeave={() => setHover(0)}>
                                    {[1, 2, 3, 4, 5].map((star) => (
                                        <label key={star} className="cursor-pointer" onMouseEnter={() => setHover(star)}>
                                            <input type="radio" name="rating" value={star} className="sr-only" checked={form.data.rating === star} onChange={() => form.setData('rating', star)} />
                                            <Icon name="star" size={30} className={cn('transition', (hover || form.data.rating) >= star ? 'fill-accent text-accent' : 'text-dim')} />
                                            <span className="sr-only">{plural(star, 'star')}</span>
                                        </label>
                                    ))}
                                </div>
                                {form.errors.rating && <p className="field-error mt-2">{form.errors.rating}</p>}
                            </fieldset>
                            <div className="field mt-5">
                                <label htmlFor="review_text" className="field-label">Your review</label>
                                <textarea id="review_text" className="input" minLength={20} maxLength={1200} required placeholder="What stayed with you? No spoilers, please."
                                    value={form.data.review_text} onChange={(event) => form.setData('review_text', event.target.value)} />
                                {form.errors.review_text && <p className="field-error">{form.errors.review_text}</p>}
                            </div>
                            <button type="submit" disabled={form.processing} className="btn btn-primary mt-5">Publish review</button>
                        </form>
                    )}

                    {reviews.length ? reviews.map((review) => (
                        <article key={review.id} className="border-t border-line py-8 first:border-t-0 first:pt-0">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div className="flex items-center gap-3">
                                    <span className="grid h-10 w-10 place-items-center bg-ink-4 text-lg font-black uppercase [font-stretch:75%]">{review.initial}</span>
                                    <div>
                                        <p className="text-sm font-semibold">{review.author}</p>
                                        <p className="text-xs text-mute">Verified booking · {review.ago}</p>
                                    </div>
                                </div>
                                <StarRating rating={review.rating} />
                            </div>
                            <p className="mt-5 max-w-2xl text-lg leading-relaxed text-paper-2">{review.text}</p>
                        </article>
                    )) : (
                        <div className="panel p-10 text-center">
                            <Icon name="quote" size={32} className="mx-auto text-dim" />
                            <p className="headline mt-4 text-3xl">No reviews yet</p>
                            <p className="mt-2 text-sm text-mute">{movie.status_key === 'coming_soon' ? 'Reviews open after the first screenings.' : 'Book a seat and be the first to tell everyone what you thought.'}</p>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
