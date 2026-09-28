import { useSyncExternalStore } from 'react';
import type { MovieCard } from '@/types';
import { notify } from './toast';

const KEY = 'bookmymovie.compare.v2';
export const COMPARE_LIMIT = 4;

type Item = Partial<MovieCard> & Pick<MovieCard, 'id' | 'title' | 'slug' | 'palette'>;

const listeners = new Set<() => void>();
let items: Item[] = read();

function read(): Item[] {
    try {
        const parsed = JSON.parse(localStorage.getItem(KEY) || '[]');
        return Array.isArray(parsed) ? parsed.slice(0, COMPARE_LIMIT) : [];
    } catch {
        return [];
    }
}

function write(next: Item[]) {
    items = next;
    try {
        localStorage.setItem(KEY, JSON.stringify(next));
    } catch {
        /* private mode: keep it in memory */
    }
    listeners.forEach((listener) => listener());
}

if (typeof window !== 'undefined') {
    window.addEventListener('storage', (event) => {
        if (event.key === KEY) {
            items = read();
            listeners.forEach((listener) => listener());
        }
    });
}

function subscribe(listener: () => void) {
    listeners.add(listener);
    return () => listeners.delete(listener);
}

/** Films picked for side-by-side comparison, shared across tabs. */
export function useCompare() {
    const list = useSyncExternalStore(subscribe, () => items, () => items);

    return {
        list,
        has: (id: number) => list.some((movie) => Number(movie.id) === Number(id)),
        toggle(movie: Item) {
            if (list.some((item) => Number(item.id) === Number(movie.id))) {
                write(list.filter((item) => Number(item.id) !== Number(movie.id)));
                notify(`${movie.title} removed from compare`);
                return;
            }

            const next = [...list];
            if (next.length >= COMPARE_LIMIT) {
                next.shift();
                notify(`You can compare up to ${COMPARE_LIMIT} films. The oldest was replaced.`);
            } else {
                notify(`${movie.title} added to compare`);
            }
            write([...next, movie]);
        },
        clear: () => write([]),
    };
}
