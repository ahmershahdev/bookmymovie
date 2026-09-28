import { cn } from '@/lib/utils';
import type { Palette } from '@/types';

type PosterMovie = {
    title: string;
    slug?: string;
    palette?: Palette;
    poster_url?: string | null;
    genre?: string;
    certificate?: string;
    release_year?: string | null;
};

function hash(text: string): number {
    let value = 0;
    for (let index = 0; index < text.length; index++) value = (value * 31 + text.charCodeAt(index)) | 0;
    return Math.abs(value);
}

/**
 * The real poster when one is uploaded, otherwise a typographic one-sheet
 * generated from the film's palette: condensed billing type over one of four
 * graphic compositions, so the catalogue never shows an empty box.
 */
/**
 * Bundled artwork ships a 640px copy next to each full-size file
 * (card.webp + card-sm.webp, hero.webp + hero-sm.webp), so grids and phones
 * download about a quarter of the bytes.
 */
export function responsiveSrcSet(url: string | null | undefined): string | undefined {
    const match = url?.match(/^(.*\/(card|hero))\.webp(\?.*)?$/);
    if (!match) return undefined;
    const [, base, kind] = match;
    return kind === 'card' ? `${base}-sm.webp 640w, ${base}.webp 1200w` : `${base}-sm.webp 960w, ${base}.webp 1600w`;
}

export default function Poster({ movie, eager = false, size = 'md', meta = true, className, sizes }: {
    movie: PosterMovie;
    eager?: boolean;
    size?: 'sm' | 'md' | 'lg';
    meta?: boolean;
    className?: string;
    sizes?: string;
}) {
    const [ground, accent, paper] = movie.palette ?? ['#1b1b1b', '#e3ff3b', '#f2f0ea'];
    const variant = hash(movie.slug ?? movie.title) % 4;
    const length = movie.title.length;
    const titleSize = {
        lg: length > 26 ? 'text-[2.6rem]' : length > 16 ? 'text-[3.4rem]' : 'text-[4.4rem]',
        md: length > 26 ? 'text-[1.7rem]' : length > 16 ? 'text-[2.2rem]' : 'text-[2.9rem]',
        sm: length > 26 ? 'text-lg' : length > 16 ? 'text-xl' : 'text-[1.7rem]',
    }[size];
    const initial = movie.title.replace(/^(The|A|An)\s+/i, '').charAt(0);

    if (movie.poster_url) {
        return (
            <div className={cn('poster', className)}>
                <img src={movie.poster_url} srcSet={responsiveSrcSet(movie.poster_url)}
                    sizes={sizes ?? (size === 'lg' ? '(min-width: 1024px) 40vw, 90vw' : size === 'sm' ? '(min-width: 768px) 12rem, 40vw' : '(min-width: 1280px) 22vw, (min-width: 768px) 32vw, 48vw')}
                    alt={`Poster for ${movie.title}`} width={1200} height={900}
                    loading={eager ? 'eager' : 'lazy'} decoding="async" {...(eager ? { fetchPriority: 'high' as const } : {})}
                    className="absolute inset-0 h-full w-full object-cover" />
            </div>
        );
    }

    return (
        <div className={cn('poster', className)} role="img" aria-label={`Poster for ${movie.title}`}
            style={{ background: `linear-gradient(170deg, color-mix(in oklab, ${accent} 16%, ${ground}) 0%, ${ground} 50%, color-mix(in oklab, ${ground} 55%, black) 100%)` }}>
            {variant === 0 && (
                <>
                    <div className="absolute -right-[22%] top-[6%] aspect-square w-[82%] rounded-full" style={{ background: accent }} />
                    <div className="absolute inset-x-0 top-[34%] space-y-[5%]">
                        {[1, 2, 3, 4, 5, 6].map((band) => <div key={band} className="h-[3px]" style={{ background: ground, opacity: 1 - band * 0.12 }} />)}
                    </div>
                </>
            )}
            {variant === 1 && (
                <>
                    <div className="absolute inset-y-0 left-[14%] w-[26%]" style={{ background: `linear-gradient(180deg, ${accent}, transparent 85%)`, opacity: 0.7 }} />
                    <div className="absolute inset-y-0 left-[48%] w-[5%]" style={{ background: `linear-gradient(180deg, ${paper}, transparent 70%)`, opacity: 0.4 }} />
                </>
            )}
            {variant === 2 && (
                <div className="display absolute -right-[8%] -top-[12%] select-none leading-none" style={{ color: accent, opacity: 0.55, fontSize: size === 'sm' ? '11rem' : '19rem' }}>{initial}</div>
            )}
            {variant === 3 && [1, 2, 3, 4, 5].map((ring) => (
                <div key={ring} className="absolute left-1/2 top-[30%] aspect-square -translate-x-1/2 -translate-y-1/2 rounded-full border"
                    style={{ width: `${20 + ring * 22}%`, borderColor: accent, opacity: 0.9 - ring * 0.14 }} />
            ))}

            <div className="absolute inset-0 flex flex-col justify-between p-[8%]">
                <div className={cn('flex items-start justify-between gap-2 text-[8px] font-bold uppercase tracking-[.18em] [font-stretch:125%]', !meta && 'invisible')} style={{ color: paper, opacity: 0.8 }}>
                    <span>{movie.release_year ?? ''}</span>
                    <span>{movie.certificate ?? ''}</span>
                </div>
                <div>
                    {movie.genre && <p className="text-[8px] font-bold uppercase tracking-[.2em] [font-stretch:125%]" style={{ color: accent }}>{movie.genre}</p>}
                    <p className={cn('display mt-2 leading-[.86]', titleSize)} style={{ color: paper }}>{movie.title}</p>
                    <div className="mt-3 h-[3px] w-8" style={{ background: accent }} />
                </div>
            </div>
        </div>
    );
}
