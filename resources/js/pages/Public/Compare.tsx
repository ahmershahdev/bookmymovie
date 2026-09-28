import { Link } from '@inertiajs/react';
import Dropdown from '@/components/Dropdown';
import Icon from '@/components/Icon';
import Poster from '@/components/Poster';
import { EmptyState, PageHeader } from '@/components/ui';
import { useCompare } from '@/lib/compare';
import { cn, money, route } from '@/lib/utils';
import type { MovieCard } from '@/types';

type Field = keyof MovieCard;

const rows: [string, Field, string | null][] = [
    ['Genre', 'genre', null],
    ['Runtime', 'duration', 'min:duration_minutes'],
    ['Certificate', 'certificate', null],
    ['Language', 'language', null],
    ['Audience rating', 'rating', 'max:rating'],
    ['Reviews', 'reviews', 'max:reviews'],
    ['Tickets from', 'price', 'min:price'],
    ['Released', 'release_year', null],
    ['Status', 'status', null],
];

export default function Compare({ catalogue }: { catalogue: MovieCard[] }) {
    const compare = useCompare();
    const list = compare.list as MovieCard[];
    const slots: (MovieCard | null)[] = [...list, ...Array.from({ length: Math.max(0, 4 - list.length) }, () => null)].slice(0, 4);

    const best = (rule: string) => {
        const [mode, field] = rule.split(':') as ['min' | 'max', Field];
        const values = list.map((movie) => Number(movie[field]) || 0).filter((value) => value > 0);
        if (values.length < 2) return null;
        return { field, value: mode === 'min' ? Math.min(...values) : Math.max(...values) };
    };

    const format = (movie: MovieCard, field: Field) => {
        const value = movie[field];
        if (field === 'price') return Number(value) > 0 ? money(value as number) : '—';
        if (field === 'rating') return movie.reviews > 0 ? `${Number(value).toFixed(1)} / 5` : 'No reviews';
        return (value as string | number | null) ?? '—';
    };

    return (
        <>
            <PageHeader label="Compare" title="Side by side" accent={['side']}
                lede="Add up to four films. The best rating, lowest price and shortest runtime are highlighted, so choosing tonight’s film takes seconds." />

            <section className="pt-14">
                <div className="shell">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div data-compare-add className="flex-1 sm:max-w-md"><Dropdown label="Add a film" value="" placeholder={`Choose from ${catalogue.length} films…`}
                            options={catalogue.filter((movie) => !compare.has(movie.id)).map((movie) => ({ value: String(movie.id), label: movie.title, hint: movie.status }))}
                            onChange={(value) => {
                                const movie = catalogue.find((item) => String(item.id) === value);
                                if (movie) compare.toggle(movie);
                            }} /></div>
                        {list.length > 0 && <button type="button" className="btn btn-ghost" onClick={compare.clear}>Clear all</button>}
                    </div>

                    {list.length === 0 ? (
                        <div className="mt-12">
                            <EmptyState icon="compare" title="Nothing to compare yet" action={<Link href={route('movies.index')} className="btn btn-primary">Browse films</Link>}>
                                Pick films from the menu above, or tap the compare icon on any poster while you browse.
                            </EmptyState>
                        </div>
                    ) : (
                        <div className="mt-12 overflow-x-auto border border-line" data-lenis-prevent>
                            {/* Always four equal slots: one film never stretches to fill the page, and empty slots invite the next pick. */}
                            <table className="w-full min-w-[46rem] table-fixed border-collapse text-left">
                                <caption className="sr-only">Film comparison</caption>
                                <colgroup>
                                    <col className="w-32 sm:w-44" />
                                    {slots.map((_, index) => <col key={index} />)}
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col" className="sticky left-0 z-10 bg-ink align-bottom"><span className="sr-only">Attribute</span></th>
                                        {slots.map((movie, index) => (
                                            <th key={movie?.id ?? `empty-${index}`} scope="col" className="border-s border-line p-3 align-top font-normal">
                                                {movie ? (
                                                    <div className="group relative">
                                                        <Link href={route('movies.show', movie.slug)} className="block overflow-hidden">
                                                            <div className="transition-transform duration-700 ease-[var(--ease-out-expo)] group-hover:scale-105"><Poster movie={movie} size="sm" meta={false} /></div>
                                                        </Link>
                                                        <button type="button" onClick={() => compare.toggle(movie)} className="absolute right-2 top-2 grid h-8 w-8 place-items-center bg-ink/80 text-paper backdrop-blur transition hover:bg-signal hover:text-noir" aria-label={`Remove ${movie.title}`}>
                                                            <Icon name="close" size={14} />
                                                        </button>
                                                        <p className="headline mt-3 line-clamp-2 text-lg leading-tight">{movie.title}</p>
                                                    </div>
                                                ) : (
                                                    <button type="button" onClick={() => document.querySelector<HTMLElement>('[data-compare-add] button')?.click()}
                                                        className="grid aspect-[4/3] w-full place-items-center border border-dashed border-line-2 text-mute transition hover:border-accent hover:text-accent">
                                                        <span className="flex flex-col items-center gap-2 text-xs"><Icon name="plus" size={20} /> Add a film</span>
                                                    </button>
                                                )}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="text-sm">
                                    {rows.map(([label, field, rule]) => {
                                        const winner = rule ? best(rule) : null;
                                        return (
                                            <tr key={label} className="border-t border-line">
                                                <th scope="row" className="label sticky left-0 z-10 bg-ink px-3 py-4 font-semibold">{label}</th>
                                                {slots.map((movie, index) => {
                                                    const wins = movie && winner && Number(movie[winner.field]) === winner.value;
                                                    return (
                                                        <td key={movie?.id ?? `empty-${index}`} className={cn('num border-s border-line px-3 py-4', wins && 'bg-volt/[.07] font-semibold text-accent')}>
                                                            {movie ? <>{format(movie, field)}{wins && <span className="ms-2 text-[10px] uppercase">Best</span>}</> : <span className="text-dim">—</span>}
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        );
                                    })}
                                    <tr className="border-t border-line">
                                        <th scope="row" className="sticky left-0 z-10 bg-ink py-6"><span className="sr-only">Book</span></th>
                                        {slots.map((movie, index) => (
                                            <td key={movie?.id ?? `empty-${index}`} className="border-s border-line px-3 py-6">
                                                {movie && <Link href={`${route('movies.show', movie.slug)}#showtimes`} className="btn btn-primary btn-sm w-full">Showtimes</Link>}
                                            </td>
                                        ))}
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </section>
        </>
    );
}
