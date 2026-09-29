import { Link } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { LanguageToggle } from '@/components/shell/Navbar';
import { useT } from '@/lib/i18n';
import { route, useShared } from '@/lib/utils';

const promises = ['No booking fees', 'Live seat maps', 'IMAX · Dolby · 4DX', 'Pay at the counter', 'Free cancellation until 2h before', 'Open source'];

export default function Footer() {
    const { site, footer } = useShared();
    const t = useT();

    const columns: { title: string; links: [string, string][] }[] = [
        {
            title: 'Explore',
            links: [
                ['Now showing', route('movies.status', 'now-showing')],
                ['Coming soon', route('movies.status', 'coming-soon')],
                ['Cinemas', route('cinemas.index')],
                ['Offers', route('offers')],
                ['Gift cards', route('gift-cards')],
                ['Compare films', route('movies.compare')],
                ['Search', route('search')],
            ],
        },
        { title: 'Genres', links: footer.genres.slice(0, 7).map((genre) => [genre.name, route('movies.genre', genre.slug)]) },
        {
            title: 'Help',
            links: [['FAQ', route('faq')], ['Contact', route('contact')], ['E-tickets', route('eticket.info')], ['Refunds', route('refund')], ['Accessibility', route('accessibility')], ['About us', route('about')]],
        },
    ];

    return (
        <footer className="relative mt-32 overflow-hidden border-t border-line bg-ink" data-print-hide>
            <div className="marquee overflow-hidden border-b border-line bg-volt py-3 text-noir" aria-hidden="true">
                <div className="marquee-track marquee-track-fast">
                    {[0, 1].map((loop) => (
                        <div key={loop} className="flex">
                            {promises.map((item) => (
                                <span key={item} className="flex items-center gap-6 pe-6 text-2xl font-extrabold uppercase [font-stretch:62%] sm:text-3xl">
                                    {t(item)} <Icon name="ticket" size={18} stroke={2} />
                                </span>
                            ))}
                        </div>
                    ))}
                </div>
            </div>

            <div className="shell pt-20">
                <div className="grid gap-16 lg:grid-cols-12">
                    <div className="lg:col-span-5">
                        <img src={site.logo_url} alt={site.name} width={280} height={210} loading="lazy" decoding="async" className="mb-8 h-24 w-auto" />
                        <p className="display text-[clamp(3.5rem,7vw,6.5rem)]">{t('See it on the')} <span className="text-accent">{t('big')}</span> {t('screen.')}</p>
                        <p className="lede mt-6 max-w-md">{site.footer_description}</p>
                        <div className="mt-8 flex flex-wrap gap-2">
                            <Link href={route('movies.index')} className="btn btn-primary">{t('Browse showtimes')} <Icon name="arrow-right" size={16} className="arrow" /></Link>
                            <Link href={route('about')} className="btn btn-ghost">{t('About the project')}</Link>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-10 sm:grid-cols-4 lg:col-span-7">
                        {columns.map((column) => (
                            <div key={column.title}>
                                <p className="label">{t(column.title)}</p>
                                <ul className="mt-5 space-y-3 text-sm">
                                    {column.links.map(([label, href]) => (
                                        <li key={label}><Link href={href} className="link text-paper-2 hover:text-paper">{column.title === 'Genres' ? label : t(label)}</Link></li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                        <div>
                            <p className="label">{t('Cinemas')}</p>
                            <ul className="mt-5 space-y-3 text-sm">
                                {footer.cinemas.map((cinema) => (
                                    <li key={cinema.slug}>
                                        <Link href={route('cinemas.show', cinema.slug)} className="group block text-paper-2 hover:text-paper">
                                            <span className="link">{cinema.name}</span>
                                            <span className="label mt-1 block text-[9px] text-dim">{cinema.city}</span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>

                        </div>
                    </div>
                </div>

                <div className="grid-lines mt-20 sm:grid-cols-2 lg:grid-cols-4" aria-label="Contact">
                    <a href={`mailto:${site.support_email}`} className="group p-6 transition-colors hover:!bg-volt hover:text-noir sm:col-span-2">
                        <span className="label group-hover:text-noir/60">{t('Email support')}</span>
                        <span className="headline mt-3 block break-all text-3xl sm:text-4xl">{site.support_email}</span>
                    </a>
                    <a href={`tel:${site.support_phone.replace(/[^\d+]/g, '')}`} className="group p-6 transition-colors hover:!bg-volt hover:text-noir">
                        <span className="label group-hover:text-noir/60">{t('Call')}</span>
                        <span className="num mt-3 block text-xl" dir="ltr">{site.support_phone}</span>
                    </a>
                    <div className="p-6">
                        <span className="label">{t('Based in')}</span>
                        <span className="mt-3 block text-lg font-semibold">{site.contact_address}</span>
                    </div>
                </div>

                {/* One cell per link, however many are configured: never an empty block. */}
                {site.socials.length > 0 && (
                    <div className="grid-lines footer-socials border-t-0" style={{ ['--socials' as string]: site.socials.length }} aria-label="Elsewhere">
                        {site.socials.map((social) => (
                            <a key={social.key} href={social.url} target="_blank" rel="noopener me" className="group flex items-center justify-between gap-3 p-6 transition-colors hover:!bg-volt hover:text-noir">
                                <span className="flex items-center gap-3 text-lg font-semibold">{['github', 'linkedin'].includes(social.key) ? <Icon name={social.key} size={18} /> : <Icon name="globe" size={18} />} {social.label}</span>
                                <Icon name="arrow-up-right" size={16} className="transition group-hover:rotate-45" />
                            </a>
                        ))}
                    </div>
                )}

                {/* A watermark, not text: drawn from a data attribute so screen readers
                    and contrast checkers do not treat it as content. */}
                <div data-watermark={site.name} className="pointer-events-none mt-24 select-none whitespace-nowrap text-center text-[15.5vw] font-black uppercase leading-[.78] tracking-[-0.03em] text-paper/[.06] [font-stretch:62%] after:content-[attr(data-watermark)]" aria-hidden="true" />

                <div className="flex flex-col gap-6 border-t border-line py-8 text-xs text-mute md:flex-row md:items-center md:justify-between">
                    <p dir="ltr" className="rtl:text-right">© {new Date().getFullYear()} {site.name} · {site.copyright_note}</p>
                    <ul className="flex flex-wrap items-center gap-x-6 gap-y-2">
                        {[['Terms', route('terms')], ['Privacy', route('privacy')], ['Cookies', route('cookies')], ['Refunds', route('refund')]].map(([label, href]) => (
                            <li key={label}><Link href={href} className="link hover:text-paper">{t(label)}</Link></li>
                        ))}
                        <li><a href="/sitemap.xml" className="link hover:text-paper">{t('Sitemap')}</a></li>
                        <li><LanguageToggle className="!h-9" /></li>
                    </ul>
                </div>
            </div>
        </footer>
    );
}
