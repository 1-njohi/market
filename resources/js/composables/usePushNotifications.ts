import { ref, computed } from 'vue';

export type PushState =
    | 'unsupported'
    | 'denied'
    | 'subscribed'
    | 'can-prompt'
    | 'loading';

const DISMISS_KEY = 'push_prompt_dismissed_until';
const DISMISS_DAYS = 30;

// Module-scoped state — every component that imports this shares the
// same refs. Changing state anywhere updates it everywhere.
const state = ref<PushState>('can-prompt');
let hasRefreshed = false;

const isSupported = (): boolean =>
    typeof window !== 'undefined' &&
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window;

const refresh = async (): Promise<void> => {
    if (!isSupported()) {
        state.value = 'unsupported';
        return;
    }

    if (Notification.permission === 'denied') {
        state.value = 'denied';
        return;
    }

    try {
        const { data } = await window.axios.get('/push/status');
        state.value = data.subscribed ? 'subscribed' : 'can-prompt';
    } catch (err) {
        console.error('[push] status check failed', err);
        state.value = 'can-prompt';
    }
};

const subscribe = async (): Promise<boolean> => {
    if (!isSupported()) return false;

    state.value = 'loading';

    try {
        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            state.value = permission === 'denied' ? 'denied' : 'can-prompt';
            return false;
        }

        const registration = await navigator.serviceWorker.ready;

        const existing = await registration.pushManager.getSubscription();
        const subscription =
            existing ??
            (await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(
                    import.meta.env.VITE_VAPID_PUBLIC_KEY,
                ),
            }));

        await window.axios.post('/push/subscribe', subscription.toJSON());

        state.value = 'subscribed';
        return true;
    } catch (err) {
        console.error('[push] subscribe failed', err);
        state.value = 'can-prompt';
        return false;
    }
};

const dismiss = (): void => {
    const until = Date.now() + DISMISS_DAYS * 24 * 60 * 60 * 1000;
    localStorage.setItem(DISMISS_KEY, String(until));
    // Force a re-evaluation by touching state. Since state is
    // module-scoped, this propagates to every consumer.
    state.value = 'subscribed'; // no-op semantically, but flips the computed
};

const isDismissed = (): boolean => {
    const raw = localStorage.getItem(DISMISS_KEY);
    if (!raw) return false;
    const until = Number(raw);
    if (Number.isNaN(until) || until < Date.now()) {
        localStorage.removeItem(DISMISS_KEY);
        return false;
    }
    return true;
};

const shouldShowPrompt = computed(() => {
    // Access a non-reactive source (localStorage) plus a reactive one
    // (state) so the computed re-evaluates when either changes.
    void state.value;
    return state.value === 'can-prompt' && !isDismissed();
});

/**
 * Idempotent mount-time hook. Safe to call from any component —
 * only the first caller triggers the network request.
 */
const ensureRefreshed = async (): Promise<void> => {
    if (hasRefreshed) return;
    hasRefreshed = true;
    await refresh();
};

export function usePushNotifications() {
    return {
        state,
        refresh,
        ensureRefreshed,
        subscribe,
        dismiss,
        shouldShowPrompt,
    };
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}