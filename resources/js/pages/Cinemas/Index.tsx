import { Link } from '@inertiajs/react';
import Icon from '@/components/Icon';
import { CountUp, Reveal } from '@/components/motion';
import { PageHeader } from '@/components/ui';
import { cn, plural, route } from '@/lib/utils';

type Cinema = { slug: string; name: string; address: string; amenities: string; screens: number; seats: number; today: number; formats: string[] };

interface Props {
    cities: { name: string; slug: string; province: string; cinemas: Cinema[] }[];
    totals: { cinemas: number; screens: number; seats: number; cities: number };
}

export default function CinemasIndex({ cities, totals }: Props) {
    return (
        <>
            <PageHeader label="Our cinemas" title="Rooms worth leaving the house for" accent={['leaving']}
                lede="From a restored seafront picture palace to a boutique ScreenX room, every partner venue shares one seat map, one price list and one standard of care." />

            <section className="pt-14">
                <div className="shell">
                    <dl className="grid-lines grid-cols-2 lg:grid-cols-4">
                        {([[totals.cinemas, 'Cinemas'], [totals.cities, 'Cities'], [totals.screens, 'Screens'], [totals.seats, 'Seats']] as [number, string][]).map(([value, label]) => (
                            <div key={label} className="px-5 py-10">
                                <dd><CountUp value={value} className="display block text-7xl tabular" /></dd>
                                <dt className="label mt-3">{label}</dt>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            {cities.map((city) => (
                <section key={city.slug} id={`city-${city.slug}`} className="scroll-mt-24 pt-24" aria-labelledby={`city-title-${city.slug}`}>
                    <div className="shell grid grid-cols-1 gap-10 lg:grid-cols-12">
                        <div className="min-w-0 lg:col-span-3">
                            <p className="label">{city.province}</p>
                            <h2 id={`city-title-${city.slug}`} className="display mt-3 text-[clamp(3rem,13vw,6rem)] [overflow-wrap:anywhere]">{city.name}</h2>
                            <p className="mt-3 text-sm text-mute">{plural(city.cinemas.length, 'cinema')}</p>
                        </div>
                        <div className="grid-lines grid-fill-odd min-w-0 md:grid-cols-2 lg:col-span-9">
                            {city.cinemas.map((cinema, index) => (
                                <Reveal as="article" key={cinema.slug} delay={index * 80} className="group flex min-w-0 flex-col p-5 sm:p-8">
                                    <div className="flex items-start justify-between gap-4">
                                        <h3 className="headline min-w-0 text-3xl [overflow-wrap:anywhere] sm:text-4xl"><Link href={route('cinemas.show', cinema.slug)} className="link">{cinema.name}</Link></h3>
                                        <span className={cn('tag shrink-0', cinema.today > 0 && 'tag-mint')}>{cinema.today} today</span>
                                    </div>
                                    <p className="mt-4 flex items-start gap-2 text-sm text-mute"><Icon name="pin" size={16} className="mt-0.5 text-dim" /> {cinema.address}</p>
                                    <div className="mt-6 flex flex-wrap gap-1.5">
                                        {cinema.formats.map((format) => <span key={format} className="tag">{format}</span>)}
                                    </div>
                                    {cinema.amenities && <p className="mt-6 text-sm text-mute">{cinema.amenities}</p>}
                                    <div className="mt-auto flex items-center justify-between border-t border-line pt-6 text-sm">
                                        <span className="num text-xs text-mute">{cinema.screens} screens · {cinema.seats} seats</span>
                                        <Link href={route('cinemas.show', cinema.slug)} className="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[.08em] text-accent [font-stretch:115%]">
                                            Showtimes <Icon name="arrow-right" size={14} className="transition group-hover:translate-x-1" />
                                        </Link>
                                    </div>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>
            ))}
        </>
    );
}
