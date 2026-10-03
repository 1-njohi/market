self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    console.log('[SW] push event fired');

    let data = {};
    try {
        data = event.data?.json() ?? {};
    } catch {
        data = { title: 'Betslip Pirates', body: event.data?.text() ?? '' };
    }

    const title = data.title ?? 'Betslip Pirates';
    const options = {
        body: data.body ?? '',
        icon: data.icon ?? '/img/logo-192.png',
        badge: data.badge ?? '/img/badge-72.png',
        data: data.data ?? {},
    };

    event.waitUntil(
        self.registration
            .showNotification(title, options)
            .then(() => console.log('[SW] notification shown OK'))
            .catch((err) =>
                console.error('[SW] showNotification FAILED:', err.name, err.message),
            ),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url ?? '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const c of list) {
                if (c.url === url && 'focus' in c) return c.focus();
            }
            return self.clients.openWindow(url);
        }),
    );
});