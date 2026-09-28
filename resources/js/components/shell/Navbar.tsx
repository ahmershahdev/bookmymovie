import { Link, router, usePage } from '@inertiajs/react';
import { AnimatePresence, motion, useMotionValue, useScroll, useSpring } from 'motion/react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import Icon from '@/components/Icon';
import Poster from '@/components/Poster';
import ThemeToggle from '@/components/ThemeToggle';
import { useLocale, useT } from '@/lib/i18n';
import { lockScroll } from '@/lib/scroll';
import { cn, pad, route, useShared } from '@/lib/utils';

const ease = [0.76, 0, 0.24, 1] as const;
const easeOut = [0.16, 1, 0.3, 1] as const;

export const CONTACT = {
    website: 'https://ahmershah.dev/',
    github: 'https://github.com/ahmershahdev',
    linkedin: 'https://linkedin.com/in/syedahmershah',
    email: 'support@ahmershah.dev',
    phone: '+92 370 4831994',
    phoneHref: '+923704831994',
};

function useActive() {
    const { url } = usePage();
    const path = url.split('?')[0];
    return (prefixes: string[]) => prefixes.some((prefix) => (prefix === '/' ? path === '/' : path === prefix || path.startsWith(prefix + '/')));
}

export function Wordmark({ className, compact = false }: { className?: string; compact?: boolean }) {
    return (
        <span className={cn('flex items-center gap-2.5', className)}>
            <span className="grid h-8 w-8 shrink-0 place-items-center bg-volt text-noir" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M4 6.5A1.5 1.5 0 0 1 5.5 5h13A1.5 1.5 0 0 1 20 6.5V9a2.5 2.5 0 0 0 0 5v2.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 16.5V14a2.5 2.5 0 0 0 0-5Z" /></svg>
            </span>
            {!compact && (
                <span className="text-[1.05rem] font-extrabold uppercase leading-none tracking-[-0.01em] [font-stretch:125%]">
                    Book<span className="text-accent">My</span>Movie
                </span>
            )}
        </span>
    );
}

/** Live Karachi time, like the clock over a box office. */
function Clock() {
    const [now, setNow] = useState<Date | null>(null);
    useEffect(() => {
        setNow(new Date());
        const timer = window.setInterval(() => setNow(new Date()), 15000);
        return () => window.clearInterval(timer);
    }, []);
    if (!now) return null;
    const time = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Karachi' });

    return (
        <span className="label hidden items-center gap-2 xl:flex" title="Local time in Pakistan">
            <span className="h-1.5 w-1.5 animate-blink bg-mint" aria-hidden="true" />KHI <span className="num text-paper">{time}</span>
        </span>
    );
}

/** Pulls gently toward the pointer: small, tactile, never in the way. */
function Magnetic({ children, strength = 0.25 }: { children: ReactNode; strength?: number }) {
    const ref = useRef<HTMLSpanElement>(null);
    const x = useSpring(useMotionValue(0), { stiffness: 300, damping: 20 });
    const y = useSpring(useMotionValue(0), { stiffness: 300, damping: 20 });

    return (
        <motion.span ref={ref} style={{ x, y }} className="inline-flex"
            onPointerMove={(event) => {
                if (event.pointerType !== 'mouse' || !ref.current) return;
                const rect = ref.current.getBoundingClientRect();
                x.set((event.clientX - rect.left - rect.width / 2) * strength);
                y.set((event.clientY - rect.top - rect.height / 2) * strength);
            }}
            onPointerLeave={() => { x.set(0); y.set(0); }}>
            {children}
        </motion.span>
    );
}

