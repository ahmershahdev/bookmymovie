import { useReducedMotion } from 'motion/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/** A classic 5×7 dot-matrix alphabet: each row is five bits, left to right. */
const GLYPHS: Record<string, number[]> = {
    A: [14, 17, 17, 31, 17, 17, 17], B: [30, 17, 17, 30, 17, 17, 30], C: [14, 17, 16, 16, 16, 17, 14], D: [30, 17, 17, 17, 17, 17, 30],
    E: [31, 16, 16, 30, 16, 16, 31], F: [31, 16, 16, 30, 16, 16, 16], G: [14, 17, 16, 23, 17, 17, 15], H: [17, 17, 17, 31, 17, 17, 17],
    I: [14, 4, 4, 4, 4, 4, 14], J: [7, 2, 2, 2, 2, 18, 12], K: [17, 18, 20, 24, 20, 18, 17], L: [16, 16, 16, 16, 16, 16, 31],
    M: [17, 27, 21, 21, 17, 17, 17], N: [17, 17, 25, 21, 19, 17, 17], O: [14, 17, 17, 17, 17, 17, 14], P: [30, 17, 17, 30, 16, 16, 16],
    Q: [14, 17, 17, 17, 21, 18, 13], R: [30, 17, 17, 30, 20, 18, 17], S: [15, 16, 16, 14, 1, 1, 30], T: [31, 4, 4, 4, 4, 4, 4],
    U: [17, 17, 17, 17, 17, 17, 14], V: [17, 17, 17, 17, 17, 10, 4], W: [17, 17, 17, 21, 21, 21, 10], X: [17, 17, 10, 4, 10, 17, 17],
    Y: [17, 17, 17, 10, 4, 4, 4], Z: [31, 1, 2, 4, 8, 16, 31],
    0: [14, 17, 19, 21, 25, 17, 14], 1: [4, 12, 4, 4, 4, 4, 14], 2: [14, 17, 1, 2, 4, 8, 31], 3: [31, 2, 4, 2, 1, 17, 14],
    4: [2, 6, 10, 18, 31, 2, 2], 5: [31, 16, 30, 1, 1, 17, 14], 6: [6, 8, 16, 30, 17, 17, 14], 7: [31, 1, 2, 4, 8, 8, 8],
    8: [14, 17, 17, 14, 17, 17, 14], 9: [14, 17, 17, 15, 1, 2, 12],
    '-': [0, 0, 0, 31, 0, 0, 0], "'": [4, 4, 8, 0, 0, 0, 0], ':': [0, 12, 12, 0, 12, 12, 0], '.': [0, 0, 0, 0, 0, 12, 12],
    '!': [4, 4, 4, 4, 4, 0, 4], '?': [14, 17, 1, 2, 4, 0, 4], '&': [12, 18, 20, 8, 21, 18, 13], ',': [0, 0, 0, 0, 12, 4, 8], ' ': [0, 0, 0, 0, 0, 0, 0],
};

/** Breaks a title into sign lines of at most `width` characters, on word boundaries. */
function lines(text: string, width: number): string[] {
    const words = text.toUpperCase().replace(/[’‘]/g, "'").split(/\s+/).filter(Boolean);
    const result: string[] = [];
    let current = '';
    for (const word of words) {
        const next = current ? `${current} ${word}` : word;
        if (next.length > width && current) { result.push(current); current = word; } else current = next;
    }
    if (current) result.push(current);
    return result.slice(0, 3);
}

/**
 * A cinema marquee: the title spelled in light bulbs that switch on one
 * column at a time when the sign scrolls into view, a chase of bulbs around
 * the frame, and the odd bulb that flickers. Pure SVG and CSS.
 */
