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
                        <Dropdown className="flex-1 sm:max-w-md" label="Add a film" value="" placeholder={`Choose from ${catalogue.length} films…`}
                            options={catalogue.filter((movie) => !compare.has(movie.id)).map((movie) => ({ value: String(movie.id), label: movie.title, hint: movie.status }))}
                            onChange={(value) => {
                                const movie = catalogue.find((item) => String(item.id) === value);
                                if (movie) compare.toggle(movie);
                            }} />
                        {list.length > 0 && <button type="button" className="btn btn-ghost" onClick={compare.clear}>Clear all</button>}
                    </div>

                    {list.length === 0 ? (
                        <div className="mt-12">
                            <EmptyState icon="compare" title="Nothing to compare yet" action={<Link href={route('movies.index')} className="btn btn-primary">Browse films</Link>}>
                                Pick films from the menu above, or tap the compare icon on any poster while you browse.
                            </EmptyState>
                        </div>
                    ) : (
                        <div className="mt-12 overflow-x-auto" data-lenis-prevent>
                            <table className="w-full min-w-[720px] table-fixed border-collapse text-left">
                                <caption className="sr-only">Film comparison</caption>
                                <thead>
                                    <tr>
                                        <th scope="col" className="w-44 align-bottom"><span className="sr-only">Attribute</span></th>
                                        {list.map((movie) => (
                                            <th key={movie.id} scope="col" className="px-2 pb-6 align-bottom font-normal">
                                                <div className="relative">
                                                    <Link href={route('movies.show', movie.slug)} className="block"><Poster movie={movie} size="sm" /></Link>
                                                    <button type="button" onClick={() => compare.toggle(movie)} className="absolute right-2 top-2 grid h-8 w-8 place-items-center bg-ink/80 text-paper hover:bg-signal hover:text-noir" aria-label={`Remove ${movie.title}`}>
                                                        <Icon name="close" size={14} />
                                                    </button>
                                                </div>
                                                <p className="headline mt-3 text-xl">{movie.title}</p>
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="text-sm">
                                    {rows.map(([label, field, rule]) => {
                                        const winner = rule ? best(rule) : null;
                                        return (
                                            <tr key={label} className="border-t border-line">
                                                <th scope="row" className="label py-4 pr-4 font-semibold">{label}</th>
                                                {list.map((movie) => (
                                                    <td key={movie.id} className={cn('num px-2 py-4', winner && Number(movie[winner.field]) === winner.value && 'font-semibold text-accent')}>
                                                        {format(movie, field)}
                                                    </td>
                                                ))}
                                            </tr>
                                        );
                                    })}
                                    <tr className="border-t border-line">
                                        <th scope="row" className="py-6"><span className="sr-only">Book</span></th>
                                        {list.map((movie) => (
                                            <td key={movie.id} className="px-2 py-6"><Link href={`${route('movies.show', movie.slug)}#showtimes`} className="btn btn-primary btn-sm w-full">Showtimes</Link></td>
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
