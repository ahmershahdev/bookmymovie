import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { getLenis, onLenisScroll } from '@/lib/scroll';
import { cn } from '@/lib/utils';

/**
 * The page scrollbar, redrawn: a hairline track on the right edge with a
 * thumb that widens on hover and can be dragged. It follows Lenis's smoothed
 * position so it never lags the content, and hides itself on touch screens
 * and on pages too short to scroll.
 */
export default function ScrollIndicator() {
    const thumb = useRef<HTMLDivElement>(null);
    const track = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(false);
    const [active, setActive] = useState(false);
    const [dragging, setDragging] = useState(false);
    const idle = useRef<number>(0);

    const measure = useCallback(() => {
        const node = thumb.current;
        if (!node) return;
        const viewport = window.innerHeight;
        const height = document.documentElement.scrollHeight;
        const scrollable = height > viewport + 4;
        setVisible(scrollable);
        if (!scrollable) return;

        const size = Math.max(48, (viewport / height) * viewport);
        const progress = window.scrollY / (height - viewport);
        node.style.height = `${size}px`;
        node.style.transform = `translate3d(0, ${Math.min(1, Math.max(0, progress)) * (viewport - size)}px, 0)`;
    }, []);

    const wake = useCallback(() => {
        setActive(true);
        window.clearTimeout(idle.current);
        idle.current = window.setTimeout(() => setActive(false), 1200);
    }, []);

    useEffect(() => {
        const update = () => {
            measure();
            wake();
        };
        const offLenis = onLenisScroll(update);
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', measure);
        const observer = new ResizeObserver(measure);
        observer.observe(document.body);
        const offNavigate = router.on('navigate', () => requestAnimationFrame(measure));
        measure();

        return () => {
            offLenis();
            offNavigate();
            observer.disconnect();
            window.removeEventListener('scroll', update);
            window.removeEventListener('resize', measure);
        };
    }, [measure, wake]);

    const onPointerDown = (event: React.PointerEvent) => {
        const node = thumb.current;
        const bar = track.current;
        if (!node || !bar) return;
        event.preventDefault();

        const viewport = window.innerHeight;
        const height = document.documentElement.scrollHeight;
        const size = node.getBoundingClientRect().height;
        const onThumb = event.target === node;
        const grabOffset = onThumb ? event.clientY - node.getBoundingClientRect().top : size / 2;

        const scrollFor = (clientY: number) => {
            const ratio = (clientY - grabOffset) / (viewport - size);
            return Math.min(1, Math.max(0, ratio)) * (height - viewport);
        };

        const lenis = getLenis();
        const go = (clientY: number, immediate: boolean) => {
            const top = scrollFor(clientY);
            if (lenis) lenis.scrollTo(top, { immediate });
            else window.scrollTo({ top });
        };

        go(event.clientY, onThumb);
        setDragging(true);
        bar.setPointerCapture(event.pointerId);

        const move = (moveEvent: PointerEvent) => go(moveEvent.clientY, true);
        const up = () => {
            setDragging(false);
            bar.removeEventListener('pointermove', move);
            bar.removeEventListener('pointerup', up);
            bar.removeEventListener('pointercancel', up);
        };
        bar.addEventListener('pointermove', move);
        bar.addEventListener('pointerup', up);
        bar.addEventListener('pointercancel', up);
    };

    return (
        <div ref={track} onPointerDown={onPointerDown} onPointerEnter={wake} aria-hidden="true" data-print-hide data-page-scrollbar
            className={cn('group fixed inset-y-0 right-0 z-[95] hidden w-3.5 cursor-default touch-none [@media(pointer:fine)]:block', !visible && '!hidden')}>
            <div ref={thumb}
                className={cn('absolute right-[3px] top-0 w-[3px] origin-right bg-paper/35 transition-[width,background-color,opacity] duration-300 will-change-transform',
                    'group-hover:w-[7px] group-hover:bg-paper/70',
                    dragging && '!w-[7px] !bg-volt',
                    active || dragging ? 'opacity-100' : 'opacity-40')} />
        </div>
    );
}
