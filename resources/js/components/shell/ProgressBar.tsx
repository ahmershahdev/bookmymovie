import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/** Thin volt line across the top while an Inertia visit is in flight. */
export default function ProgressBar() {
    const [progress, setProgress] = useState<number | null>(null);
    const timer = useRef<number>(0);

    useEffect(() => {
        const offStart = router.on('start', (event) => {
            if (event.detail.visit.prefetch) return;
            window.clearTimeout(timer.current);
            // Only show for visits slower than a blink.
            timer.current = window.setTimeout(() => setProgress(0.15), 120);
        });
        const offProgress = router.on('progress', (event) => {
            const percentage = event.detail.progress?.percentage;
            if (percentage) setProgress((current) => (current === null ? null : Math.max(current, percentage / 100)));
        });
        const offFinish = router.on('finish', () => {
            window.clearTimeout(timer.current);
            setProgress((current) => (current === null ? null : 1));
            timer.current = window.setTimeout(() => setProgress(null), 350);
        });

        return () => {
            offStart();
            offProgress();
            offFinish();
            window.clearTimeout(timer.current);
        };
    }, []);

    useEffect(() => {
        if (progress === null || progress >= 0.9) return;
        const trickle = window.setInterval(() => setProgress((current) => (current === null ? null : Math.min(0.9, current + (0.9 - current) * 0.12))), 200);
        return () => window.clearInterval(trickle);
    }, [progress === null]); // eslint-disable-line react-hooks/exhaustive-deps

    return (
        <div aria-hidden="true" className="pointer-events-none fixed inset-x-0 top-0 z-[130] h-[2px]" data-print-hide>
            <div className="h-full origin-left bg-volt shadow-[0_0_12px_#e3ff3b] transition-[transform,opacity] duration-300"
                style={{ transform: `scaleX(${progress ?? 0})`, opacity: progress === null ? 0 : 1 }} />
        </div>
    );
}