export default function Navbar() {
    const { auth, counts, navMovies } = useShared();
    const isActive = useActive();
    const t = useT();
    const [compact, setCompact] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);
    const [searchOpen, setSearchOpen] = useState(false);
    const [accountOpen, setAccountOpen] = useState(false);
    const { scrollYProgress } = useScroll();
    const progress = useSpring(scrollYProgress, { stiffness: 200, damping: 30 });

    const links = [
        { label: 'Films', href: route('movies.index'), active: isActive(['/movies', '/genres']) },
        { label: 'Cinemas', href: route('cinemas.index'), active: isActive(['/cinemas']) },
        { label: 'Offers', href: route('offers'), active: isActive(['/offers']) },
        { label: 'Compare', href: route('movies.compare'), active: isActive(['/compare']) },
        { label: 'Help', href: route('faq'), active: isActive(['/faq', '/contact', '/e-ticket-info']) },
    ];

    useEffect(() => {
        const onScroll = () => setCompact(window.scrollY > 140);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => lockScroll(menuOpen || searchOpen), [menuOpen, searchOpen]);

    useEffect(() => router.on('navigate', () => {
        setMenuOpen(false);
        setSearchOpen(false);
        setAccountOpen(false);
    }), []);

    // "/" or Ctrl/⌘+K opens search, unless the user is typing in a field.
    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            const target = event.target as HTMLElement;
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable;
            if (((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') || (event.key === '/' && !typing)) {
                event.preventDefault();
                setSearchOpen(true);
            }
            if (event.key === 'Escape') {
                setSearchOpen(false);
                setMenuOpen(false);
                setAccountOpen(false);
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    const badge = (count: number) => count > 0 && (
        <span className="num absolute right-0.5 top-0.5 grid h-4 min-w-4 place-items-center bg-volt px-1 text-[9px] font-bold text-noir">{count}</span>
    );

    return (
        <>
            <a href="#main" className="sr-only z-[140] bg-volt px-5 py-3 text-sm font-semibold text-noir focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

            <header data-print-hide className={cn('pointer-events-none fixed inset-x-0 top-0 z-50 flex justify-center transition-[padding] duration-700 ease-[var(--ease-out-expo)]', compact ? 'px-3 pt-3' : 'px-0 pt-0')}>
                <motion.nav layout transition={{ duration: 0.7, ease: easeOut }} aria-label="Primary"
                    className={cn('pointer-events-auto relative flex items-center gap-4 overflow-hidden',
                        compact
                            ? 'glass h-14 w-full max-w-[1100px] pl-3 pr-1.5 shadow-2xl shadow-black/30'
                            : 'shell h-[var(--header)] border-b border-transparent bg-transparent')}>
                    <Link href={route('home')} aria-label="BookMyMovie home" className="shrink-0"><Wordmark compact={compact} /></Link>

                    <ul className={cn('hidden items-center lg:flex', compact ? 'ml-1' : 'ml-6')}>
                        {links.map((link) => (
                            <li key={link.label}>
                                <Link href={link.href} aria-current={link.active ? 'page' : undefined}
                                    className={cn('group relative flex items-center px-3.5 text-[11px] font-semibold uppercase tracking-[.09em] [font-stretch:115%] transition-colors xl:px-4',
                                        compact ? 'h-14' : 'h-[var(--header)]', link.active ? 'text-paper' : 'text-mute hover:text-paper')}>
                                    <span className="relative overflow-hidden">
                                        <span className="block transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:-translate-y-full">{t(link.label)}</span>
                                        <span className="absolute inset-0 translate-y-full text-accent transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:translate-y-0" aria-hidden="true">{t(link.label)}</span>
                                    </span>
                                    {link.active && <motion.span layoutId="nav-active" className="absolute inset-x-3.5 bottom-2 h-[2px] bg-volt" aria-hidden="true" />}
                                </Link>
                            </li>
                        ))}
                    </ul>

                    <div className="ml-auto flex items-center gap-1 sm:gap-1.5">
                        {!compact && <Clock />}
                        <button type="button" onClick={() => setSearchOpen(true)} aria-label="Search films and cinemas"
                            className={cn('hidden h-11 items-center gap-3 border border-line-2 pl-3.5 pr-2 text-sm text-mute transition hover:border-paper hover:text-paper', compact ? '' : 'md:flex')}>
                            <Icon name="search" size={16} />
                            <span className="pe-6">{t('Search')}</span>
                            <kbd className="num border border-line-2 px-1.5 py-0.5 text-[10px]">/</kbd>
                        </button>
                        <button type="button" onClick={() => setSearchOpen(true)} className={cn('btn btn-ghost btn-icon', compact ? '' : 'md:hidden')} aria-label="Search"><Icon name="search" size={18} /></button>
                        <LanguageToggle className="hidden xl:inline-flex" />
                        <ThemeToggle className="hidden sm:inline-flex" />
                        <Link href={route('user.wishlist')} className="btn btn-ghost btn-icon relative hidden sm:inline-flex" aria-label={`Watchlist${counts.wishlist ? `, ${counts.wishlist} films` : ''}`}>
                            <Icon name="heart" size={18} />{badge(counts.wishlist)}
                        </Link>
                        <Link href={route('user.cart')} className="btn btn-ghost btn-icon relative" aria-label={`Cart${counts.cart ? `, ${counts.cart} seats held` : ''}`}>
                            <Icon name="bag" size={18} />{badge(counts.cart)}
                        </Link>

                        {auth.user ? (
                            <AccountMenu open={accountOpen} setOpen={setAccountOpen} />
                        ) : (
                            <Link href={route('user.login')} className="btn btn-light btn-sm hidden sm:inline-flex">{t('Sign in')}</Link>
                        )}

                        <Magnetic>
                            <button type="button" onClick={() => setMenuOpen(true)} aria-label="Open menu" aria-expanded={menuOpen} aria-controls="site-menu"
                                className="group flex h-11 items-center gap-2.5 bg-paper pl-3.5 pr-3 text-[11px] font-bold uppercase tracking-[.08em] text-ink transition-colors [font-stretch:115%] hover:bg-volt hover:text-noir">
                                <span className="hidden sm:inline">{t('Menu')}</span>
                                <span className="flex w-4 flex-col gap-[5px]" aria-hidden="true">
                                    <span className="h-[1.5px] w-full bg-current transition-transform duration-300 group-hover:translate-x-0.5" />
                                    <span className="h-[1.5px] w-2/3 bg-current transition-all duration-300 group-hover:w-full" />
                                </span>
                            </button>
                        </Magnetic>
                    </div>

                    <motion.span className="absolute inset-x-0 bottom-0 h-[2px] origin-left bg-volt" style={{ scaleX: progress }} aria-hidden="true" />
                </motion.nav>
            </header>

            <SiteMenu open={menuOpen} onClose={() => setMenuOpen(false)} links={[...links, { label: 'About', href: route('about'), active: isActive(['/about']) }]} />
            <CommandPalette open={searchOpen} onClose={() => setSearchOpen(false)} movies={navMovies} />
        </>
    );
}

function AccountMenu({ open, setOpen }: { open: boolean; setOpen: (value: boolean | ((current: boolean) => boolean)) => void }) {
    const { auth } = useShared();
    const t = useT();
    if (!auth.user) return null;

    return (
        <div className="relative hidden sm:block">
            <button type="button" onClick={() => setOpen((value) => !value)} aria-expanded={open} aria-haspopup="menu" aria-label="Account menu"
                className="flex h-11 items-center gap-2 border border-line-2 pl-1 pr-2.5 text-sm transition hover:border-paper">
                {auth.user.avatar ? (
                    <img src={auth.user.avatar} alt="" className="h-9 w-9 object-cover" />
                ) : (
                    <span className="grid h-9 w-9 place-items-center bg-volt text-lg font-extrabold uppercase text-noir [font-stretch:75%]">{auth.user.name.charAt(0)}</span>
                )}
                <Icon name="chevron-down" size={14} className={cn('transition', open && 'rotate-180')} />
            </button>
            <AnimatePresence>
                {open && (
                    <>
                        <div className="fixed inset-0 z-0" onClick={() => setOpen(false)} aria-hidden="true" />
                        <motion.div role="menu" initial={{ opacity: 0, y: -8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }} transition={{ duration: 0.25 }}
                            className="fixed end-4 top-20 z-10 w-64 border border-line-2 bg-ink-2 p-1.5 shadow-2xl shadow-black/40">
                            <div className="px-3 pb-3 pt-2">
                                <p className="truncate text-sm font-semibold">{auth.user.name}</p>
                                <p className="truncate text-xs text-mute">{auth.user.email}</p>
                            </div>
                            <div className="rule mb-1" />
                            {[['Overview', 'user.dashboard', 'grid'], ['Bookings', 'user.bookings', 'ticket'], ['Watchlist', 'user.wishlist', 'heart'], ['Profile & security', 'user.profile', 'user'], ['Gift cards', 'gift-cards', 'gift']].map(([label, name, icon]) => (
                                <Link key={name} href={route(name)} role="menuitem" className="flex items-center gap-3 px-3 py-2.5 text-sm text-paper-2 transition hover:bg-volt hover:text-noir">
                                    <Icon name={icon} size={16} /> {t(label)}
                                </Link>
                            ))}
                            <Link href={route('user.logout')} method="post" as="button" role="menuitem" className="mt-1 flex w-full items-center gap-3 border-t border-line px-3 py-2.5 text-left text-sm text-mute transition hover:bg-signal hover:text-noir">
                                <Icon name="logout" size={16} /> {t('Sign out')}
                            </Link>
                        </motion.div>
                    </>
                )}
            </AnimatePresence>
        </div>
    );
}

/** What the menu's "New here" panel lists. Links go where each feature lives. */
function useFeatures() {
    const t = useT();
    return [
        { icon: 'qr', title: t('QR e-tickets and Wallet passes'), text: t('Scan in at the door, or add the ticket to Apple or Google Wallet.'), href: route('eticket.info') },
        { icon: 'seat', title: t('Best seats, picked for you'), text: t('One tap finds the best 2 (or 4) seats together.'), href: route('movies.index') },
        { icon: 'popcorn', title: t('Food and drink at checkout'), text: t('Add popcorn, nachos and combos with your seats.'), href: route('movies.index') },
        { icon: 'gift', title: t('Loyalty points and gift cards'), text: t('Earn points on every booking and pay with gift cards.'), href: route('gift-cards') },
        { icon: 'shield', title: t('Two-step sign-in'), text: t('Turn on a one-time email code in your profile.'), href: route('user.profile') },
        { icon: 'language', title: t('Urdu and offline tickets'), text: t('Switch to Urdu. Tickets you open once work without signal.'), href: route('user.bookings') },
        { icon: 'chart', title: t('Admin audit log and refunds'), text: t('Every admin action on record, with one-click refunds.'), href: route('admin.activity') },
        { icon: 'mail', title: t('Emails sent in the background'), text: t('Confirmations queue up, so pages never wait on email.'), href: null },
    ];
}

/** English and Urdu. Posts the choice and reloads the page props in place. */
export function LanguageToggle({ className }: { className?: string }) {
    const locale = useLocale();
    const next = locale === 'ur' ? 'en' : 'ur';
    return (
        <button type="button" onClick={() => router.post(route('locale.update'), { locale: next }, { preserveScroll: true })}
            className={cn('btn btn-ghost btn-sm gap-2', className)} lang={next} aria-label={next === 'ur' ? 'اردو میں دیکھیں' : 'View in English'}>
            <Icon name="language" size={16} />
            <span className={next === 'ur' ? 'font-urdu text-base leading-none' : ''}>{next === 'ur' ? 'اردو' : 'English'}</span>
        </button>
    );
}

/** Full-screen menu: condensed links, what is new, now showing and contact. */
function SiteMenu({ open, onClose, links }: { open: boolean; onClose: () => void; links: { label: string; href: string; active: boolean }[] }) {
    const { auth, navMovies } = useShared();
    const [hovered, setHovered] = useState<number | null>(null);
    const features = useFeatures();
    const t = useT();

    return (
        <AnimatePresence>
            {open && (
                <motion.div id="site-menu" role="dialog" aria-modal="true" aria-label={t('Menu')} data-lenis-prevent
                    initial={{ clipPath: 'inset(0 0 100% 0)' }} animate={{ clipPath: 'inset(0 0 0% 0)' }} exit={{ clipPath: 'inset(0 0 100% 0)' }}
                    transition={{ duration: 0.8, ease }}
                    className="fixed inset-0 z-[70] overflow-y-auto overflow-x-hidden overscroll-contain bg-ink text-paper">
                    <div className="sticky top-0 z-10 border-b border-line bg-ink/90 backdrop-blur-xl">
                        <div className="shell flex h-[var(--header)] items-center justify-between gap-3">
                            <Link href={route('home')} aria-label="BookMyMovie home"><Wordmark /></Link>
                            <div className="flex items-center gap-2">
                                <LanguageToggle className="hidden sm:inline-flex" />
                                <ThemeToggle />
                                <button type="button" onClick={onClose} className="group flex h-11 items-center gap-2.5 bg-volt px-4 text-[11px] font-bold uppercase tracking-[.08em] text-noir [font-stretch:115%]" aria-label={t('Close menu')}>
                                    {t('Close')} <Icon name="close" size={16} className="transition-transform duration-500 group-hover:rotate-90" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="shell grid gap-12 py-10 lg:grid-cols-12 lg:gap-10 lg:py-12">
                        <nav className="lg:col-span-6" aria-label={t('Site')} onPointerLeave={() => setHovered(null)}>
                            <ul>
                                {links.map((link, index) => (
                                    <li key={link.label} className="overflow-hidden border-b border-line" onPointerEnter={() => setHovered(index)}>
                                        <motion.div initial={{ y: '110%' }} animate={{ y: 0 }} exit={{ y: '110%' }} transition={{ duration: 0.9, ease: easeOut, delay: 0.2 + index * 0.05 }}>
                                            <Link href={link.href} className="group flex items-center justify-between gap-6 py-1.5">
                                                <span className={cn('display text-[clamp(3rem,7vw,6.25rem)] transition-[color,opacity,transform] duration-500 ease-[var(--ease-out-expo)] group-hover:translate-x-4 rtl:group-hover:-translate-x-4',
                                                    hovered !== null && hovered !== index ? 'opacity-25' : 'opacity-100', link.active && '!text-accent')}>
                                                    {t(link.label)}
                                                </span>
                                                <span className="flex items-center gap-4">
                                                    <span className="num text-sm text-mute">{pad(index + 1)}</span>
                                                    <Icon name="arrow-up-right" size={28} className="text-dim transition duration-500 group-hover:rotate-45 group-hover:text-accent" />
                                                </span>
                                            </Link>
                                        </motion.div>
                                    </li>
                                ))}
                            </ul>
                            <div className="mt-8 flex flex-wrap gap-2">
                                {auth.user ? (
                                    <>
                                        <Link href={route('user.dashboard')} className="btn btn-primary">{t('My account')}</Link>
                                        <Link href={route('user.bookings')} className="btn btn-ghost">{t('Bookings')}</Link>
                                        <Link href={route('user.logout')} method="post" as="button" className="btn btn-ghost">{t('Sign out')}</Link>
                                    </>
                                ) : (
                                    <>
                                        <Link href={route('user.login')} className="btn btn-primary">{t('Sign in')}</Link>
                                        <Link href={route('user.register')} className="btn btn-ghost">{t('Create account')}</Link>
                                    </>
                                )}
                                <LanguageToggle className="sm:hidden" />
                            </div>

                            <div className="mt-12">
                                <p className="label">{t('Now showing')}</p>
                                <div className="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    {navMovies.slice(0, 4).map((movie) => (
                                        <Link key={movie.slug} href={route('movies.show', movie.slug)} className="group block overflow-hidden" aria-label={movie.title}>
                                            <div className="transition-transform duration-700 ease-[var(--ease-out-expo)] group-hover:scale-105"><Poster movie={movie} size="sm" meta={false} /></div>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </nav>

                        <motion.aside className="space-y-10 lg:col-span-6 lg:border-s lg:border-line lg:ps-10" initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }} transition={{ duration: 0.8, ease: easeOut, delay: 0.4 }}>
                            <div>
                                <p className="label label-accent">{t('New on BookMyMovie')}</p>
                                <ul className="mt-4 grid gap-px border border-line bg-line sm:grid-cols-2">
                                    {features.map((feature) => {
                                        const body = (
                                            <>
                                                <span className="grid h-9 w-9 shrink-0 place-items-center border border-line-2 text-accent transition-colors group-hover:border-accent"><Icon name={feature.icon} size={17} /></span>
                                                <span className="min-w-0">
                                                    <span className="block text-sm font-semibold leading-snug">{feature.title}</span>
                                                    <span className="mt-1 block text-xs leading-relaxed text-mute">{feature.text}</span>
                                                </span>
                                            </>
                                        );
                                        return (
                                            <li key={feature.icon} className="bg-ink">
                                                {feature.href ? (
                                                    <Link href={feature.href} className="group flex h-full gap-3 p-4 transition-colors hover:bg-ink-2">{body}</Link>
                                                ) : (
                                                    <div className="group flex h-full gap-3 p-4">{body}</div>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                            <div className="grid gap-10 sm:grid-cols-2">
                                <div>
                                    <p className="label">{t('Get in touch')}</p>
                                    <a href={`mailto:${CONTACT.email}`} className="link headline mt-4 block break-all text-2xl">{CONTACT.email}</a>
                                    <a href={`tel:${CONTACT.phoneHref}`} dir="ltr" className="link num mt-2 block text-lg text-paper-2">{CONTACT.phone}</a>
                                </div>
                                <div>
                                    <p className="label">{t('Elsewhere')}</p>
                                    <ul className="mt-3 grid">
                                        {[['Portfolio', CONTACT.website], ['GitHub', CONTACT.github], ['LinkedIn', CONTACT.linkedin]].map(([label, href]) => (
                                            <li key={label}>
                                                <a href={href} target="_blank" rel="noopener" className="group flex items-center justify-between border-b border-line py-2.5 text-sm transition-colors hover:text-accent">
                                                    {label} <Icon name="arrow-up-right" size={16} className="transition group-hover:rotate-45" />
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </motion.aside>
                    </div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

function CommandPalette({ open, onClose, movies }: { open: boolean; onClose: () => void; movies: ReturnType<typeof useShared>['navMovies'] }) {
    const [query, setQuery] = useState('');
    const [cursor, setCursor] = useState(0);
    const [navigated, setNavigated] = useState(false);
    const input = useRef<HTMLInputElement>(null);

    const results = useMemo(() => {
        const q = query.toLowerCase().trim();
        return movies.filter((movie) => !q || movie.title.toLowerCase().includes(q) || movie.genre.toLowerCase().includes(q)).slice(0, 6);
    }, [movies, query]);

    useEffect(() => {
        if (open) {
            setQuery('');
            setCursor(0);
            setNavigated(false);
            window.setTimeout(() => input.current?.focus(), 50);
        }
    }, [open]);

    // Enter searches everything for typed text, unless a result was picked with the arrows.
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const picked = results[cursor];
        if (query.trim().length > 1 && !navigated) {
            router.visit(route('search', { query: query.trim() }));
        } else if (picked) {
            router.visit(route('movies.show', picked.slug));
        }
    };

    return (
        <AnimatePresence>
            {open && (
                <>
                    <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} className="fixed inset-0 z-[80] bg-ink/80 backdrop-blur-sm" onClick={onClose} aria-hidden="true" />
                    <motion.div role="dialog" aria-modal="true" aria-label="Search" data-lenis-prevent
                        initial={{ opacity: 0, y: -20 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -12 }} transition={{ duration: 0.4, ease: easeOut }}
                        className="fixed inset-x-4 top-[10vh] z-[81] mx-auto max-w-2xl">
                        <form onSubmit={submit} className="border border-line-2 bg-ink-2 shadow-2xl shadow-black/50">
                            <label className="flex items-center gap-4 border-b border-line px-5">
                                <Icon name="search" size={20} className="text-accent" />
                                <span className="sr-only">Search</span>
                                <input ref={input} value={query} onChange={(event) => { setQuery(event.target.value); setCursor(0); setNavigated(false); }} autoComplete="off" maxLength={120}
                                    onKeyDown={(event) => {
                                        if (event.key === 'ArrowDown') { event.preventDefault(); setNavigated(true); setCursor((value) => Math.min(results.length - 1, value + 1)); }
                                        if (event.key === 'ArrowUp') { event.preventDefault(); setNavigated(true); setCursor((value) => Math.max(0, value - 1)); }
                                    }}
                                    placeholder="Search a film, cinema, actor or director…" role="combobox" aria-expanded="true" aria-controls="palette-results"
                                    className="h-16 flex-1 bg-transparent text-lg placeholder:text-dim focus:outline-none" />
                                <kbd className="num border border-line-2 px-2 py-1 text-[10px] text-mute">ESC</kbd>
                            </label>
                            <div id="palette-results" role="listbox" className="max-h-[55vh] overflow-y-auto p-2">
                                <p className="label px-3 pb-2 pt-2">{query ? 'Matching now showing' : 'Popular right now'}</p>
                                {results.map((movie, index) => (
                                    <Link key={movie.slug} href={route('movies.show', movie.slug)} role="option" aria-selected={index === cursor} onMouseEnter={() => { setCursor(index); setNavigated(true); }}
                                        className={cn('flex items-center justify-between px-3 py-3 transition', index === cursor ? 'bg-volt text-noir' : '')}>
                                        <span>
                                            <span className="headline block text-2xl">{movie.title}</span>
                                            <span className={cn('num block text-[11px] uppercase', index === cursor ? 'text-noir/60' : 'text-mute')}>{movie.genre} · {movie.duration}</span>
                                        </span>
                                        <Icon name="arrow-up-right" size={18} />
                                    </Link>
                                ))}
                                {query.trim().length > 1 && (
                                    <button type="button" onClick={() => router.visit(route('search', { query: query.trim() }))} className="mt-1 flex w-full items-center justify-between px-3 py-3 text-left text-sm text-paper-2 transition hover:bg-ink-3">
                                        <span>Search everything for “<span className="text-paper">{query}</span>”</span>
                                        <Icon name="arrow-right" size={16} />
                                    </button>
                                )}
                            </div>
                        </form>
                    </motion.div>
                </>
            )}
        </AnimatePresence>
    );
}
