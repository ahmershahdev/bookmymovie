import { animate, motion, useInView, useReducedMotion } from 'motion/react';
import { createElement, useEffect, useRef, type ElementType, type ReactNode } from 'react';
import { cn } from '@/lib/utils';

const ease = [0.16, 1, 0.3, 1] as const;

/** Fades and lifts its children the first time they scroll into view. */
export function Reveal({ children, delay = 0, y = 32, className, as = 'div' }: { children: ReactNode; delay?: number; y?: number; className?: string; as?: 'div' | 'li' | 'section' | 'article' }) {
    const reduce = useReducedMotion();
    const Component = motion[as];

    return (
        <Component
            className={className}
            initial={reduce ? false : { opacity: 0, y }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: '0px 0px -8% 0px' }}
            transition={{ duration: 1, ease, delay: delay / 1000 }}
        >
            {children}
        </Component>
    );
}

/**
 * Big condensed headlines rise word by word out of a mask, like credits
 * being set on a title card. Screen readers get the plain sentence.
 */
export function SplitHeading({ text, as = 'h2', className, delay = 0, accent, id }: { text: string; as?: ElementType; className?: string; delay?: number; accent?: string[]; id?: string }) {
    const ref = useRef<HTMLElement>(null);
    const inView = useInView(ref, { once: true, margin: '0px 0px -10% 0px' });
    const reduce = useReducedMotion();
    const words = text.split(' ');
    const accents = new Set((accent ?? []).map((word) => word.toLowerCase()));

    return createElement(
        as,
        { ref, id, className: cn('display', className), 'aria-label': text },
        words.map((word, index) => (
            <span key={`${word}-${index}`} className="inline-block overflow-hidden pb-[0.06em] align-bottom" aria-hidden="true">
                <motion.span
                    className={cn('inline-block', accents.has(word.toLowerCase().replace(/[^\p{L}\p{N}]/gu, '')) && 'text-accent')}
                    initial={reduce ? false : { y: '110%' }}
                    animate={inView || reduce ? { y: '0%' } : undefined}
                    transition={{ duration: 1.1, ease, delay: delay / 1000 + index * 0.06 }}
                >
                    {word}
                </motion.span>
                {index < words.length - 1 && ' '}
            </span>
        )),
    );
}

/** Counts a number up when it scrolls into view. */
export function CountUp({ value, className }: { value: number; className?: string }) {
    const ref = useRef<HTMLSpanElement>(null);
    const inView = useInView(ref, { once: true });
    const reduce = useReducedMotion();

    useEffect(() => {
        if (!inView || reduce || !ref.current) return;
        const node = ref.current;
        const controls = animate(0, value, {
            duration: 1.8,
            ease,
            onUpdate: (latest) => {
                node.textContent = Math.round(latest).toLocaleString('en-PK');
            },
        });
        return () => controls.stop();
    }, [inView, reduce, value]);

    return (
        <span ref={ref} className={className}>
            {value.toLocaleString('en-PK')}
        </span>
    );
}
