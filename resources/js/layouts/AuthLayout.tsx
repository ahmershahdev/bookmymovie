import { Link, usePage } from '@inertiajs/react';
import { motion, useReducedMotion } from 'motion/react';
import { Children, lazy, Suspense, useEffect, useState, type ReactNode } from 'react';
import Icon from '@/components/Icon';
import { SplitHeading } from '@/components/motion';
import Poster from '@/components/Poster';
import { Wordmark } from '@/components/shell/Navbar';
import ProgressBar from '@/components/shell/ProgressBar';
import ScrollIndicator from '@/components/shell/ScrollIndicator';
import Toaster from '@/components/shell/Toaster';
import ThemeToggle from '@/components/ThemeToggle';
import { Alert } from '@/components/ui';
import { Meta } from '@/layouts/SiteLayout';
import { cn, route, useShared } from '@/lib/utils';

const PosterRing = lazy(() => import('@/three/PosterRing'));

const promises = ['Live seat maps', 'No booking fees', 'Pay online or at the counter', 'Free cancellation until 2h before', 'IMAX · Dolby · 4DX'];

/**
 * Split screen. Left: a sticky stage with the 3D poster ring turning
 * through what is showing. Right: the form column, which scrolls with the
 * rest of the page (smooth scroll and the custom scrollbar included).
 */
export default function AuthLayout({ children }: { children: ReactNode }) {
    const { navMovies, errors } = useShared();
    const { component } = usePage();
    const reduce = useReducedMotion();
    const [active, setActive] = useState(0);
    const movie = navMovies[active];
    const customer = component === 'Auth/Login' || component === 'Auth/Register';

    useEffect(() => {
        if (reduce || navMovies.length < 2) return;
        const timer = window.setInterval(() => setActive((index) => (index + 1) % navMovies.length), 4200);
        return () => window.clearInterval(timer);
    }, [reduce, navMovies.length]);

    return (
        <>
            <Meta />
            <ProgressBar />
            <a href="#main" className="sr-only z-[140] bg-volt px-5 py-3 text-sm font-semibold text-noir focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to form</a>

            <div className="lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
                <aside className="relative hidden h-[100svh] overflow-hidden border-r border-line bg-ink-2 lg:sticky lg:top-0 lg:flex lg:flex-col" aria-hidden="true">
                    <div className="absolute inset-0">
                        {navMovies.length > 0 && !reduce ? (
                            <Suspense fallback={null}><PosterRing movies={navMovies} active={active} className="h-full w-full" /></Suspense>
                        ) : navMovies[0] ? (
                            <div className="absolute left-1/2 top-1/2 w-64 -translate-x-1/2 -translate-y-1/2"><Poster movie={navMovies[0]} size="lg" /></div>
                        ) : null}
                    </div>
                    <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-ink-2)_70%,transparent)_0%,transparent_22%,transparent_48%,var(--color-ink-2)_90%)]" />

                    <div className="relative z-10 flex h-24 items-center justify-between px-10">
                        <Link href={route('home')} tabIndex={-1}><Wordmark /></Link>
                        {movie && <p className="label">Now showing <span className="text-paper">{movie.title}</span></p>}
                    </div>

                    <div className="relative z-10 mt-auto px-10 pb-8">
                        <p className="display text-[clamp(4rem,7vw,7.5rem)]">Your seat is <span className="text-accent">waiting</span></p>
                        <p className="mt-4 max-w-sm text-sm text-mute">One account for every partner cinema. Book in under a minute, pay your way.</p>
                    </div>
                    <div className="marquee relative z-10 overflow-hidden border-t border-line bg-volt py-2.5 text-noir">
                        <div className="marquee-track marquee-track-fast">
                            {[0, 1].map((loop) => (
                                <div key={loop} className="flex">
                                    {promises.map((item) => (
                                        <span key={item} className="flex items-center gap-5 pr-5 text-lg font-extrabold uppercase [font-stretch:62%]">{item} <Icon name="ticket" size={14} stroke={2} /></span>
                                    ))}
                                </div>
                            ))}
                        </div>
                    </div>
                </aside>

                <main id="main" className="flex min-h-[100svh] flex-col">
                    <div className="sticky top-0 z-20 flex h-20 items-center justify-between gap-4 border-b border-line bg-ink/85 px-5 backdrop-blur-xl sm:px-10">
                        <Link href={route('home')} className="lg:hidden"><Wordmark /></Link>
                        {customer ? <AuthSwitch component={component} /> : <Link href={route('home')} className="label hidden items-center gap-2 hover:text-paper lg:inline-flex"><Icon name="arrow-left" size={14} /> Back to site</Link>}
                        <div className="flex items-center gap-1">
                            <ThemeToggle />
                            <Link href={route('home')} className="btn btn-ghost btn-icon" aria-label="Close and go back to the site"><Icon name="close" size={16} /></Link>
                        </div>
                    </div>

                    <div className="flex flex-1 items-start justify-center px-5 pb-20 pt-12 sm:px-10 lg:items-center lg:pt-10">
                        <div className="w-full max-w-[28rem]">
                            {errors.oauth && <div className="mb-6"><Alert>{errors.oauth}</Alert></div>}
                            {children}
                        </div>
                    </div>

                </main>
            </div>
            <Toaster />
            <ScrollIndicator />
        </>
    );
}

/** Sign in / Create account tabs with a sliding indicator. */
function AuthSwitch({ component }: { component: string }) {
    const tabs = [['Auth/Login', 'Sign in', route('user.login')], ['Auth/Register', 'Create account', route('user.register')]];

    return (
        <nav className="relative grid grid-cols-2 border border-line-2 p-1" aria-label="Account">
            {tabs.map(([name, label, href]) => (
                <Link key={name} href={href} preserveScroll aria-current={component === name ? 'page' : undefined}
                    className={cn('relative z-10 px-4 py-2 text-center text-[11px] font-bold uppercase tracking-[.08em] transition-colors [font-stretch:115%]', component === name ? 'text-noir' : 'text-mute hover:text-paper')}>
                    {component === name && <motion.span layoutId="auth-tab" className="absolute inset-0 -z-10 bg-volt" transition={{ type: 'spring', stiffness: 400, damping: 34 }} />}
                    {label}
                </Link>
            ))}
        </nav>
    );
}

export function AuthHeading({ label, title, step, children }: { label: string; title: string; step?: string; children?: ReactNode }) {
    return (
        <div className="mb-10">
            <p className="label flex items-center gap-3">
                <span className="text-accent">{label}</span>
                {step && <><span className="h-px w-8 bg-line-2" /><span className="num">{step}</span></>}
            </p>
            <SplitHeading as="h1" text={title} className="mt-4 text-[clamp(3.75rem,9vw,6.5rem)]" />
            {children && <p className="lede mt-5 !text-base">{children}</p>}
        </div>
    );
}

/** Fields rise in one after another, the way a marquee lights up. */
export function Stagger({ children, className }: { children: ReactNode; className?: string }) {
    const reduce = useReducedMotion();
    return (
        <motion.div className={className} initial={reduce ? false : 'hidden'} animate="shown"
            variants={{ hidden: {}, shown: { transition: { staggerChildren: 0.06, delayChildren: 0.25 } } }}>
            {Children.map(children, (child) => child && (
                <motion.div variants={{ hidden: { opacity: 0, y: 18 }, shown: { opacity: 1, y: 0, transition: { duration: 0.6, ease: [0.16, 1, 0.3, 1] } } }}>{child}</motion.div>
            ))}
        </motion.div>
    );
}

export const authLayout = (page: ReactNode) => <AuthLayout>{page}</AuthLayout>;
