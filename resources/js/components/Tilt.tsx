import { motion, useMotionTemplate, useMotionValue, useReducedMotion, useSpring, useTransform } from 'motion/react';
import { useRef, useState, type ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Tilts its child toward the pointer in 3D and slides a sheen of light
 * across it, like a foil print catching a lamp. Springs keep it smooth;
 * touch, reduced motion and print get a flat, still card.
 */
export default function Tilt({ children, className, max = 10, shine = true, scale = 1.02, depth = 1200 }: {
    children: ReactNode; className?: string; max?: number; shine?: boolean; scale?: number; depth?: number;
}) {
    const reduce = useReducedMotion();
    const ref = useRef<HTMLDivElement>(null);
    const px = useMotionValue(0.5);
    const py = useMotionValue(0.5);
    const hovering = useMotionValue(0);
    // Sheen layers exist only while hovered: blend-mode layers on every card of a grid are costly.
    const [active, setActive] = useState(false);
    const spring = { stiffness: 220, damping: 22, mass: 0.6 };
    const rotateX = useSpring(useTransform(py, [0, 1], [max, -max]), spring);
    const rotateY = useSpring(useTransform(px, [0, 1], [-max, max]), spring);
    const lift = useSpring(useTransform(hovering, [0, 1], [1, scale]), spring);
    const glow = useSpring(hovering, spring);
    const sheenX = useTransform(px, (value) => `${value * 100}%`);
    const sheenY = useTransform(py, (value) => `${value * 100}%`);
    const sheen = useMotionTemplate`radial-gradient(circle at ${sheenX} ${sheenY}, rgb(255 255 255 / 0.28), rgb(255 255 255 / 0.06) 32%, transparent 60%)`;
    const band = useTransform(px, (value) => `linear-gradient(${105 + value * 30}deg, transparent 30%, rgb(227 255 59 / 0.10) 45%, rgb(183 156 255 / 0.10) 55%, transparent 70%)`);

    if (reduce) return <div className={className}>{children}</div>;

    const move = (event: React.PointerEvent) => {
        if (event.pointerType === 'touch' || !ref.current) return;
        const box = ref.current.getBoundingClientRect();
        px.set((event.clientX - box.left) / box.width);
        py.set((event.clientY - box.top) / box.height);
        hovering.set(1);
        if (!active) setActive(true);
    };
    const leave = () => { px.set(0.5); py.set(0.5); hovering.set(0); setActive(false); };

    return (
        <div ref={ref} className={cn('tilt-stage', className)} style={{ perspective: depth }} onPointerMove={move} onPointerLeave={leave}>
            <motion.div className="relative h-full [transform-style:preserve-3d] print:!transform-none" style={{ rotateX, rotateY, scale: lift }}>
                {children}
                {shine && active && (
                    <>
                        <motion.span aria-hidden="true" className="pointer-events-none absolute inset-0 z-20 mix-blend-soft-light print:hidden" style={{ background: sheen, opacity: glow }} />
                        <motion.span aria-hidden="true" className="pointer-events-none absolute inset-0 z-20 mix-blend-screen print:hidden" style={{ background: band, opacity: glow }} />
                    </>
                )}
            </motion.div>
        </div>
    );
}
