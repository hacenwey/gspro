// GestionPro — service worker (PWA + offline shell).
// Strategy:
//  - Precache the offline fallback page on install.
//  - Navigation (HTML): network-first, fall back to the cached page, then offline.html.
//  - Same-origin static assets + cross-origin fonts/CDN: cache-first.
//  - Never touch non-GET requests or API/sell endpoints (POS sync must reach the network).

const VERSION = 'gp-v3-2026-07-18';
const STATIC_CACHE = 'gp-static-' + VERSION;
const PAGE_CACHE   = 'gp-pages-'  + VERSION;

const STATIC_ASSETS = [
    '/offline.html',
    // Brand marks: the sidebar would otherwise show a broken image offline.
    '/public/img/logo-mark.png',
    '/public/img/icon-192.png',
];

// Cross-origin hosts whose CSS/fonts we want available offline (icons, webfonts).
const CDN_HOSTS = [
    'fonts.googleapis.com',
    'fonts.gstatic.com',
    'cdnjs.cloudflare.com',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            // allSettled so one failed asset never blocks the whole install.
            .then(c => Promise.allSettled(STATIC_ASSETS.map(a => c.add(a))))
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

async function cacheFirst(req, cacheName) {
    const cached = await caches.match(req);
    if (cached) return cached;
    try {
        const fresh = await fetch(req);
        if (fresh && (fresh.ok || fresh.type === 'opaque')) {
            const cache = await caches.open(cacheName);
            cache.put(req, fresh.clone());
        }
        return fresh;
    } catch (_) {
        return cached || new Response('', { status: 504 });
    }
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Never cache API or sale endpoints — they must hit the network (or fail loudly).
    if (url.pathname.includes('/api/') || url.pathname.includes('/caisse/sell') || url.pathname.includes('/caisse/search')) {
        return;
    }

    // Cross-origin fonts / icon CDNs: cache-first.
    if (CDN_HOSTS.includes(url.hostname)) {
        event.respondWith(cacheFirst(req, STATIC_CACHE));
        return;
    }

    if (url.origin !== self.location.origin) return;

    // Navigation (HTML): network-first with offline fallback.
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

    // Same-origin static assets: cache-first.
    if (/\.(css|js|woff2?|ttf|otf|png|jpg|jpeg|svg|ico|webp)$/i.test(url.pathname)) {
        event.respondWith(cacheFirst(req, STATIC_CACHE));
    }
});
