import { Link } from '@inertiajs/react';
import { AnimatePresence, motion } from 'motion/react';
import { useDeferredValue, useMemo, useState } from 'react';
import Dropdown from '@/components/Dropdown';
import Icon from '@/components/Icon';
import MovieCard from '@/components/MovieCard';
import { EmptyState, PageHeader } from '@/components/ui';
import { plural, route, useShared } from '@/lib/utils';
import type { MovieCard as Movie } from '@/types';

type CatalogueMovie = Movie & { genre_slugs: string[]; released_at: string | null; people: string };

interface Props {
    movies: CatalogueMovie[];
    genres: { name: string; slug: string; count: number }[];
    fixedGenre: { name: string; slug: string } | null;
    status: string | null;
    statuses: { slug: string; label: string }[];
    languages: string[];
    certificates: string[];
    sorts: Record<string, string>;
    statusCounts: Record<string, number>;
}

const PAGE = 12;

export default function MoviesIndex({ movies, genres, fixedGenre, status, statuses, languages, certificates, sorts, statusCounts }: Props) {
    const { site } = useShared();
    const [search, setSearch] = useState('');
    const [genre, setGenre] = useState('');
    const [language, setLanguage] = useState('');
    const [certificate, setCertificate] = useState('');
    const [sort, setSort] = useState('popular');
    const [limit, setLimit] = useState(PAGE);
    const query = useDeferredValue(search.trim().toLowerCase());

    // Everything filters on screen: no reloads, no ?query=strings.
    const results = useMemo(() => {
        const filtered = movies.filter((movie) =>
            (!query || `${movie.title} ${movie.tagline} ${movie.genre} ${movie.people} ${movie.language}`.toLowerCase().includes(query))
            && (!genre || movie.genre_slugs.includes(genre))
            && (!language || movie.language === language)
            && (!certificate || movie.certificate === certificate));

        const sorted = [...filtered];
        switch (sort) {
            case 'rating': sorted.sort((a, b) => b.rating - a.rating || b.reviews - a.reviews); break;
            case 'newest': sorted.sort((a, b) => (b.released_at ?? '').localeCompare(a.released_at ?? '')); break;
            case 'title': sorted.sort((a, b) => a.title.localeCompare(b.title)); break;
            case 'runtime': sorted.sort((a, b) => a.duration_minutes - b.duration_minutes); break;
        }
        return sorted;
    }, [movies, query, genre, language, certificate, sort]);

    const reset = () => {
        setSearch('');
        setGenre('');
        setLanguage('');
        setCertificate('');
        setSort('popular');
        setLimit(PAGE);
    };
    const filtering = Boolean(search || genre || language || certificate);
    const activeStatus = statuses.find((item) => item.slug === status);
    const title = fixedGenre ? `${fixedGenre.name} films` : activeStatus ? activeStatus.label : 'Every film every showtime';

    return (
        <>
            <PageHeader
                label={fixedGenre ? 'Genre' : (site.catalog_heading ?? 'In cinemas')}
                title={title}
                accent={fixedGenre ? ['films'] : activeStatus ? [] : ['every']}
                lede={fixedGenre ? `${plural(movies.length, fixedGenre.name.toLowerCase() + ' film')} in our cinemas now or opening soon.` : (site.catalog_intro ?? 'Browse what is playing this week and what opens next. Filter, sort, then pick a seat.')}
            />

            <section className="sticky top-0 z-30 border-b border-line bg-ink/90 backdrop-blur-xl">
                <div className="shell py-4">
                    {!fixedGenre && (
                        <nav className="no-scrollbar flex gap-1 overflow-x-auto" aria-label="Filter by status">
                            <Link href={route('movies.index')} preserveScroll aria-current={!status ? 'page' : undefined} className="chip">
                                In cinemas <span className="num opacity-60">{(statusCounts.now_showing ?? 0) + (statusCounts.coming_soon ?? 0)}</span>
                            </Link>
                            {statuses.map((item) => (
                                <Link key={item.slug} href={route('movies.status', item.slug)} preserveScroll aria-current={status === item.slug ? 'page' : undefined} className="chip">
                                    {item.label} <span className="num opacity-60">{statusCounts[item.slug.replace('-', '_')] ?? 0}</span>
                                </Link>
                            ))}
                        </nav>
                    )}

                    <div className="mt-3 grid gap-2 md:grid-cols-12">
                        <label className="relative md:col-span-4">
                            <span className="sr-only">Search films</span>
                            <Icon name="search" size={18} className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-mute" />
                            <input type="search" value={search} onChange={(event) => { setSearch(event.target.value); setLimit(PAGE); }} maxLength={120}
                                placeholder="Title, actor, director or keyword" className="input !pl-11" />
                        </label>
                        {!fixedGenre && (
                            <Dropdown className="md:col-span-2" hideLabel label="Genre" value={genre} onChange={(value) => { setGenre(value); setLimit(PAGE); }}
                                options={[['', 'All genres'], ...genres.map((item) => [item.slug, `${item.name} (${item.count})`] as [string, string])]} />
                        )}
                        <Dropdown className={fixedGenre ? 'md:col-span-3' : 'md:col-span-2'} hideLabel label="Language" value={language} onChange={(value) => { setLanguage(value); setLimit(PAGE); }}
                            options={[['', 'Any language'], ...languages.map((item) => [item, item] as [string, string])]} />
                        <Dropdown className="md:col-span-2" hideLabel label="Certificate" value={certificate} onChange={(value) => { setCertificate(value); setLimit(PAGE); }}
                            options={[['', 'Any rating'], ...certificates.map((item) => [item, item] as [string, string])]} />
                        <Dropdown className={fixedGenre ? 'md:col-span-3' : 'md:col-span-2'} hideLabel label="Sort" value={sort} onChange={setSort} options={sorts} />
                    </div>
                </div>
            </section>

            <section className="pt-8" aria-label="Results">
                <div className="shell">
                    <div className="mb-10 flex flex-wrap items-center justify-between gap-4 text-sm text-mute">
                        <p aria-live="polite">
                            <span className="num text-paper">{results.length}</span> {results.length === 1 ? 'film' : 'films'}
                            {search && <> for “<span className="text-paper">{search}</span>”</>}
                        </p>
                        {filtering && (
                            <button type="button" onClick={reset} className="link inline-flex items-center gap-1 text-paper-2 hover:text-paper"><Icon name="close" size={14} /> Clear filters</button>
                        )}
                    </div>

                    {results.length === 0 ? (
                        <EmptyState icon="film" title="Nothing matches yet" action={<button type="button" onClick={reset} className="btn btn-primary">Show all films</button>}>
                            Try a different spelling, remove a filter, or browse everything that is playing this week.
                        </EmptyState>
                    ) : (
                        <>
                            <motion.div layout className="grid grid-cols-2 gap-x-4 gap-y-14 sm:grid-cols-3 lg:grid-cols-4">
                                <AnimatePresence mode="popLayout">
                                    {results.slice(0, limit).map((movie, index) => (
                                        <motion.div key={movie.id} layout initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, scale: 0.96 }}
                                            transition={{ duration: 0.5, ease: [0.16, 1, 0.3, 1], delay: Math.min(index, 8) * 0.03 }}>
                                            <MovieCard movie={movie} eager={index < 4} />
                                        </motion.div>
                                    ))}
                                </AnimatePresence>
                            </motion.div>
                            {results.length > limit && (
                                <div className="mt-16 flex flex-col items-center gap-3 border-t border-line pt-10">
                                    <p className="label">Showing {Math.min(limit, results.length)} of {results.length}</p>
                                    <button type="button" onClick={() => setLimit(limit + PAGE)} className="btn btn-ghost btn-lg">Show more films <Icon name="arrow-down" size={16} /></button>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </section>

            <section className="pt-24">
                <div className="shell">
                    <p className="label">{fixedGenre ? 'Other genres' : 'Browse by genre'}</p>
                    <div className="mt-5 flex flex-wrap gap-2">
                        {genres.filter((item) => item.slug !== fixedGenre?.slug).map((item) => (
                            <Link key={item.slug} href={route('movies.genre', item.slug)} className="chip">{item.name} <span className="num opacity-60">{item.count}</span></Link>
                        ))}
                    </div>
                </div>
            </section>
        </>
    );
}
