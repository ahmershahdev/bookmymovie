import { Link } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { CONTACT } from '@/components/shell/Navbar';
import { route, useShared } from '@/lib/utils';

const promises = ['No booking fees', 'Live seat maps', 'IMAX · Dolby · 4DX', 'Pay at the counter', 'Free cancellation until 2h before', 'Open source'];

export default function Footer() {
    const { site, footer } = useShared();

    const columns: { title: string; links: [string, string][] }[] = [
        {
            title: 'Explore',
            links: [
                ['Now showing', route('movies.status', 'now-showing')],
                ['Coming soon', route('movies.status', 'coming-soon')],
                ['Cinemas', route('cinemas.index')],
                ['Offers', route('offers')],
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
                                <span key={item} className="flex items-center gap-6 pr-6 text-2xl font-extrabold uppercase [font-stretch:62%] sm:text-3xl">
                                    {item} <Icon name="ticket" size={18} stroke={2} />
                                </span>
                            ))}
                        </div>
                    ))}
                </div>
            </div>

            <div className="shell pt-20">
                <div className="grid gap-16 lg:grid-cols-12">
                    <div className="lg:col-span-5">
                        <p className="display text-[clamp(3.5rem,7vw,6.5rem)]">See it on the <span className="text-accent">big</span> screen.</p>
                        <p className="lede mt-6 max-w-md">{site.footer_description}</p>
                        <div className="mt-8 flex flex-wrap gap-2">
                            <Link href={route('movies.index')} className="btn btn-primary">Browse showtimes <Icon name="arrow-right" size={16} className="arrow" /></Link>
                            <a href={CONTACT.github} rel="noopener" target="_blank" className="btn btn-ghost"><Icon name="github" size={16} /> GitHub</a>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-10 sm:grid-cols-4 lg:col-span-7">
                        {columns.map((column) => (
                            <div key={column.title}>
                                <p className="label">{column.title}</p>
                                <ul className="mt-5 space-y-3 text-sm">
                                    {column.links.map(([label, href]) => (
                                        <li key={label}><Link href={href} className="link text-paper-2 hover:text-paper">{label}</Link></li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                        <div>
                            <p className="label">Cinemas</p>
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

                <div className="grid-lines mt-20 sm:grid-cols-2 lg:grid-cols-5" aria-label="Contact">
                    <a href={`mailto:${CONTACT.email}`} className="group p-6 transition-colors hover:!bg-volt hover:text-noir sm:col-span-2">
                        <span className="label group-hover:text-noir/60">Email support</span>
                        <span className="headline mt-3 block break-all text-3xl sm:text-4xl">{CONTACT.email}</span>
                    </a>
                    <a href={`tel:${CONTACT.phoneHref}`} className="group p-6 transition-colors hover:!bg-volt hover:text-noir">
                        <span className="label group-hover:text-noir/60">Call</span>
                        <span className="num mt-3 block text-xl">{CONTACT.phone}</span>
                    </a>
                    {[['Portfolio', 'ahmershah.dev', CONTACT.website, 'globe'], ['GitHub', 'ahmershahdev', CONTACT.github, 'github']].map(([label, handle, href, icon]) => (
                        <a key={label} href={href} target="_blank" rel="noopener" className="group flex flex-col justify-between p-6 transition-colors hover:!bg-volt hover:text-noir">
                            <span className="label flex items-center justify-between group-hover:text-noir/60">{label} <Icon name="arrow-up-right" size={14} className="transition group-hover:rotate-45" /></span>
                            <span className="mt-3 flex items-center gap-2 text-lg font-semibold"><Icon name={icon} size={18} /> {handle}</span>
                        </a>
                    ))}
                    <a href={CONTACT.linkedin} target="_blank" rel="noopener" className="group flex flex-col justify-between p-6 transition-colors hover:!bg-volt hover:text-noir sm:col-span-2 lg:col-span-5">
                        <span className="label flex items-center justify-between group-hover:text-noir/60">LinkedIn <Icon name="arrow-up-right" size={14} className="transition group-hover:rotate-45" /></span>
                        <span className="mt-3 flex items-center gap-2 text-lg font-semibold"><Icon name="linkedin" size={18} /> Syed Ahmer Shah · linkedin.com/in/syedahmershah</span>
                    </a>
                </div>

                <p className="pointer-events-none mt-24 select-none whitespace-nowrap text-center text-[15.5vw] font-black uppercase leading-[.78] tracking-[-0.03em] text-paper/[.06] [font-stretch:62%]" aria-hidden="true">
                    {site.name}
                </p>

                <div className="flex flex-col gap-6 border-t border-line py-8 text-xs text-mute md:flex-row md:items-center md:justify-between">
                    <p>© {new Date().getFullYear()} {site.name}. {site.copyright_note}</p>
                    <ul className="flex flex-wrap gap-x-6 gap-y-2">
                        {[['Terms', route('terms')], ['Privacy', route('privacy')], ['Cookies', route('cookies')], ['Refunds', route('refund')]].map(([label, href]) => (
                            <li key={label}><Link href={href} className="link hover:text-paper">{label}</Link></li>
                        ))}
                        <li><a href="/sitemap.xml" className="link hover:text-paper">Sitemap</a></li>
                    </ul>
                </div>
            </div>
        </footer>
    );
}
