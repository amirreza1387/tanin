const CACHE_NAME = 'tanin-chat-shell-v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || !event.request.url.startsWith(self.location.origin)) return;
    event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/private-messages-7f3a';
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
        const existing = clients.find((client) => 'focus' in client);
        if (existing) { existing.focus(); existing.postMessage({ type: 'private-chat:notification-click', url: targetUrl }); return; }
        return self.clients.openWindow(targetUrl);
    }));
});
