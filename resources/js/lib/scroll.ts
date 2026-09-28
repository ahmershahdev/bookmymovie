import Lenis from 'lenis';
import { router } from '@inertiajs/react';
import { prefersReducedMotion } from './utils';

/**
 * One Lenis instance for the whole app. Inertia swaps pages without a reload,
 * so after every visit Lenis is re-measured and synced to the scroll position
 * Inertia chose (top of page, a #hash, or the preserved position).
 */
let lenis: Lenis | null = null;
const listeners = new Set<(lenis: Lenis) => void>();

export function startSmoothScroll(): void {
    if (lenis || typeof window === 'undefined' || prefersReducedMotion()) return;

    lenis = new Lenis({
        autoRaf: true,
        lerp: 0.11,
        wheelMultiplier: 0.95,
        smoothWheel: true,
        syncTouch: false,
        anchors: { offset: -96 },
        // Modals, menus and scroll rails keep their own native scrolling.
        prevent: (node: HTMLElement) => node.closest('[data-lenis-prevent]') !== null,
    });

    lenis.on('scroll', () => listeners.forEach((listener) => lenis && listener(lenis)));

    router.on('navigate', () => {
        requestAnimationFrame(() => {
            if (!lenis) return;
            lenis.resize();
            lenis.scrollTo(window.scrollY, { immediate: true, force: true });
        });
    });
}

export function getLenis(): Lenis | null {
    return lenis;
}

export function onLenisScroll(listener: (lenis: Lenis) => void): () => void {
    listeners.add(listener);
    return () => listeners.delete(listener);
}

/** Stops page scrolling while an overlay is open. */
export function lockScroll(locked: boolean): void {
    document.documentElement.style.overflow = locked ? 'hidden' : '';
    // The drawn page scrollbar steps aside so the overlay's own is the only one.
    document.documentElement.toggleAttribute('data-scroll-locked', locked);
    if (!lenis) return;
    if (locked) {
        lenis.stop();
    } else {
        lenis.start();
    }
}

export function scrollToTarget(target: string | number | HTMLElement, offset = -96): void {
    if (lenis) {
        lenis.scrollTo(target, { offset, duration: 1.2 });
        return;
    }
    if (typeof target === 'number') {
        window.scrollTo({ top: target, behavior: 'smooth' });
    } else {
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        element?.scrollIntoView({ behavior: 'smooth' });
    }
}
