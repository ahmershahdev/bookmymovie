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

function useActive() {
    const { url } = usePage();
    const path = url.split('?')[0];
    return (prefixes: string[]) => prefixes.some((prefix) => (prefix === '/' ? path === '/' : path === prefix || path.startsWith(prefix + '/')));
}

/** The brand mark: the uploaded logo (Admin → Site & brand), sized for the bar it sits in. */
export function Wordmark({ className, compact = false }: { className?: string; compact?: boolean }) {
    const { site } = useShared();
    return (
        <span className={cn('flex items-center', className)}>
            <img src={site.logo_url} alt={site.name} width={280} height={210} decoding="async"
                className={cn('w-auto object-contain drop-shadow-[0_4px_14px_rgba(0,0,0,.45)] transition-[height] duration-500 ease-[var(--ease-out-expo)]', compact ? 'h-9' : 'h-11 lg:h-12')} />
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

type MenuLink = { label: string; href: string; active: boolean };

/** One titled group of small links in the menu. */
function MenuGroup({ title, links, delay }: { title: string; links: { label: string; href: string; icon?: string; note?: string }[]; delay: number }) {
    return (
        <motion.div initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }} transition={{ duration: 0.7, ease: easeOut, delay }}>
            <p className="label">{title}</p>
            <ul className="mt-4 border-t border-line">
                {links.map((link) => (
                    <li key={link.label} className="border-b border-line">
                        <Link href={link.href} className="group flex items-center justify-between gap-3 py-3 text-sm text-paper-2 transition-colors hover:text-accent">
                            <span className="flex items-center gap-3">
                                {link.icon && <Icon name={link.icon} size={16} className="text-dim transition-colors group-hover:text-accent" />}
                                {link.label}
                                {link.note && <span className="tag tag-volt !px-1.5 !py-0 text-[9px]">{link.note}</span>}
                            </span>
                            <Icon name="arrow-right" size={14} className="-translate-x-2 opacity-0 transition duration-300 group-hover:translate-x-0 group-hover:opacity-100 rtl:rotate-180" />
                        </Link>
                    </li>
                ))}
            </ul>
        </motion.div>
    );
}

/**
 * Full-screen menu, three columns: where to go (big type), grouped
 * shortcuts (account, help, what's new), and what's on plus how to reach us.
 */
