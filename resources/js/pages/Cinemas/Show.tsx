import { Link } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '@/components/Icon';
import Poster from '@/components/Poster';
import { PageHeader, SectionHeading } from '@/components/ui';
import { cn, route } from '@/lib/utils';
import type { MovieCard } from '@/types';

interface Props {
    theater: {
        name: string;
        slug: string;
        description: string | null;
        address: string;
        city: string;
        province: string | null;
        hours: string;
        phone: string;
        map_url: string;
        amenities: { name: string; description: string; icon: string }[];
        screens: { name: string; format: string; sound: string; seats: number }[];
    };
    days: { date: string; weekday: string; day: string; schedule: { movie: MovieCard; shows: { id: number; time: string; screen: string; format: string; left: number }[] }[] }[];
    nearby: { slug: string; name: string; city: string }[];
}

export default function CinemaShow({ theater, days, nearby }: Props) {
    const [date, setDate] = useState(days[0]?.date ?? '');
    const schedule = days.find((day) => day.date === date)?.schedule ?? [];
    return (
        <>
            <PageHeader label={[theater.city, theater.province].filter(Boolean).join(' · ')} title={theater.name} lede={theater.description}>
                <div className="grid-lines mt-12 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div className="p-5"><p className="label">Address</p><p className="mt-2 text-paper-2">{theater.address}</p></div>
                    <div className="p-5"><p className="label">Hours</p><p className="num mt-2 text-paper-2">{theater.hours}</p></div>
                    <div className="p-5"><p className="label">Box office</p><p className="num mt-2"><a className="link" href={`tel:${theater.phone.replace(/\s+/g, '')}`}>{theater.phone}</a></p></div>
                    <div className="p-5"><p className="label">Getting there</p><p className="mt-2"><a className="link inline-flex items-center gap-1 text-accent" href={theater.map_url} target="_blank" rel="noopener">Open in Maps <Icon name="arrow-up-right" size={14} /></a></p></div>
                </div>
            </PageHeader>

            <section id="programme" className="scroll-mt-24 pt-16" aria-labelledby="programme-title">
                <div className="shell">
                    <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                        <SectionHeading label="Programme" title="What’s on" id="programme-title" />
                        <nav className="no-scrollbar flex gap-1 overflow-x-auto pb-1" aria-label="Choose a date">
                            {days.map((day) => (
                                <button key={day.date} type="button" onClick={() => setDate(day.date)} aria-pressed={day.date === date}
                                    className={cn('flex min-w-[4.75rem] flex-col items-center border px-3 py-2.5 transition', day.date === date ? 'border-volt bg-volt text-noir' : 'border-line-2 text-paper-2 hover:border-paper')}>
                                    <span className="text-[10px] font-bold uppercase tracking-[.1em] [font-stretch:115%]">{day.weekday}</span>
                                    <span className="display text-4xl !text-inherit">{day.day}</span>
                                </button>
                            ))}
                        </nav>
                    </div>

                    <div className="mt-12 border-t border-line">
                        {schedule.length ? schedule.map(({ movie, shows }) => (
                            <article key={movie.id} className="grid gap-6 border-b border-line py-10 md:grid-cols-12">
                                <Link href={route('movies.show', movie.slug)} className="max-w-[10rem] md:col-span-2" tabIndex={-1} aria-hidden="true"><Poster movie={movie} size="sm" /></Link>
                                <div className="md:col-span-4">
                                    <p className="label">{movie.genre}</p>
                                    <h3 className="headline mt-2 text-4xl"><Link href={route('movies.show', movie.slug)} className="link">{movie.title}</Link></h3>
                                    <p className="num mt-2 text-[11px] uppercase text-mute">{movie.duration} · {movie.certificate} · {movie.language}</p>
                                    <p className="mt-4 line-clamp-2 text-sm text-mute">{movie.tagline}</p>
                                </div>
                                <ul className="flex flex-wrap content-start gap-2 md:col-span-6">
                                    {shows.map((show) => (
                                        <li key={show.id}>
                                            <Link href={route('movies.seats', { slug: movie.slug, show: show.id })} className="group relative isolate flex min-w-[8.5rem] flex-col overflow-hidden border border-line-2 px-4 py-3 transition hover:border-accent">
                                                <span className="absolute inset-0 -z-10 origin-bottom scale-y-0 bg-volt transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:scale-y-100" />
                                                <span className="num text-xl font-semibold group-hover:text-noir">{show.time}</span>
                                                <span className="label mt-1 text-[9px] group-hover:text-noir/70">{show.screen} · {show.format}</span>
                                                <span className={cn('num mt-1 text-xs group-hover:text-noir', show.left < 20 ? 'text-signal' : 'text-mute')}>{show.left} seats left</span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </article>
                        )) : (
                            <p className="py-16 text-center text-mute">No more shows on this date. Try another day above.</p>
                        )}
                    </div>
                </div>
            </section>

            <section className="pt-28" aria-labelledby="facilities">
                <div className="shell grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-4"><SectionHeading label="Facilities" title="The venue" accent={['venue']} id="facilities" /></div>
                    <div className="lg:col-span-8">
                        <ul className="grid-lines grid-fill-odd sm:grid-cols-2">
                            {theater.amenities.map((amenity) => (
                                <li key={amenity.name} className="flex gap-4 p-5">
                                    <span className="grid h-11 w-11 shrink-0 place-items-center bg-volt text-noir"><Icon name={amenity.icon} size={18} /></span>
                                    <span><span className="block font-semibold">{amenity.name}</span><span className="mt-1 block text-sm text-mute">{amenity.description}</span></span>
                                </li>
                            ))}
                        </ul>
                        <h3 className="label mt-14">Screens</h3>
                        <div className="mt-4 overflow-x-auto border border-line" data-lenis-prevent>
                            <table className="w-full text-left text-sm">
                                <thead className="bg-ink-2"><tr className="label text-[10px]"><th className="px-5 py-3 font-semibold">Screen</th><th className="px-5 py-3 font-semibold">Format</th><th className="hidden px-5 py-3 font-semibold sm:table-cell">Sound</th><th className="px-5 py-3 text-right font-semibold">Seats</th></tr></thead>
                                <tbody className="divide-y divide-line">
                                    {theater.screens.map((screen) => (
                                        <tr key={screen.name}><td className="px-5 py-4">{screen.name}</td><td className="px-5 py-4 text-paper-2">{screen.format}</td><td className="hidden px-5 py-4 text-mute sm:table-cell">{screen.sound}</td><td className="num px-5 py-4 text-right text-paper-2">{screen.seats}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            {nearby.length > 0 && (
                <section className="pt-28">
                    <div className="shell">
                        <p className="label">Other cinemas</p>
                        <div className="grid-lines mt-6 md:grid-cols-3">
                            {nearby.map((cinema) => (
                                <Link key={cinema.slug} href={route('cinemas.show', cinema.slug)} className="group flex items-center justify-between p-6 transition-colors hover:!bg-volt hover:text-noir">
                                    <span><span className="headline block text-3xl">{cinema.name}</span><span className="text-sm text-mute group-hover:text-noir/70">{cinema.city}</span></span>
                                    <Icon name="arrow-up-right" size={20} className="transition group-hover:rotate-45" />
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </>
    );
}
