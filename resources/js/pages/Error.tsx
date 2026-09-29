import { Head, Link, router, usePage } from '@inertiajs/react';
import { animate, motion, useMotionTemplate, useMotionValue, useReducedMotion, useSpring } from 'motion/react';
import { useEffect, useState } from 'react';
import Icon from '@/components/Icon';
import Poster from '@/components/Poster';
import Tilt from '@/components/Tilt';
import { route, useShared } from '@/lib/utils';

const copy: Record<number, { eyebrow: string; title: string; message: string }> = {
    403: { eyebrow: 'Staff only', title: 'This door is for the projection booth', message: 'You do not have permission to open this page. If you think that is a mistake, sign in with the right account or ask the owner to change your role.' },
    404: { eyebrow: 'Reel missing', title: 'This reel never made it to the cinema', message: 'The page you asked for has ended its run, moved to another screen, or the link was typed a little differently. Everything that is actually playing is one click away.' },
    419: { eyebrow: 'Session expired', title: 'The intermission ran long', message: 'Your page sat open for a while, so we refreshed its security token. Go back and try again; nothing was submitted twice.' },
    429: { eyebrow: 'Too many requests', title: 'Easy there, speed reader', message: 'We received a lot of requests from your connection in a short time, so we paused things to keep the box office quick for everyone. Wait a minute and try again.' },
    500: { eyebrow: 'Projector jam', title: 'The film snapped in the gate', message: 'Something went wrong on our side and the team has already been told. Your bookings are safe. Please try again in a minute.' },
    503: { eyebrow: 'Changing reels', title: 'Back after this short intermission', message: 'BookMyMovie is down for a few minutes of maintenance. Your bookings and tickets are safe.' },
};

/** A film-leader countdown: rings, a cross hair and a sweeping hand, ending on the status code. */
function Leader({ status }: { status: number }) {
    const reduce = useReducedMotion();
    const [count, setCount] = useState(reduce ? 0 : 3);
    const angle = useMotionValue(0);
    const sweep = useMotionTemplate`conic-gradient(from 0deg, color-mix(in oklab, var(--color-accent) 28%, transparent) ${angle}deg, transparent 0deg)`;

    useEffect(() => {
        if (reduce || count === 0) return;
        angle.set(0);
        const controls = animate(angle, 360, { duration: 0.9, ease: 'linear' });
        const timer = window.setTimeout(() => setCount(count - 1), 900);
        return () => { controls.stop(); window.clearTimeout(timer); };
    }, [count, reduce, angle]);

    return (
        <div className="relative aspect-square w-full max-w-[26rem]" aria-hidden="true">
            <svg viewBox="0 0 200 200" className="absolute inset-0 h-full w-full text-paper/70">
                <circle cx="100" cy="100" r="92" fill="none" stroke="currentColor" strokeWidth="1.5" />
                <circle cx="100" cy="100" r="70" fill="none" stroke="currentColor" strokeWidth="1" opacity=".6" />
                <line x1="100" y1="2" x2="100" y2="198" stroke="currentColor" strokeWidth=".8" opacity=".5" />
                <line x1="2" y1="100" x2="198" y2="100" stroke="currentColor" strokeWidth=".8" opacity=".5" />
            </svg>
            {count > 0 && <motion.div className="absolute inset-[4%] rounded-full" style={{ background: sweep }} />}
            <div className="absolute inset-0 grid place-items-center">
                <motion.span key={count} initial={{ opacity: 0, scale: 1.3 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.35 }}
                    className="display text-[clamp(5rem,14vw,11rem)] leading-none text-paper">{count > 0 ? count : status}</motion.span>
            </div>
        </div>
    );
}

