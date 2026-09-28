import { useMemo } from 'react';
import { encode } from 'uqr';

/**
 * A crisp SVG QR code, drawn in the browser so it also works offline. Each
 * dark module becomes one run in a single path, which keeps the DOM small.
 */
export default function QrCode({ value, size = 184, className, label }: { value: string; size?: number; className?: string; label?: string }) {
    const { path, modules } = useMemo(() => {
        const { data, size: count } = encode(value, { ecc: 'Q', border: 2 });
        let d = '';
        data.forEach((row, y) => {
            let x = 0;
            while (x < count) {
                if (!row[x]) { x++; continue; }
                const start = x;
                while (x < count && row[x]) x++;
                d += `M${start} ${y}h${x - start}v1h-${x - start}z`;
            }
        });
        return { path: d, modules: count };
    }, [value]);

    return (
        <svg viewBox={`0 0 ${modules} ${modules}`} width={size} height={size} className={className} role="img" aria-label={label ?? 'QR code'} shapeRendering="crispEdges">
            <rect width={modules} height={modules} fill="#ffffff" />
            <path d={path} fill="#0a0a0a" />
        </svg>
    );
}
