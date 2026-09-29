import { Link } from '@inertiajs/react';
import Icon from '@/components/Icon';
import Poster from '@/components/Poster';
import Tilt from '@/components/Tilt';
import { useCompare } from '@/lib/compare';
import { useT } from '@/lib/i18n';
import { cn, money, pad, route } from '@/lib/utils';
import type { MovieCard as Movie } from '@/types';

export default function MovieCard({ movie, eager = false, index, className }: { movie: Movie; eager?: boolean; index?: number; className?: string }) {
    const compare = useCompare();
    const t = useT();
    const inCompare = compare.has(movie.id);
    const onSale = Number(movie.original_price) > Number(movie.price);
    const href = route('movies.show', movie.slug);
    const statusTone = movie.status_key === 'coming_soon' ? 'tag-volt' : movie.status_key === 'ended' ? '' : 'tag-mint';

    return (
        <article className={cn('group relative flex min-w-0 flex-col', className)}>
            <Tilt max={8} scale={1.015}>
            <Link href={href} prefetch className="relative block overflow-hidden">
                {/* The name comes from this line; the poster, hover strip and tag repeat it visually. */}
                <span className="sr-only">{movie.title}, {t(movie.status)}, {movie.genre}, {movie.duration}</span>
                <div className="transition-transform duration-[900ms] ease-[var(--ease-out-expo)] group-hover:scale-[1.04]" aria-hidden="true">
                    <Poster movie={movie} eager={eager} meta={false} />
                </div>

                {/* Hover state: a volt ticket strip rises from the bottom edge. */}
                <div aria-hidden="true" className="absolute inset-x-0 bottom-0 flex translate-y-full items-center justify-between bg-volt px-4 py-3 text-noir transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:translate-y-0 group-focus-visible:translate-y-0">
                    <span className="text-[11px] font-bold uppercase tracking-[.08em] [font-stretch:115%]">{movie.first_show_id ? t('Book seats') : t('Details')}</span>
                    <Icon name="arrow-up-right" size={18} />
                </div>

                <div className="pointer-events-none absolute inset-x-3 top-3 flex items-start justify-between" aria-hidden="true">
                    <span className={cn('tag', statusTone)}>{t(movie.status)}</span>
                    {index !== undefined && !movie.poster_url && (
                        <span className="display display-outline text-6xl text-paper/90">{pad(index)}</span>
                    )}
                </div>
            </Link>
            </Tilt>

            <button type="button" onClick={() => compare.toggle(movie)} aria-pressed={inCompare} aria-label={`${inCompare ? 'Remove' : 'Add'} ${movie.title} ${inCompare ? 'from' : 'to'} compare`}
                className={cn('absolute right-3 top-12 z-10 grid h-9 w-9 place-items-center border bg-ink/80 backdrop-blur transition',
                    inCompare ? 'border-accent text-accent opacity-100' : 'border-line-2 text-paper opacity-0 group-hover:opacity-100 focus-visible:opacity-100 hover:border-accent hover:text-accent')}>
                <Icon name={inCompare ? 'check' : 'compare'} size={16} />
            </button>

            <div className="mt-4 flex flex-1 flex-col">
                <p className="label truncate">{movie.genre}</p>
                <h3 className="headline mt-2 text-[1.35rem] text-balance [overflow-wrap:anywhere] sm:text-[1.65rem]" dir="auto">
                    <Link href={href} className="link">{movie.title}</Link>
                </h3>
                <p className="num mt-2 text-[11px] uppercase text-mute">{movie.duration} · {movie.certificate} · {movie.language}</p>

                <div className="mt-auto flex items-end justify-between gap-3 border-t border-line pt-3">
                    <div className="flex items-center gap-1.5 text-sm">
                        {movie.reviews > 0 ? (
                            <>
                                <Icon name="star" size={14} className="fill-accent text-accent" />
                                <span className="num font-semibold">{Number(movie.rating).toFixed(1)}</span>
                                <span className="text-mute">({movie.reviews})</span>
                            </>
                        ) : (
                            <span className="text-mute">{movie.status_key === 'coming_soon' ? t('Opens :date', { date: movie.release_date ?? '' }) : t('New')}</span>
                        )}
                    </div>
                    {movie.first_show_id && Number(movie.price) > 0 ? (
                        <p className="text-right leading-tight">
                            <span className="label block text-[9px]">{t('from')}</span>
                            <span className={cn('num text-sm font-semibold', onSale ? 'text-signal' : 'text-paper')}>{money(movie.price)}</span>
                        </p>
                    ) : null}
                </div>
            </div>
        </article>
    );
}
