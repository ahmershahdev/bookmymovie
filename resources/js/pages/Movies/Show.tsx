import { Link, router } from '@inertiajs/react';
import { motion, useScroll, useTransform } from 'motion/react';
import { useRef, useState } from 'react';
import Icon from '@/components/Icon';
import MarqueeSign from '@/components/MarqueeSign';
import MovieCard from '@/components/MovieCard';
import { Reveal, SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { BackdropVideo, TrailerModal } from '@/components/Trailer';
import ReviewsSection, { type ReviewPage, type ReviewSummary } from '@/components/Reviews';
import { Breadcrumbs, SectionHeading } from '@/components/ui';
import { useT } from '@/lib/i18n';
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
    reviews: ReviewPage;
    reviewSummary: ReviewSummary;
    totalReviews: number;
    distribution: { stars: number; count: number }[];
    pricing: { tier: string; rows: string; from: number; to: number; was: number | null; kids: number | null; benefits: string | null }[];
    similar: Movie[];
    primaryGenre: { name: string; slug: string } | null;
    directors: Credit[];
    writers: Credit[];
    crew: Credit[];
    cast: Credit[];
    inWishlist: boolean;
    canReview: boolean;
    userReview: { rating: number; title: string; text: string; spoilers: boolean } | null;
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
    const [trailer, setTrailer] = useState<number | null>(null);
    const t = useT();

    const toggleWishlist = () => {
        if (props.inWishlist) {
            router.delete(route('user.wishlist.remove', movie.id), { preserveScroll: true });
        } else {
            router.post(route('user.wishlist'), { movie_id: movie.id }, { preserveScroll: true });
        }
    };

    const sections: [string, string][] = [['showtimes', t('Showtimes')], ...(props.pricing.length ? [['prices', t('Prices')] as [string, string]] : []), ...(movie.trailers.length ? [['trailers', movie.trailers.length > 1 ? t('Trailers') : t('Trailer')] as [string, string]] : []), ['story', 'Story'], ['cast', 'Cast & crew'], ['details', 'Details'], ['reviews', `Reviews (${totalReviews})`]];
    if (props.similar.length) sections.push(['similar', 'More like this']);

    return (
        <>
            {/* Hero ------------------------------------------------------------ */}
            <section ref={hero} className="relative isolate overflow-hidden pb-16 pt-[calc(var(--header)+2.5rem)]">
                <div className="absolute inset-0 -z-10" aria-hidden="true"
                    style={{ background: `radial-gradient(55% 70% at 22% 35%, color-mix(in oklab, ${accent} 20%, transparent), transparent 70%), radial-gradient(60% 60% at 90% 0%, color-mix(in oklab, ${ground} 80%, transparent), transparent 70%), var(--color-ink)` }} />
                {(movie.trailers.length > 0 || (movie.hero_image_url && movie.hero_image_url !== movie.poster_url)) && (
                    <div className="absolute inset-0 -z-10 opacity-45" aria-hidden="true">
                        <BackdropVideo trailer={movie.trailers[0]} image={movie.hero_image_url} />
                        <div className="absolute inset-0 rtl:-scale-x-100 bg-[linear-gradient(90deg,var(--color-ink)_0%,color-mix(in_oklab,var(--color-ink)_60%,transparent)_45%,transparent_100%)]" />
                    </div>
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
                            {movie.tagline && <p className="lede mt-6 max-w-2xl text-xl sm:text-2xl" dir="auto">{movie.tagline}</p>}

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
                                {movie.trailers.length > 0 && (
                                    <button type="button" onClick={() => setTrailer(0)} className="btn btn-ghost btn-lg"><Icon name="play" size={18} /> {t('Watch trailer')}</button>
                                )}
                                <button type="button" onClick={() => compare.toggle(movie)} aria-pressed={compare.has(movie.id)} className={cn('btn btn-ghost btn-lg', compare.has(movie.id) && '!border-accent !text-accent')}>
                                    <Icon name="compare" size={18} /> {compare.has(movie.id) ? 'In compare' : 'Compare'}
                                </button>
                            </div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* The cinema marquee: the title in light bulbs. */}
            <section className="shell pb-16 pt-6" aria-label="Marquee">
                <MarqueeSign title={movie.title} eyebrow={movie.status_key === 'coming_soon' ? 'Coming soon' : movie.status_key === 'ended' ? 'Thanks for watching' : 'Now playing'} />
            </section>

            {/* Section nav ----------------------------------------------------------- */}
            <nav className="sticky top-[4.75rem] z-40 border-y border-line bg-ink/90 backdrop-blur-xl" aria-label="On this page">
                <ul className="shell no-scrollbar flex overflow-x-auto">
                    {sections.map(([anchor, label]) => (
                        <li key={anchor}>
                            <button type="button" onClick={() => scrollToTarget(`#${anchor}`, -140)} className="block whitespace-nowrap px-4 py-4 text-[11px] font-semibold uppercase tracking-[.08em] text-mute transition hover:text-accent [font-stretch:115%]">
                                {label}
                            </button>
                        </li>
                    ))}
                </ul>
            </nav>

            <Showtimes {...props} />
            {props.pricing.length > 0 && <Pricing pricing={props.pricing} kidsNote={props.pricing.some((tier) => tier.kids !== null)} />}

            {movie.trailers.length > 0 && (
                <section id="trailers" className="scroll-mt-24 pt-28" aria-labelledby="trailers-title">
                    <div className="shell">
                        <SectionHeading label={movie.trailers.length > 1 ? t(':count cuts, slightly different', { count: movie.trailers.length }) : t('Watch before you book')}
                            title={movie.trailers.length > 1 ? t('Trailers') : t('Trailer')} id="trailers-title" />
                        <div className={cn('mt-12 grid gap-4', movie.trailers.length > 1 && 'md:grid-cols-2')}>
                            {movie.trailers.map((item, index) => (
                                <button key={item.src} type="button" onClick={() => setTrailer(index)} className="group relative block aspect-video overflow-hidden bg-ink-3 text-left"
                                    aria-label={`${t('Play')} ${item.label}: ${movie.title}`}>
                                    {item.poster || movie.hero_image_url ? (
                                        <img src={item.poster ?? movie.hero_image_url ?? ''} alt="" loading="lazy" decoding="async"
                                            className="h-full w-full object-cover transition-transform duration-[900ms] ease-[var(--ease-out-expo)] group-hover:scale-[1.04]" />
                                    ) : null}
                                    <span className="absolute inset-0 bg-gradient-to-t from-ink via-ink/20 to-transparent" />
                                    <span className="absolute left-5 top-5 num text-[11px] text-paper-2">{String(index + 1).padStart(2, '0')} · 0:10</span>
                                    <span className="absolute inset-x-5 bottom-5 flex items-end justify-between gap-4">
                                        <span className="headline text-3xl sm:text-4xl">{item.label}</span>
                                        <span className="grid h-14 w-14 shrink-0 place-items-center bg-volt text-noir transition-transform duration-500 group-hover:scale-110"><Icon name="play" size={22} /></span>
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>
                </section>
            )}
            <TrailerModal title={movie.title} trailers={movie.trailers} open={trailer !== null} initial={trailer ?? 0} onClose={() => setTrailer(null)} />

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

            <ReviewsSection movie={movie} initial={props.reviews} summary={props.reviewSummary} canReview={props.canReview} userReview={props.userReview} />

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

/** What a seat costs for this film, per tier, across the coming week. */
function Pricing({ pricing, kidsNote }: { pricing: Props['pricing']; kidsNote: boolean }) {
    const t = useT();
    return (
        <section id="prices" className="scroll-mt-40 pt-28" aria-labelledby="prices-title">
            <div className="shell">
                <SectionHeading label={t('No booking fee on any seat')} title={t('Tickets & prices')} id="prices-title"
                    description={t('Prices change with the screen and the time of day. This is the full range for the next seven days; the seat map shows the exact price before you tap.')} />
                <div className="mt-12 grid border-s border-t border-line sm:grid-cols-2 xl:grid-cols-4">
                    {pricing.map((tier, index) => (
                        <motion.article key={tier.tier} initial={{ opacity: 0, y: 24 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true, margin: '-10%' }}
                            transition={{ duration: 0.7, delay: index * 0.08, ease: [0.16, 1, 0.3, 1] }} className="group relative flex flex-col border-b border-e border-line bg-ink p-7 transition-colors hover:bg-ink-2">
                            <p className="label">{t('Rows')} <span className="num text-paper">{tier.rows}</span></p>
                            <h3 className="headline mt-3 text-3xl">{tier.tier}</h3>
                            <p className="mt-6 flex items-baseline gap-2">
                                <span className="display text-5xl text-accent">{money(tier.from)}</span>
                                {tier.to > tier.from && <span className="num text-sm text-mute">– {money(tier.to)}</span>}
                            </p>
                            {tier.was && <p className="num mt-1 text-xs text-signal line-through">{money(tier.was)}</p>}
                            {tier.kids !== null && <p className="mt-2 text-sm text-paper-2">{t('Children (3–12)')}: <span className="num">{money(tier.kids)}</span></p>}
                            {tier.benefits && (
                                <ul className="mt-6 space-y-2 border-t border-line pt-5 text-sm text-mute">
                                    {tier.benefits.split(/[,;·]/).map((item) => item.trim()).filter(Boolean).map((item) => (
                                        <li key={item} className="flex gap-2"><Icon name="check" size={14} className="mt-0.5 shrink-0 text-mint" />{item}</li>
                                    ))}
                                </ul>
                            )}
                            <span className="absolute inset-x-0 bottom-0 h-[2px] origin-left scale-x-0 bg-volt transition-transform duration-700 ease-[var(--ease-out-expo)] group-hover:scale-x-100" aria-hidden="true" />
                        </motion.article>
                    ))}
                </div>
                <p className="mt-4 text-xs text-mute">{kidsNote ? t('Child prices apply to ages 3–12 with an adult.') : t('This film has no child discount.')} {t('Coupons, gift cards and loyalty points come off at checkout.')}</p>
            </div>
        </section>
    );
}