export default function ErrorPage({ status }: { status: number }) {
    const { navMovies } = useShared();
    const { url } = usePage();
    const reduce = useReducedMotion();
    const text = copy[status] ?? copy[500];
    const [query, setQuery] = useState('');
    const x = useMotionValue(50);
    const y = useMotionValue(35);
    const sx = useSpring(x, { stiffness: 80, damping: 20 });
    const sy = useSpring(y, { stiffness: 80, damping: 20 });
    // The cursor is the projector: a warm pool of light over a dark room.
    const beam = useMotionTemplate`radial-gradient(38rem circle at ${sx}% ${sy}%, rgb(255 244 214 / 0.13), rgb(255 244 214 / 0.04) 40%, transparent 70%)`;

    const search = (event: React.FormEvent) => {
        event.preventDefault();
        const term = query.trim();
        if (term) router.visit(route('search', { query: term }));
    };

    return (
        <>
            <Head title={`${status} · ${text.title}`}>
                <meta head-key="robots" name="robots" content="noindex" />
            </Head>
            <section className="relative isolate overflow-hidden pb-24 pt-[calc(var(--header)+3rem)]"
                onPointerMove={(event) => {
                    if (reduce) return;
                    const box = event.currentTarget.getBoundingClientRect();
                    x.set(((event.clientX - box.left) / box.width) * 100);
                    y.set(((event.clientY - box.top) / box.height) * 100);
                }}>
                <motion.div className="pointer-events-none absolute inset-0 -z-10" style={{ background: beam }} aria-hidden="true" />
                <div className="grain pointer-events-none absolute inset-0 -z-10 opacity-[.06]" aria-hidden="true" />
                <p className="pointer-events-none absolute -right-[4vw] top-[8vh] -z-10 select-none text-[34vw] font-black leading-none text-paper/[.035] [font-stretch:62%]" aria-hidden="true">{status}</p>

                <div className="shell grid items-center gap-14 lg:grid-cols-12">
                    <div className="lg:col-span-7">
                        <p className="label label-accent flex items-center gap-3"><span className="h-px w-8 bg-accent" /> Error {status} · {text.eyebrow}</p>
                        <h1 className="display mt-6 text-[clamp(3.4rem,8.5vw,8rem)] leading-[.86]">{text.title}</h1>
                        <p className="lede mt-7 max-w-xl">{text.message}</p>

                        {status === 404 && (
                            <div className="mt-8 inline-flex max-w-full items-center gap-4 border border-dashed border-line-2 px-4 py-3">
                                <span className="label shrink-0">You asked for</span>
                                <code className="num truncate text-sm text-paper" dir="ltr">{url}</code>
                            </div>
                        )}

                        {status === 404 && (
                            <form onSubmit={search} className="mt-8 flex max-w-xl gap-2" role="search">
                                <label htmlFor="lost-search" className="sr-only">Search films, cinemas and people</label>
                                <div className="input-wrap relative flex-1">
                                    <Icon name="search" size={17} className="input-icon" />
                                    <input id="lost-search" value={query} onChange={(event) => setQuery(event.target.value)} maxLength={120} autoComplete="off"
                                        placeholder="Search a film, cinema or actor" className="input has-icon" />
                                </div>
                                <button type="submit" className="btn btn-primary">Find it</button>
                            </form>
                        )}

                        <div className="mt-8 flex flex-wrap gap-2">
                            <Link href={route('home')} className="btn btn-light"><Icon name="arrow-left" size={16} /> Back to the lobby</Link>
                            <Link href={route('movies.index')} className="btn btn-ghost">Everything showing</Link>
                            <Link href={route('cinemas.index')} className="btn btn-ghost">Our cinemas</Link>
                            <Link href={route('contact')} className="btn btn-ghost">Tell us about a broken link</Link>
                        </div>
                    </div>

                    <div className="flex justify-center lg:col-span-5">
                        <Leader status={status} />
                    </div>
                </div>

                {navMovies.length > 0 && status !== 503 && (
                    <div className="shell mt-24">
                        <div className="flex items-end justify-between gap-4 border-t border-line pt-8">
                            <p className="headline text-3xl">Playing instead</p>
                            <Link href={route('movies.index')} className="link text-sm text-accent">All films</Link>
                        </div>
                        <ul className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {navMovies.slice(0, 4).map((movie, index) => (
                                <motion.li key={movie.id} initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 + index * 0.08, duration: 0.6, ease: [0.16, 1, 0.3, 1] }}>
                                    <Link href={route('movies.show', movie.slug)} className="group block">
                                        <Tilt max={8}><Poster movie={movie} size="sm" meta={false} /></Tilt>
                                        <p className="mt-3 truncate font-semibold group-hover:text-accent">{movie.title}</p>
                                        <p className="text-xs text-mute">{movie.genre} · {movie.duration}</p>
                                    </Link>
                                </motion.li>
                            ))}
                        </ul>
                    </div>
                )}
            </section>
        </>
    );
}
