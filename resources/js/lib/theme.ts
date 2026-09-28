import { useSyncExternalStore } from 'react';

export type Theme = 'dark' | 'light';

const KEY = 'bmm-theme';
const listeners = new Set<() => void>();

const current = (): Theme => (document.documentElement.dataset.theme === 'light' ? 'light' : 'dark');

/** Switches theme with a short cross-fade and remembers the choice. */
export function setTheme(theme: Theme): void {
    const root = document.documentElement;
    root.classList.add('theme-transition');
    root.dataset.theme = theme;
    try {
        localStorage.setItem(KEY, theme);
    } catch {
        /* private mode */
    }
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', theme === 'light' ? '#f3f1ea' : '#0a0a0a');
    listeners.forEach((listener) => listener());
    window.setTimeout(() => root.classList.remove('theme-transition'), 500);
}

export function useTheme(): [Theme, () => void] {
    const theme = useSyncExternalStore(
        (listener) => {
            listeners.add(listener);
            return () => listeners.delete(listener);
        },
        current,
        () => 'dark' as Theme,
    );

    return [theme, () => setTheme(theme === 'dark' ? 'light' : 'dark')];
}

/** Reads a design token (e.g. "--color-ink") for canvases and WebGL. */
export function token(name: string): string {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || '#0a0a0a';
}