export default function MarqueeSign({ title, eyebrow = 'Now playing', className }: { title: string; eyebrow?: string; className?: string }) {
    const ref = useRef<HTMLDivElement>(null);
    const [inView, setInView] = useState(false);
    useEffect(() => {
        const node = ref.current;
        if (!node) return;
        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) { setInView(true); observer.disconnect(); }
        }, { threshold: 0.35 });
        observer.observe(node);
        return () => observer.disconnect();
    }, []);
    const reduce = useReducedMotion();
    const rows = useMemo(() => lines(title, 16), [title]);
    const columns = Math.max(...rows.map((row) => row.length)) * 6 - 1;
    const pitch = 10;
    const width = columns * pitch + 40;
    const height = rows.length * 8 * pitch + 40;

    const bulbs = useMemo(() => {
        const list: { x: number; y: number; on: boolean; column: number; flicker: boolean }[] = [];
        rows.forEach((row, rowIndex) => {
            const offset = Math.floor((columns - (row.length * 6 - 1)) / 2);
            [...row].forEach((char, charIndex) => {
                const glyph = GLYPHS[char] ?? GLYPHS[' '];
                glyph.forEach((bits, y) => {
                    for (let x = 0; x < 5; x++) {
                        const column = offset + charIndex * 6 + x;
                        const on = Boolean(bits & (1 << (4 - x)));
                        list.push({ x: 20 + column * pitch + pitch / 2, y: 20 + (rowIndex * 8 + y) * pitch + pitch / 2, on, column, flicker: on && (column * 7 + y * 13) % 29 === 0 });
                    }
                });
            });
        });
        return list;
    }, [rows, columns]);

    const frame = useMemo(() => {
        const list: { x: number; y: number; index: number }[] = [];
        const step = 14;
        let index = 0;
        for (let x = 7; x <= width - 7; x += step) { list.push({ x, y: 7, index: index++ }); }
        for (let y = 7 + step; y <= height - 7; y += step) { list.push({ x: width - 7, y, index: index++ }); }
        for (let x = width - 7 - step; x >= 7; x -= step) { list.push({ x, y: height - 7, index: index++ }); }
        for (let y = height - 7 - step; y > 7; y -= step) { list.push({ x: 7, y, index: index++ }); }
        return list;
    }, [width, height]);

    const lit = inView || reduce;

    return (
        <div ref={ref} className={cn('marquee-sign relative', className)} role="img" aria-label={`${eyebrow}: ${title}`}>
            <p className="label label-accent mb-3 text-center" aria-hidden="true">{eyebrow}</p>
            <svg viewBox={`0 0 ${width} ${height}`} className="mx-auto block w-full max-w-5xl" aria-hidden="true">
                <defs>
                    <radialGradient id="bulb-on"><stop offset="0" stopColor="#fffbe8" /><stop offset=".45" stopColor="#ffe9a3" /><stop offset="1" stopColor="#ffb938" /></radialGradient>
                    {/* A gradient halo instead of a blur filter: hundreds of filtered, animated circles stall the renderer. */}
                    <radialGradient id="bulb-halo"><stop offset="0" stopColor="#ffcf5c" stopOpacity=".7" /><stop offset=".5" stopColor="#ffb938" stopOpacity=".25" /><stop offset="1" stopColor="#ffb938" stopOpacity="0" /></radialGradient>
                </defs>
                <rect x="1" y="1" width={width - 2} height={height - 2} fill="#0b0a09" stroke="#2a2723" strokeWidth="2" />
                <g className={cn('marquee-chase', !reduce && lit && 'is-running')}>
                    {frame.map((bulb) => <circle key={bulb.index} cx={bulb.x} cy={bulb.y} r={2.6} className={cn('chase-bulb', bulb.index % 3 === 0 && 'phase-a', bulb.index % 3 === 1 && 'phase-b', bulb.index % 3 === 2 && 'phase-c')} />)}
                </g>
                {bulbs.map((bulb, index) => bulb.on ? (
                    <g key={index} className={cn('sign-bulb', lit && 'is-lit', bulb.flicker && !reduce && 'flicker')} style={{ animationDelay: reduce ? '0ms' : `${bulb.column * 28}ms` }}>
                        <circle cx={bulb.x} cy={bulb.y} r={7} fill="url(#bulb-halo)" className="glow" />
                        <circle cx={bulb.x} cy={bulb.y} r={3.1} fill="url(#bulb-on)" />
                    </g>
                ) : (
                    <circle key={index} cx={bulb.x} cy={bulb.y} r={1.9} fill="#1c1a17" />
                ))}
            </svg>
        </div>
    );
}
