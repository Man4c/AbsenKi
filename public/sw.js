const CACHE_NAME = 'absenki-v2';
const OFFLINE_URL = '/offline.html';

// Assets yang akan di-cache saat install
const STATIC_CACHE_URLS = [
    '/offline.html',
    '/favicon.svg',
    '/favicon.ico',
    '/eabsensi.png'
];

// URL patterns yang TIDAK boleh di-cache (butuh CSRF token fresh)
const NOCACHE_PATTERNS = [
    '/login',
    '/register',
    '/password',
    '/logout',
    '/livewire',
    '/sanctum/csrf-cookie'
];

// Install service worker dan cache static assets
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Service Worker] Caching static assets');
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate service worker dan hapus cache lama
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[Service Worker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Helper: Check if URL should NOT be cached
function shouldNotCache(url) {
    return NOCACHE_PATTERNS.some(pattern => url.includes(pattern));
}

// Fetch strategy: Network First, fallback to Cache
self.addEventListener('fetch', (event) => {
    const requestUrl = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip Chrome extensions
    if (requestUrl.protocol === 'chrome-extension:') {
        return;
    }

    // JANGAN cache halaman auth dan livewire (butuh CSRF fresh)
    if (shouldNotCache(requestUrl.pathname)) {
        console.log('[SW] Skip cache:', requestUrl.pathname);
        return; // Let browser handle normally
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Clone response karena response hanya bisa digunakan sekali
                const responseToCache = response.clone();

                // Hanya cache static assets (CSS, JS, images, fonts)
                const isStaticAsset = /\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|ico)$/i.test(requestUrl.pathname);

                if (response.status === 200 && isStaticAsset) {
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                }

                return response;
            })
            .catch(() => {
                // Jika network gagal, gunakan cache
                return caches.match(event.request)
                    .then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }

                        // Jika tidak ada di cache, tampilkan offline page untuk navigasi
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }

                        // Return basic response untuk asset lainnya
                        return new Response('Offline', {
                            status: 503,
                            statusText: 'Service Unavailable',
                            headers: new Headers({
                                'Content-Type': 'text/plain'
                            })
                        });
                    });
            })
    );
});

// Handle messages from client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
