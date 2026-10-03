export function initializeServiceWorker(): void {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    // Only register in production. In dev, a stale service worker caching
    // old responses is the single most confusing bug in Inertia — skip it
    // unless you're actively working on push.
    if (import.meta.env.DEV) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js')
            .then((registration) => {
                console.log('[SW] registered', registration.scope);
            })
            .catch((err) => {
                console.error('[SW] registration failed', err);
            });
    });
}