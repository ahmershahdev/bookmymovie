import { usePage } from '@inertiajs/react';
import { route as ziggyRoute } from 'ziggy-js';
import type { SharedProps } from '@/types';

type RouteParams = Parameters<typeof ziggyRoute>[1];

/** Laravel named routes in React, relative so they work on any host. */
export function route(name: string, params?: RouteParams): string {
    return ziggyRoute(name, params, false, (window as unknown as { Ziggy: never }).Ziggy) as unknown as string;
}

export function cn(...classes: Array<string | false | null | undefined>): string {
    return classes.filter(Boolean).join(' ');
}

export function money(value: number | string | null | undefined): string {
    return 'PKR ' + Math.round(Number(value) || 0).toLocaleString('en-PK');
}

export function plural(count: number, word: string, pluralWord = word + 's'): string {
    return `${count} ${count === 1 ? word : pluralWord}`;
}

export function pad(value: number, size = 2): string {
    return String(value).padStart(size, '0');
}

export function useShared(): SharedProps {
    return usePage<SharedProps>().props;
}

export const prefersReducedMotion = (): boolean =>
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
