import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import { useEffect, useState } from 'react';
import Icon from '@/components/Icon';
import { onLenisScroll, scrollToTarget } from '@/lib/scroll';
import { cn } from '@/lib/utils';

const RING = 2 * Math.PI * 21;

/**
 * Back to the top. A ring fills as you read; hovering launches the arrow out
 * of the top and catches a new one from below; clicking glides home.
 */
export default function ScrollTop({ className }: { className?: string }) {
    const reduce = useReducedMotion();
    const [progress, setProgress] = useState(0);
    const [visible, setVisible] = useState(false);
    const [launching, setLaunching] = useState(false);

    useEffect(() => {
        const update = () => {
            const max = document.documentElement.scrollHeight - window.innerHeight;
            setProgress(max > 0 ? Math.min(1, window.scrollY / max) : 0);
            setVisible(window.scrollY > 700);
        };
        update();
        const off = onLenisScroll(update);
        window.addEventListener('scroll', update, { passive: true });
        return () => { off(); window.removeEventListener('scroll', update); };
    }, []);

    const go = () => {
        setLaunching(true);
        scrollToTarget(0, 0);
        window.setTimeout(() => setLaunching(false), 900);
    };

    return (
        <AnimatePresence>
            {visible && (
                <motion.button type="button" onClick={go} aria-label="Back to top" data-print-hide
                    initial={reduce ? { opacity: 0 } : { opacity: 0, scale: 0.6, y: 20 }} animate={{ opacity: 1, scale: 1, y: 0 }} exit={{ opacity: 0, scale: 0.6, y: 20 }}
                    transition={{ type: 'spring', stiffness: 380, damping: 26 }} whileTap={{ scale: 0.9 }}
                    className={cn('group relative grid h-14 w-14 place-items-center bg-ink-2/90 text-paper shadow-2xl shadow-black/40 backdrop-blur-md', className)}>
                    <svg viewBox="0 0 48 48" className="absolute inset-0 h-full w-full -rotate-90" aria-hidden="true">
                        <circle cx="24" cy="24" r="21" fill="none" stroke="currentColor" strokeWidth="1.5" className="text-line-2" />
                        <circle cx="24" cy="24" r="21" fill="none" stroke="currentColor" strokeWidth="2" className="text-volt transition-[stroke-dashoffset] duration-150"
                            strokeDasharray={RING} strokeDashoffset={RING * (1 - progress)} strokeLinecap="square" />
                    </svg>
                    <span className="relative grid h-6 w-6 place-items-center overflow-hidden">
                        <Icon name="arrow-up" size={18} className={cn('transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:-translate-y-[160%]', launching && '-translate-y-[160%]')} />
                        <Icon name="arrow-up" size={18} className={cn('absolute translate-y-[160%] text-accent transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover:translate-y-0', launching && 'translate-y-0')} />
                    </span>
                    <span className="num pointer-events-none absolute -top-7 right-0 bg-volt px-1.5 py-0.5 text-[10px] text-noir opacity-0 transition-opacity group-hover:opacity-100">{Math.round(progress * 100)}%</span>
                </motion.button>
            )}
        </AnimatePresence>
    );
}
