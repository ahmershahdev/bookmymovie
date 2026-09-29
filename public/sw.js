/*
 * BookMyMovie service worker: keeps e-tickets readable with no signal.
 *
 * - Hashed build assets (/build/*) and icons: cache first. Filenames change
 *   on every release, so a cached copy is never stale.
 * - Booking pages (/account/bookings…): network first, saved on every
 *   successful load, served from the cache when offline. Inertia page visits
 *   (X-Inertia) and full page loads are stored separately.
 * - Film art, trailer stills and share images (/images/movies, /images/og,
 *   /videos/trailers/*.webp, /storage/movie-media): stale-while-revalidate,
 *   capped at MEDIA_LIMIT entries. Trailer video itself is left to the HTTP
 *   cache, because players fetch it in byte ranges.
 * - Any other page offline: /offline.html, which lists the saved tickets.
 * Saved tickets are wiped when the customer signs out (see lib/offline.ts).
 */
const VERSION = 'v3';
const SHELL = `bmm-shell-${VERSION}`;
const ASSETS = 'bmm-assets';
const TICKETS = 'bmm-tickets';
const MEDIA = 'bmm-media';
const MEDIA_LIMIT = 120;
const PRECACHE = ['/offline.html', '/images/site.webmanifest', '/images/favicon/android-chrome-192x192.png', '/images/logo.webp'];

self.addEventListener('install', (event) => {
    // One file at a time: a single missing file must not abort the install
    // (cache.addAll rejects the whole batch on any failure).
    event.waitUntil((async () => {
        const cache = await caches.open(SHELL);
        await Promise.all(PRECACHE.map((url) => cache.add(url).catch(() => undefined)));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keep = [SHELL, ASSETS, TICKETS, MEDIA];
        for (const key of await caches.keys()) {
            if (!keep.includes(key)) await caches.delete(key);
        }
        await self.clients.claim();
    })());
});

const isTicketPath = (path) => /^\/account\/bookings(\/[A-Za-z0-9-]+)?\/?$/.test(path);

/** Inertia JSON and the full HTML page live under different cache keys. */
function ticketKey(request) {
    const url = new URL(request.url);
    url.search = '';
    if (request.headers.get('X-Inertia')) url.searchParams.set('__inertia', '1');
    return url.toString();
}

async function networkFirstTicket(request) {
    const cache = await caches.open(TICKETS);
    try {
        const response = await fetch(request);
        if (response.ok && !response.redirected) await cache.put(ticketKey(request), response.clone());
        return response;
    } catch (error) {
        const cached = await cache.match(ticketKey(request));
        if (cached) return cached;
        return offlineFallback(request);
    }
}

async function cacheFirst(request) {
    const cache = await caches.open(ASSETS);
    const cached = await cache.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response.ok) await cache.put(request, response.clone());
    return response;
}

const isMedia = (path) => /^\/(images\/(movies|og)\/|storage\/movie-media\/)/.test(path) || /^\/videos\/trailers\/[^/]+\.webp$/.test(path);

/** Serve the saved copy at once and refresh it in the background. */
async function staleWhileRevalidate(event) {
    const cache = await caches.open(MEDIA);
    const cached = await cache.match(event.request);
    const refresh = fetch(event.request).then(async (response) => {
        if (response.ok && response.type === 'basic') {
            await cache.put(event.request, response.clone());
            const keys = await cache.keys();
            for (const old of keys.slice(0, Math.max(0, keys.length - MEDIA_LIMIT))) await cache.delete(old);
        }
        return response;
    });
    if (cached) {
        event.waitUntil(refresh.catch(() => undefined));
        return cached;
    }
    return refresh;
}

async function offlineFallback(request) {
    // An Inertia visit cannot render HTML; ask it to do a full page load,
    // which then lands on the offline page.
    if (request.headers.get('X-Inertia')) {
        return new Response('', { status: 409, headers: { 'X-Inertia-Location': request.url } });
    }
    return (await caches.match('/offline.html')) || new Response('You are offline.', { status: 503, headers: { 'Content-Type': 'text/plain' } });
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/favicon/') || url.pathname.startsWith('/images/logo')) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (isMedia(url.pathname)) {
        event.respondWith(staleWhileRevalidate(event));
        return;
    }

    if (isTicketPath(url.pathname)) {
        event.respondWith(networkFirstTicket(request));
        return;
    }

    if (request.mode === 'navigate' || request.headers.get('X-Inertia')) {
        event.respondWith(fetch(request).catch(() => offlineFallback(request)));
    }
});

self.addEventListener('message', (event) => {
    const data = event.data || {};

    if (data.type === 'clear-tickets') {
        event.waitUntil(caches.delete(TICKETS));
    }

    // The page that registered the worker loaded before the worker was in
    // control; it sends its own URL and assets so they are saved too.
    if (data.type === 'save' && Array.isArray(data.urls)) {
        event.waitUntil((async () => {
            for (const href of data.urls) {
                try {
                    const url = new URL(href, self.location.origin);
                    if (url.origin !== self.location.origin) continue;
                    const request = new Request(url.toString(), { credentials: 'same-origin' });
                    if (isTicketPath(url.pathname)) await networkFirstTicket(request);
                    else if (url.pathname.startsWith('/build/')) await cacheFirst(request);
                } catch (error) {
                    // Best effort only.
                }
            }
        })());
    }
});

// Web push: "a film on your watchlist is open", "seats opened on your waitlist".
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (error) { data = { title: 'BookMyMovie', body: event.data ? event.data.text() : '' }; }
    const url = typeof data.url === 'string' && data.url.startsWith(self.location.origin) ? data.url : self.location.origin + '/';
    event.waitUntil(self.registration.showNotification(data.title || 'BookMyMovie', {
        body: data.body || '',
        icon: data.icon || '/images/favicon/android-chrome-192x192.png',
        badge: '/images/favicon/android-chrome-192x192.png',
        tag: data.tag || undefined,
        renotify: Boolean(data.tag),
        data: { url },
    }));
});

// Focus an open tab on the target page, or open one.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil((async () => {
        for (const client of await self.clients.matchAll({ type: 'window', includeUncontrolled: true })) {
            if (client.url === target && 'focus' in client) return client.focus();
        }
        return self.clients.openWindow(target);
    })());
});
