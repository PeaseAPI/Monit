const CACHE_NAME = 'monit-pwa-v1';
const OFFLINE_URL = '/maintenance';

// 安装 Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll([
                '/',
                '/maintenance',
            ]);
        })
    );
    self.skipWaiting();
});

// 激活 Service Worker，清除旧缓存
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// 网络优先策略（API 请求不缓存）
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    // 跳过 API 和像素跟踪请求
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/pixel-track/')) return;

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // 缓存成功的响应
                if (response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                // 网络失败时返回缓存
                return caches.match(event.request).then((cachedResponse) => {
                    return cachedResponse || caches.match(OFFLINE_URL);
                });
            })
    );
});

// Push 通知处理
self.addEventListener('push', (event) => {
    let data = { title: 'Monit', body: '您有一条新通知' };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon || '/pwa/icon-192.png',
            badge: '/pwa/badge-72.png',
            data: data.url || '/',
            vibrate: [100, 50, 100],
        })
    );
});

// 通知点击处理
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});
