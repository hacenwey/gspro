// GestionPro — minimal service worker.
// Strategy:
//  - Network-first for navigation (HTML), fallback to cache, then offline page.
//  - Cache-first for static assets (CSS/JS/fonts).
//  - Never cache API or POST requests.

const VERSION = 'gp-v1-2026-04-18';
const STATIC_CACHE = 'gp-static-' + VERSION;
const PAGE_CACHE   = 'gp-pages-'  + VERSION;

const STATIC_ASSETS = [
    '/offline.html',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(c => c.addAll(STATIC_ASSETS))
              .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(k => !k.endsWith(VERSION)).map(k => caches.delete(k))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Never cache API or POST-like endpoints
    if (url.pathname.includes('/api/') || url.pathname.includes('/caisse/sell')) return;

    // Navigation: network-first with offline fallback
    if (req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html')) {
        event.respondWith((async () => {
            try {
                const fresh = await fetch(req);
                const cache = await caches.open(PAGE_CACHE);
                cache.put(req, fresh.clone());
                return fresh;
            } catch (_) {
                const cached = await caches.match(req);
                return cached || caches.match('/offline.html');
            }
        })());
        return;
    }

    // Static assets: cache-first
    if (/\.(css|js|woff2?|ttf|otf|png|jpg|jpeg|svg|ico)$/i.test(url.pathname)) {
        event.respondWith((async () => {
            const cached = await caches.match(req);
            if (cached) return cached;
            try {
                const fresh = await fetch(req);
                if (fresh.ok) {
                    const cache = await caches.open(STATIC_CACHE);
                    cache.put(req, fresh.clone());
                }
                return fresh;
            } catch (_) {
                return cached || new Response('', { status: 504 });
            }
        })());
    }
});
