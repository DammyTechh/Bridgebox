const CACHE_NAME = 'bridgebox-offline-v14';

const PRECACHE_URLS = [
    '/',
    '/login/admin',
    '/login/teacher',
    '/login/student',
    '/assets/css/auth.css',
    '/assets/css/dashboard.css',
    '/assets/js/auth.js',
    '/assets/js/dashboard.js',
    '/assets/js/landing.js',
    '/assets/js/login.js',
    '/assets/js/admin-actions.js',
    '/assets/js/admin-dashboard.js',
    '/assets/js/offline.js',
    '/assets/fonts/dm-sans-400.ttf',
    '/assets/fonts/dm-sans-500.ttf',
    '/assets/fonts/dm-sans-700.ttf',
    '/assets/fonts/sora-400.ttf',
    '/assets/fonts/sora-500.ttf',
    '/assets/fonts/sora-600.ttf',
    '/assets/fonts/sora-700.ttf',
    '/assets/images/favicon.png',
    '/assets/samples/students.csv'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const acceptHeader = event.request.headers.get('accept') || '';
    const isNavigation = event.request.mode === 'navigate' || acceptHeader.includes('text/html');

    event.respondWith(
        (async () => {
            if (isNavigation) {
                try {
                    const response = await fetch(event.request);
                    const cache = await caches.open(CACHE_NAME);
                    cache.put(event.request, response.clone());
                    return response;
                } catch (error) {
                    const cached = await caches.match(event.request);
                    return cached || caches.match('/');
                }
            }

            const cached = await caches.match(event.request);
            if (cached) {
                return cached;
            }

            try {
                const response = await fetch(event.request);
                const cache = await caches.open(CACHE_NAME);
                cache.put(event.request, response.clone());
                return response;
            } catch (error) {
                return caches.match('/');
            }
        })()
    );
});