function SiteMenu({ open, onClose, links }: { open: boolean; onClose: () => void; links: MenuLink[] }) {
    const { auth, navMovies, site } = useShared();
    const [hovered, setHovered] = useState<number | null>(null);
    const [query, setQuery] = useState('');
    const features = useFeatures();
    const t = useT();

    const account = auth.user
        ? [
            { label: t('Overview'), href: route('user.dashboard'), icon: 'grid' },
            { label: t('Bookings'), href: route('user.bookings'), icon: 'ticket' },
            { label: t('Watchlist'), href: route('user.wishlist'), icon: 'heart' },
            { label: t('Profile & security'), href: route('user.profile'), icon: 'user' },
            { label: t('Public profile'), href: route('profile.show', auth.user.username), icon: 'star' },
        ]
        : [
            { label: t('Sign in'), href: route('user.login'), icon: 'user' },
            { label: t('Create account'), href: route('user.register'), icon: 'plus' },
            { label: t('Find my e-ticket'), href: route('eticket.info'), icon: 'ticket' },
        ];
    const help = [
        { label: t('FAQ'), href: route('faq'), icon: 'info' },
        { label: t('Contact'), href: route('contact'), icon: 'mail' },
        { label: t('E-tickets'), href: route('eticket.info'), icon: 'qr' },
        { label: t('Refunds'), href: route('refund'), icon: 'refresh' },
        { label: t('Accessibility'), href: route('accessibility'), icon: 'wheelchair' },
    ];
    const extras = [
        { label: t('Gift cards'), href: route('gift-cards'), icon: 'gift', note: t('New') },
        { label: t('Offers'), href: route('offers'), icon: 'tag' },
        { label: t('Compare films'), href: route('movies.compare'), icon: 'compare' },
        { label: t('Coming soon'), href: route('movies.status', 'coming-soon'), icon: 'calendar' },
    ];

    return (
        <AnimatePresence>
            {open && (
                <motion.div id="site-menu" role="dialog" aria-modal="true" aria-label={t('Menu')} data-lenis-prevent
                    initial={{ clipPath: 'inset(0 0 100% 0)' }} animate={{ clipPath: 'inset(0 0 0% 0)' }} exit={{ clipPath: 'inset(0 0 100% 0)' }}
                    transition={{ duration: 0.8, ease }}
                    className="fixed inset-0 z-[70] overflow-y-auto overflow-x-hidden overscroll-contain bg-ink text-paper">
                    <div className="sticky top-0 z-10 border-b border-line bg-ink/90 backdrop-blur-xl">
                        <div className="shell flex h-[var(--header)] items-center justify-between gap-3">
                            <Link href={route('home')} aria-label={`${site.name} home`}><Wordmark /></Link>
                            <form onSubmit={(event) => { event.preventDefault(); if (query.trim().length > 1) router.visit(route('search', { query: query.trim() })); }}
                                className="mx-4 hidden max-w-md flex-1 items-center gap-3 border border-line-2 px-3.5 transition-colors focus-within:border-accent md:flex" role="search">
                                <Icon name="search" size={16} className="text-mute" />
                                <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('Search films, cinemas, people…')} aria-label={t('Search')}
                                    className="h-11 flex-1 bg-transparent text-sm placeholder:text-dim focus:outline-none" />
                            </form>
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
                        <nav className="lg:col-span-5" aria-label={t('Site')} onPointerLeave={() => setHovered(null)}>
                            <p className="label mb-4">{t('Explore')}</p>
                            <ul>
                                {links.map((link, index) => (
                                    <li key={link.label} className="overflow-hidden border-b border-line" onPointerEnter={() => setHovered(index)}>
                                        <motion.div initial={{ y: '110%' }} animate={{ y: 0 }} exit={{ y: '110%' }} transition={{ duration: 0.9, ease: easeOut, delay: 0.15 + index * 0.05 }}>
                                            <Link href={link.href} className="group flex items-center justify-between gap-6 py-1">
                                                <span className={cn('display text-[clamp(2.75rem,5.6vw,5.25rem)] transition-[color,opacity,transform] duration-500 ease-[var(--ease-out-expo)] group-hover:translate-x-3 rtl:group-hover:-translate-x-3',
                                                    hovered !== null && hovered !== index ? 'opacity-25' : 'opacity-100', link.active && '!text-accent')}>
                                                    {t(link.label)}
                                                </span>
                                                <span className="flex items-center gap-4">
                                                    <span className="num text-sm text-mute">{pad(index + 1)}</span>
                                                    <Icon name="arrow-up-right" size={24} className="text-dim transition duration-500 group-hover:rotate-45 group-hover:text-accent" />
                                                </span>
                                            </Link>
                                        </motion.div>
                                    </li>
                                ))}
                            </ul>
                            {auth.user && (
                                <Link href={route('user.logout')} method="post" as="button" className="btn btn-ghost btn-sm mt-8"><Icon name="logout" size={14} /> {t('Sign out')}</Link>
                            )}
                        </nav>

                        <div className="grid gap-10 sm:grid-cols-2 lg:col-span-4 lg:grid-cols-1 xl:grid-cols-2 lg:border-s lg:border-line lg:ps-10">
                            <MenuGroup title={auth.user ? t('Your account') : t('Account')} links={account} delay={0.3} />
                            <MenuGroup title={t('More to do')} links={extras} delay={0.38} />
                            <MenuGroup title={t('Help')} links={help} delay={0.46} />
                            <MenuGroup title={t('New on BookMyMovie')} links={features.map((feature) => ({ label: feature.title, href: feature.href ?? route('about'), icon: feature.icon }))} delay={0.54} />
                        </div>

                        <motion.aside className="space-y-10 lg:col-span-3" initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }} transition={{ duration: 0.8, ease: easeOut, delay: 0.5 }}>
                            <div>
                                <p className="label">{t('Now showing')}</p>
                                <div className="mt-4 grid grid-cols-2 gap-2">
                                    {navMovies.slice(0, 4).map((movie) => (
                                        <Link key={movie.slug} href={route('movies.show', movie.slug)} className="group block overflow-hidden" aria-label={movie.title}>
                                            <div className="transition-transform duration-700 ease-[var(--ease-out-expo)] group-hover:scale-105"><Poster movie={movie} size="sm" meta={false} /></div>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <p className="label">{t('Get in touch')}</p>
                                <a href={`mailto:${site.support_email}`} className="link mt-4 block break-all text-lg font-semibold">{site.support_email}</a>
                                <a href={`tel:${site.support_phone.replace(/[^\d+]/g, '')}`} dir="ltr" className="link num mt-1 block text-paper-2">{site.support_phone}</a>
                                <div className="mt-5 flex flex-wrap gap-1.5">
                                    {site.socials.map((social) => (
                                        <a key={social.key} href={social.url} target="_blank" rel="noopener" className="chip">{social.label} <Icon name="arrow-up-right" size={11} /></a>
                                    ))}
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
