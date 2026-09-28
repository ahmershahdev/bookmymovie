import { router } from '@inertiajs/react';

/**
 * Registers the service worker that keeps e-tickets available offline
 * (public/sw.js), hands it the assets of the page that was open before it
 * took control, and wipes saved tickets once nobody is signed in.
 */
export function registerServiceWorker(): void {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) return;

    const post = (message: unknown) => {
        if (navigator.serviceWorker.controller) navigator.serviceWorker.controller.postMessage(message);
        else navigator.serviceWorker.ready.then((registration) => registration.active?.postMessage(message)).catch(() => undefined);
    };

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(() => {
            const assets = performance.getEntriesByType('resource').map((entry) => entry.name).filter((name) => name.includes('/build/'));
            post({ type: 'save', urls: [window.location.href, ...assets] });
        }).catch(() => undefined);
    });

    router.on('navigate', (event) => {
        const signedIn = Boolean((event.detail.page.props as { auth?: { user?: unknown } }).auth?.user);
        if (!signedIn) post({ type: 'clear-tickets' });
    });
}
