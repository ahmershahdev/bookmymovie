import { Link } from '@inertiajs/react';
import { AnimatePresence, motion, useMotionValue, useReducedMotion, useSpring } from 'motion/react';
import { lazy, Suspense, useEffect, useRef, useState } from 'react';
import Accordion from '@/components/Accordion';
import Icon from '@/components/Icon';
import MovieCard from '@/components/MovieCard';
import { CountUp, Reveal, SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { BackdropVideo, TrailerModal } from '@/components/Trailer';
import { SectionHeading, StarRating } from '@/components/ui';
import { useT } from '@/lib/i18n';
import { cn, pad, route } from '@/lib/utils';
import type { MovieCard as Movie } from '@/types';

const PosterRing = lazy(() => import('@/three/PosterRing'));
const ease = [0.16, 1, 0.3, 1] as const;

interface Props {
    slides: Movie[];
    nowShowing: Movie[];
    comingSoon: Movie[];
    topRated: Movie[];
    genres: { name: string; slug: string; count: number }[];
    cinemas: { slug: string; name: string; city: string; screens: number; seats: number }[];
    offers: { code: string; description: string; value: string }[];
    reviews: { id: number; text: string; rating: number; author: string; movie: { title: string; slug: string } }[];
    faqs: { id: number; question: string; answer: string }[];
    stats: { films: number; cinemas: number; screens: number; cities: number; shows_this_week: number; tickets_sold: number };
    ticker: string[];
}

const formats = [
    ['IMAX with Laser', 'Up to 40% more image, a 12-channel sound system and the brightest projection in the country.'],
    ['Dolby Cinema', 'Dolby Vision HDR with true blacks, Dolby Atmos overhead sound and reclining seats.'],
    ['4DX Motion', 'Seats that move with the action, plus wind, mist and scent timed to the film.'],
    ['Recliner Lounge', 'Powered leather recliners, side tables and a quieter, adults-first room.'],
];

const steps = [
    ['Pick a film', 'Browse by genre, format or cinema. Every film page lists the next seven days of showtimes.'],
    ['Choose your seats', 'See every row’s price before you tap. Your seats are held for 10 minutes.'],
    ['Confirm in one step', 'Apply a coupon, confirm your details, done. No card needed and no booking fee.'],
    ['Pay at the counter', 'Show your e-ticket, pay at the box office and walk in. Cancel free until two hours before.'],
];

export default function Home(props: Props) {
    return (
        <>
            <Hero slides={props.slides} />
            {props.ticker.length > 0 && <Ticker messages={props.ticker} />}
            <NowShowing movies={props.nowShowing} genres={props.genres} stats={props.stats} />
            {props.topRated.length > 0 && <TopRated movies={props.topRated} />}
            <Formats />
            {props.comingSoon.length > 0 && <ComingSoon movies={props.comingSoon} />}
            <Numbers stats={props.stats} />
            <Cinemas cinemas={props.cinemas} />
            <HowItWorks />
            {props.offers.length > 0 && <Offers offers={props.offers} />}
            {props.reviews.length > 0 && <Reviews reviews={props.reviews} />}
            {props.faqs.length > 0 && <Faqs faqs={props.faqs} />}
        </>
    );
}

/* Hero ------------------------------------------------------------------------ */

function Hero({ slides }: { slides: Movie[] }) {
    const [active, setActive] = useState(0);
    const [paused, setPaused] = useState(false);
    const reduce = useReducedMotion();
    const count = Math.max(1, slides.length);
    const slide = slides[active];
    const [webgl, setWebgl] = useState(false);
    const [watching, setWatching] = useState(false);
    const t = useT();
    // Real artwork (a 16:9 still or a trailer) replaces the 3D ring when every slide has it.
    const cinematic = slides.length > 0 && slides.every((item) => item.hero_image_url || item.trailers.length);
    const hasTrailer = Boolean(slide?.trailers.length);

    useEffect(() => {
        try {
            const canvas = document.createElement('canvas');
            setWebgl(Boolean(canvas.getContext('webgl2') || canvas.getContext('webgl')));
        } catch {
            setWebgl(false);
        }
    }, []);

    useEffect(() => {
        if (paused || watching || reduce || count < 2) return;
        // A slide with a trailer stays up for the whole 10-second clip.
        const timer = window.setInterval(() => !document.hidden && setActive((index) => (index + 1) % count), hasTrailer ? 10000 : 7000);
        return () => window.clearInterval(timer);
    }, [paused, watching, reduce, count, active, hasTrailer]);

    if (!slide) return <div className="h-[var(--header)]" />;

    const go = (index: number) => setActive((index + count) % count);

    return (
        <section className="relative isolate flex min-h-[100svh] flex-col overflow-hidden pt-[calc(var(--header)+2rem)]" aria-roledescription="carousel" aria-label="Featured films"
            onMouseEnter={() => setPaused(true)} onMouseLeave={() => setPaused(false)} onFocus={() => setPaused(true)} onBlur={() => setPaused(false)}>
            {/* Colour wash from the active film's palette. */}
            <AnimatePresence>
                <motion.div key={slide.slug} className="absolute inset-0 -z-20" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: 1.4 }}
                    style={{ background: `radial-gradient(60% 55% at 50% 42%, color-mix(in oklab, ${slide.palette[1]} 26%, transparent), transparent 70%), var(--color-ink)` }} />
            </AnimatePresence>

            {cinematic ? (
                <AnimatePresence>
                    <motion.div key={slide.slug} className="absolute inset-0 -z-10" initial={{ opacity: 0, scale: 1.06 }} animate={{ opacity: 1, scale: 1 }} exit={{ opacity: 0 }}
                        transition={{ opacity: { duration: 1.1 }, scale: { duration: 8, ease: 'linear' } }}>
                        <BackdropVideo trailer={slide.trailers[0]} image={slide.hero_image_url} />
                    </motion.div>
                </AnimatePresence>
            ) : webgl && !reduce ? (
                <Suspense fallback={null}>
                    <PosterRing movies={slides} active={active} offset={0.2} className="absolute inset-0 -z-10" />
                </Suspense>
            ) : (
                <div className="absolute right-[6%] top-1/2 -z-10 hidden w-[24vw] max-w-sm -translate-y-1/2 lg:block">
                    <Poster movie={slide} size="lg" eager />
                </div>
            )}

            <div className="pointer-events-none absolute inset-0 -z-10 hidden rtl:-scale-x-100 bg-[linear-gradient(90deg,var(--color-ink)_0%,color-mix(in_oklab,var(--color-ink)_82%,transparent)_34%,transparent_62%)] lg:block" aria-hidden="true" />
            <div className="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-ink)_55%,transparent)_0%,transparent_28%,transparent_45%,color-mix(in_oklab,var(--color-ink)_92%,transparent)_78%,var(--color-ink)_100%)]" aria-hidden="true" />

            <div className="shell flex w-full flex-1 flex-col justify-end pb-10 sm:pb-14">
                <div className="grid items-end gap-8 lg:grid-cols-12">
                    <div className="lg:col-span-9" role="group" aria-roledescription="slide" aria-label={`${active + 1} of ${count}: ${slide.title}`}>
                        <AnimatePresence mode="wait">
                            <motion.div key={slide.slug} exit={{ opacity: 0, y: -24 }} transition={{ duration: 0.35 }}>
                                <motion.p className="label label-accent flex items-center gap-3" initial={{ opacity: 0, x: -12 }} animate={{ opacity: 1, x: 0 }} transition={{ duration: 0.6, ease }}>
                                    <span className="num">{pad(active + 1)}</span><span className="h-px w-8 bg-volt" />{slide.hero_eyebrow}
                                </motion.p>
                                {active === 0 ? (
                                    <SplitHeading as="h1" text={slide.title} className="mt-4 max-w-[14ch] text-[clamp(3.25rem,min(9.5vw,14svh),10rem)] leading-[.8]" />
                                ) : (
                                    <SplitHeading as="h2" text={slide.title} className="mt-4 max-w-[14ch] text-[clamp(3.25rem,min(9.5vw,14svh),10rem)] leading-[.8]" />
                                )}
                                <motion.div initial={{ opacity: 0, y: 16 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8, ease, delay: 0.35 }}>
                                    <p className="lede mt-5 max-w-xl" dir="auto">{slide.tagline}</p>
                                    <p className="num mt-5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] uppercase text-mute">
                                        <span>{slide.genre}</span><span className="text-dim">/</span>
                                        <span>{slide.duration}</span><span className="text-dim">/</span>
                                        <span>{slide.certificate}</span><span className="text-dim">/</span>
                                        <span>{slide.language}</span>
                                        {slide.reviews > 0 && <><span className="text-dim">/</span><span className="text-accent">★ {Number(slide.rating).toFixed(1)}</span></>}
                                    </p>
                                    <div className="mt-8 flex flex-wrap gap-2">
                                        <Link href={`${route('movies.show', slide.slug)}${slide.first_show_id ? '#showtimes' : ''}`} className="btn btn-primary btn-lg">
                                            {slide.first_show_id ? t('Book tickets') : t('Opens :date', { date: slide.release_date ?? '' })} <Icon name="arrow-right" size={18} className="arrow" />
                                        </Link>
                                        {hasTrailer && (
                                            <button type="button" onClick={() => setWatching(true)} className="btn btn-ghost btn-lg">
                                                <Icon name="play" size={18} /> {slide.trailers.length > 1 ? t('Watch :count trailers', { count: slide.trailers.length }) : t('Watch trailer')}
                                            </button>
                                        )}
                                        <Link href={route('movies.show', slide.slug)} className="btn btn-ghost btn-lg">{t('Film details')}</Link>
                                    </div>
                                </motion.div>
                            </motion.div>
                        </AnimatePresence>
                    </div>

                    {count > 1 && (
                        <div className="lg:col-span-3">
                            <div className="flex items-center justify-between gap-4 lg:justify-end">
                                <p className="num text-sm text-mute"><span className="text-paper">{pad(active + 1)}</span> / {pad(count)}</p>
                                <div className="flex gap-1">
                                    <button type="button" onClick={() => go(active - 1)} className="btn btn-ghost btn-icon" aria-label="Previous film"><Icon name="arrow-left" size={16} /></button>
                                    <button type="button" onClick={() => go(active + 1)} className="btn btn-ghost btn-icon" aria-label="Next film"><Icon name="arrow-right" size={16} /></button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {count > 1 && (
                    <div className="mt-8 grid gap-2" style={{ gridTemplateColumns: `repeat(${count}, minmax(0, 1fr))` }} role="tablist" aria-label="Choose featured film">
                        {slides.map((item, index) => (
                            <button key={item.slug} type="button" role="tab" aria-selected={active === index} onClick={() => go(index)} className="group text-left" aria-label={`Show ${item.title}`}>
                                <span className="block h-[2px] overflow-hidden bg-line-2">
                                    <span key={`${active}-${paused}`} className={cn('block h-full origin-left bg-volt', active === index ? (paused || reduce ? 'scale-x-100' : 'animate-grow') : index < active ? 'scale-x-100 opacity-40' : 'scale-x-0')} />
                                </span>
                                <span className={cn('label mt-3 hidden truncate transition sm:block', active === index ? 'text-paper' : 'text-dim group-hover:text-paper-2')}>{item.title}</span>
                            </button>
                        ))}
                    </div>
                )}
            </div>

            <TrailerModal title={slide.title} trailers={slide.trailers} open={watching} onClose={() => setWatching(false)} />
        </section>
    );
}

function Ticker({ messages }: { messages: string[] }) {
    return (
        <div className="marquee overflow-hidden border-y border-line bg-ink-2 py-4" aria-label="Announcements">
            <div className="marquee-track">
                {[0, 1, 2].map((loop) => (
                    <div key={loop} className="flex" aria-hidden={loop > 0}>
                        {messages.map((message) => (
                            <span key={message} className="flex items-center gap-6 pr-6 text-sm font-semibold uppercase tracking-[.08em] text-paper-2 [font-stretch:115%]">
                                {message} <span className="h-1.5 w-1.5 bg-volt" />
                            </span>
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}

/* Now showing --------------------------------------------------------------- */

function NowShowing({ movies, genres, stats }: { movies: Movie[]; genres: Props['genres']; stats: Props['stats'] }) {
    const rail = useRef<HTMLDivElement>(null);
    const scrollBy = (direction: number) => rail.current?.scrollBy({ left: direction * rail.current.clientWidth * 0.8, behavior: 'smooth' });

    return (
        <section className="pt-28 sm:pt-40" aria-labelledby="now-showing">
            <div className="shell flex flex-col gap-10 lg:flex-row lg:items-end lg:justify-between">
                <SectionHeading index="01" label="In cinemas this week" title="Now showing" id="now-showing" accent={['showing']}
                    description={`${movies.length} films across ${stats.cinemas} cinemas and ${stats.screens} screens, with ${stats.shows_this_week.toLocaleString('en-PK')} showtimes over the next seven days.`} />
                <div className="flex shrink-0 gap-1">
                    <button type="button" onClick={() => scrollBy(-1)} className="btn btn-ghost btn-icon" aria-label="Scroll films left"><Icon name="arrow-left" size={16} /></button>
                    <button type="button" onClick={() => scrollBy(1)} className="btn btn-ghost btn-icon" aria-label="Scroll films right"><Icon name="arrow-right" size={16} /></button>
                </div>
            </div>

            <div className="shell mt-8 flex flex-wrap gap-2">
                {genres.slice(0, 7).map((genre) => (
                    <Link key={genre.slug} href={route('movies.genre', genre.slug)} className="chip">{genre.name} <span className="num opacity-50">{genre.count}</span></Link>
                ))}
                <Link href={route('movies.index')} className="chip">All films <Icon name="arrow-right" size={12} /></Link>
            </div>

            <div ref={rail} className="rail mx-auto mt-12 max-w-[1520px] pb-2" tabIndex={0} aria-label="Now showing films, scroll horizontally">
                {movies.map((movie, index) => <MovieCard key={movie.id} movie={movie} index={index + 1} eager={index < 5} />)}
            </div>
        </section>
    );
}

/* Top rated: editorial list, poster follows the cursor ------------------------ */

function TopRated({ movies }: { movies: Movie[] }) {
    const [hovered, setHovered] = useState<number | null>(null);
    const x = useMotionValue(0);
    const y = useMotionValue(0);
    const springX = useSpring(x, { stiffness: 220, damping: 26 });
    const springY = useSpring(y, { stiffness: 220, damping: 26 });
    const list = useRef<HTMLOListElement>(null);

    return (
        <section className="pt-32 sm:pt-44" aria-labelledby="top-rated">
            <div className="shell">
                <SectionHeading index="02" label="Audience favourites" title="Rated highest by the people who went" accent={['highest']} id="top-rated"
                    description="Ratings come only from guests who booked the film, so they reflect real screenings, not marketing." />

                <ol ref={list} className="relative mt-16 border-t border-line"
                    onPointerMove={(event) => {
                        const rect = list.current?.getBoundingClientRect();
                        if (!rect) return;
                        x.set(event.clientX - rect.left);
                        y.set(event.clientY - rect.top);
                    }}
                    onPointerLeave={() => setHovered(null)}>
                    {movies.map((movie, index) => (
                        <li key={movie.id} className="border-b border-line" onPointerEnter={() => setHovered(index)}>
                            <Link href={route('movies.show', movie.slug)} className="group grid grid-cols-[3rem_1fr_auto] items-center gap-4 py-6 sm:grid-cols-[5rem_1fr_14rem_auto] sm:py-8">
                                <span className="num text-sm text-dim">{pad(index + 1)}</span>
                                <span className="display text-[clamp(2.5rem,7vw,6.5rem)] transition-[color,transform] duration-500 ease-[var(--ease-out-expo)] group-hover:translate-x-3 group-hover:text-accent">{movie.title}</span>
                                <span className="hidden sm:block">
                                    <StarRating rating={movie.rating} size={14} />
                                    <span className="num mt-1 block text-sm text-mute">{Number(movie.rating).toFixed(1)} · {movie.reviews} reviews</span>
                                </span>
                                <Icon name="arrow-up-right" size={28} className="text-dim transition duration-500 group-hover:rotate-45 group-hover:text-accent" />
                            </Link>
                        </li>
                    ))}

                    <motion.div className="pointer-events-none absolute left-0 top-0 z-10 hidden w-48 lg:block" style={{ x: springX, y: springY, translateX: '-50%', translateY: '-50%' }}
                        animate={{ opacity: hovered === null ? 0 : 1, scale: hovered === null ? 0.85 : 1, rotate: hovered === null ? -6 : -3 }} transition={{ duration: 0.35 }}>
                        {movies.map((movie, index) => (
                            <div key={movie.id} className={cn('absolute inset-x-0 top-0 transition-opacity duration-300', hovered === index ? 'opacity-100' : 'opacity-0')}>
                                <Poster movie={movie} size="sm" />
                            </div>
                        ))}
                    </motion.div>
                </ol>
            </div>
        </section>
    );
}

function Formats() {
    return (
        <section className="pt-32 sm:pt-44" aria-labelledby="formats">
            <div className="shell">
                <div className="grid gap-12 lg:grid-cols-12 lg:items-end">
                    <SectionHeading index="03" label="Choose how you watch" title="Every format one seat map" accent={['one']} id="formats" className="lg:col-span-8" />
                    <div className="lg:col-span-4">
                        <p className="lede">Pick the screen before the seat. Prices change with the format and the row, and you always see both before you book.</p>
                        <Link href={route('cinemas.index')} className="btn btn-ghost mt-6">Find a cinema <Icon name="arrow-right" size={16} className="arrow" /></Link>
                    </div>
                </div>
                <div className="grid-lines mt-16 sm:grid-cols-2 lg:grid-cols-4">
                    {formats.map(([name, copy], index) => (
                        <Reveal key={name} delay={index * 90} className="group relative flex min-h-[20rem] flex-col justify-between overflow-hidden p-7 transition-colors duration-500 hover:bg-volt hover:text-noir">
                            <span className="num text-sm text-dim transition-colors group-hover:text-noir/60">{pad(index + 1)}</span>
                            <div>
                                <h3 className="display text-5xl transition-colors group-hover:!text-noir">{name}</h3>
                                <p className="mt-4 text-sm leading-relaxed text-mute transition-colors group-hover:text-noir/75">{copy}</p>
                            </div>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

function ComingSoon({ movies }: { movies: Movie[] }) {
    return (
        <section className="pt-32 sm:pt-44" aria-labelledby="coming-soon">
            <div className="shell">
                <div className="flex flex-col gap-8 sm:flex-row sm:items-end sm:justify-between">
                    <SectionHeading index="04" label="Mark your calendar" title="Coming soon" accent={['soon']} id="coming-soon"
                        description="Add a film to your watchlist and it will be waiting in your account the day tickets go on sale." />
                    <Link href={route('movies.status', 'coming-soon')} className="btn btn-ghost shrink-0">All upcoming <Icon name="arrow-right" size={16} className="arrow" /></Link>
                </div>
                <div className="mt-14 grid grid-cols-2 gap-x-4 gap-y-12 md:grid-cols-3 lg:grid-cols-6">
                    {movies.map((movie, index) => (
                        <Reveal key={movie.id} delay={index * 70}><MovieCard movie={movie} /></Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

function Numbers({ stats }: { stats: Props['stats'] }) {
    const items: [number, string][] = [
        [stats.films, 'films on the slate'],
        [stats.cinemas, `partner cinemas in ${stats.cities} cities`],
        [stats.shows_this_week, 'showtimes this week'],
        [stats.tickets_sold, 'seats sold and counting'],
    ];

    return (
        <section className="pt-32 sm:pt-44" aria-label="BookMyMovie in numbers">
            <div className="shell">
                <div className="grid-lines grid-cols-2 lg:grid-cols-4">
                    {items.map(([value, label]) => (
                        <div key={label} className="px-5 py-12 sm:px-8">
                            <CountUp value={value} className="display block text-[clamp(4rem,9vw,8rem)] tabular" />
                            <p className="label mt-4">{label}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function Cinemas({ cinemas }: { cinemas: Props['cinemas'] }) {
    return (
        <section className="pt-32 sm:pt-44" aria-labelledby="cinemas">
            <div className="shell grid gap-14 lg:grid-cols-12">
                <div className="lg:col-span-4">
                    <SectionHeading index="05" label="Our cinemas" title="Screens with character" accent={['character']} id="cinemas"
                        description="From a restored 1960s picture palace on the Clifton seafront to a boutique ScreenX room in DHA." />
                    <Link href={route('cinemas.index')} className="btn btn-ghost mt-8">All cinemas <Icon name="arrow-right" size={16} className="arrow" /></Link>
                </div>
                <ol className="border-t border-line lg:col-span-8">
                    {cinemas.map((cinema, index) => (
                        <li key={cinema.slug} className="border-b border-line">
                            <Link href={route('cinemas.show', cinema.slug)} className="group relative isolate grid grid-cols-[2.5rem_1fr_auto] items-center gap-4 overflow-hidden py-6 sm:grid-cols-[3.5rem_1fr_12rem_auto] sm:px-3">
                                <span className="absolute inset-0 -z-10 origin-bottom scale-y-0 bg-volt transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:scale-y-100" aria-hidden="true" />
                                <span className="num text-xs text-dim transition-colors group-hover:text-noir">{pad(index + 1)}</span>
                                <span>
                                    <span className="headline block text-3xl transition-colors group-hover:text-noir sm:text-4xl">{cinema.name}</span>
                                    <span className="mt-1 block text-sm text-mute transition-colors group-hover:text-noir/70 sm:hidden">{cinema.city} · {cinema.screens} screens</span>
                                </span>
                                <span className="hidden text-sm text-mute transition-colors group-hover:text-noir/70 sm:block">{cinema.city}<br /><span className="num text-xs">{cinema.screens} screens · {cinema.seats} seats</span></span>
                                <Icon name="arrow-up-right" size={22} className="text-dim transition duration-500 group-hover:rotate-45 group-hover:text-noir" />
                            </Link>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}

function HowItWorks() {
    return (
        <section className="pt-32 sm:pt-44" aria-labelledby="how-it-works">
            <div className="shell">
                <SectionHeading index="06" label="Four steps, no surprises" title="From couch to row E in a minute" accent={['row', 'e']} id="how-it-works" />
                <ol className="grid-lines mt-16 md:grid-cols-2 lg:grid-cols-4">
                    {steps.map(([title, copy], index) => (
                        <Reveal as="li" key={title} delay={index * 100} className="relative p-7 sm:p-8">
                            <span className="display display-outline block text-8xl text-accent">{index + 1}</span>
                            <h3 className="headline mt-10 text-3xl">{title}</h3>
                            <p className="mt-3 text-sm leading-relaxed text-mute">{copy}</p>
                        </Reveal>
                    ))}
                </ol>
            </div>
        </section>
    );
}

function Offers({ offers }: { offers: Props['offers'] }) {
    return (
        <section className="pt-32 sm:pt-44" aria-labelledby="offers">
            <div className="shell">
                <Reveal className="relative overflow-hidden bg-volt p-7 text-noir sm:p-14">
                    <div className="grid gap-12 lg:grid-cols-12 lg:items-center">
                        <div className="lg:col-span-5">
                            <p className="label !text-noir/70">Offers</p>
                            <h2 className="display mt-4 text-[clamp(3.5rem,8vw,7rem)] !text-noir" id="offers">Better seats smaller bill</h2>
                            <p className="mt-5 max-w-md text-noir/75">Enter a code at checkout. Discounts are applied before you confirm, never after.</p>
                            <Link href={route('offers')} className="btn mt-8 bg-ink text-paper [--btn-wipe:#f2f0ea] hover:text-noir">All offers <Icon name="arrow-right" size={16} className="arrow" /></Link>
                        </div>
                        <div className="grid gap-3 lg:col-span-7">
                            {offers.map((offer) => (
                                <div key={offer.code} className="flex flex-col gap-4 bg-ink p-6 text-paper sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="display text-5xl">{offer.value} off</p>
                                        <p className="mt-1 text-sm text-mute">{offer.description}</p>
                                    </div>
                                    <span className="num self-start border border-dashed border-accent px-4 py-2 text-sm font-semibold tracking-[.2em] text-accent sm:self-center">{offer.code}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}

function Reviews({ reviews }: { reviews: Props['reviews'] }) {
    return (
        <section className="pt-32 sm:pt-44" aria-labelledby="reviews">
            <div className="shell">
                <SectionHeading index="07" label="Verified audience reviews" title="Heard in the foyer" accent={['foyer']} id="reviews" />
                <div className="grid-lines mt-14 lg:grid-cols-3">
                    {reviews.map((review, index) => (
                        <Reveal key={review.id} delay={index * 100}>
                            <figure className="flex h-full flex-col p-8">
                                <StarRating rating={review.rating} size={16} />
                                <blockquote className="mt-6 flex-1 text-2xl font-medium leading-snug [font-stretch:88%]">“{review.text}”</blockquote>
                                <figcaption className="mt-8 flex items-center justify-between gap-4 border-t border-line pt-5 text-sm">
                                    <span className="font-semibold">{review.author}</span>
                                    <Link href={route('movies.show', review.movie.slug)} className="link text-mute hover:text-paper">{review.movie.title}</Link>
                                </figcaption>
                            </figure>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

function Faqs({ faqs }: { faqs: Props['faqs'] }) {
    const [open, setOpen] = useState<number | null>(faqs[0]?.id ?? null);

    return (
        <section className="pt-32 sm:pt-44" aria-labelledby="faq-teaser">
            <div className="shell grid gap-12 lg:grid-cols-12">
                <div className="lg:col-span-4">
                    <SectionHeading index="08" label="Good to know" title="Questions answered" accent={['answered']} id="faq-teaser" />
                    <Link href={route('faq')} className="btn btn-ghost mt-8">Help centre <Icon name="arrow-right" size={16} className="arrow" /></Link>
                </div>
                <div className="border-t border-line lg:col-span-8">
                    {faqs.map((faq) => <Accordion key={faq.id} id={faq.id} question={faq.question} answer={faq.answer} open={open === faq.id} onToggle={() => setOpen(open === faq.id ? null : faq.id)} />)}
                </div>
            </div>
        </section>
    );
}
