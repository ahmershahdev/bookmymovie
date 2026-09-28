import { cn } from '@/lib/utils';

const GROUNDS = [
    ['#1f3b2d', '#57e39b'], ['#2a1f3d', '#b18cff'], ['#3d2a1f', '#ffb86b'], ['#1f2f3d', '#6bc7ff'],
    ['#3d1f2a', '#ff7aa2'], ['#2d3d1f', '#e3ff3b'], ['#1f3d3b', '#5ce1d6'], ['#3d351f', '#ffd84d'],
];

function hash(text: string): number {
    let value = 0;
    for (let index = 0; index < text.length; index++) value = (value * 33 + text.charCodeAt(index)) | 0;
    return Math.abs(value);
}

/**
 * A member's photo, or a monogram in one of eight colourways picked from
 * their username, so the same person always looks the same across the site.
 */
export default function Avatar({ name, seed, src, size = 40, className }: { name: string; seed?: string | null; src?: string | null; size?: number; className?: string }) {
    const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('') || '?';

    if (src) {
        return <img src={src} alt="" width={size} height={size} loading="lazy" decoding="async" className={cn('shrink-0 object-cover', className)} style={{ width: size, height: size }} />;
    }

    const [ground, ink] = GROUNDS[hash(seed || name) % GROUNDS.length];

    return (
        <span aria-hidden="true" className={cn('grid shrink-0 select-none place-items-center font-extrabold uppercase [font-stretch:75%]', className)}
            style={{ width: size, height: size, background: `linear-gradient(140deg, ${ground}, color-mix(in oklab, ${ground} 55%, black))`, color: ink, fontSize: size * 0.42 }}>
            {initials}
        </span>
    );
}
