import { ref, computed } from 'vue';
import axios from 'axios';

export type PushState =
    | 'unsupported'
    | 'denied'
    | 'subscribed'
    | 'can-prompt'
    | 'loading';

const DISMISS_KEY = 'push_prompt_dismissed_until';
const DISMISS_DAYS = 30;

const state = ref<PushState>('can-prompt');
let hasRefreshed = false;

const isSupported = (): boolean =>
    typeof window !== 'undefined' &&
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window;

/**
 * Reconcile three signals:
 *   - Browser support (static)
 *   - Notification.permission (browser-level, per-profile)
 *   - Whether THIS BROWSER has a push subscription (not "does this user
 *     have any subscriptions anywhere" — that's a different question
 *     and would permanently hide the prompt on a second device).
 */
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
        const registration = await navigator.serviceWorker.ready;
        const localSub = await registration.pushManager.getSubscription();

        if (localSub) {
            // Local subscription exists. Make sure the server knows
            // about it — reconciliation for the case where the POST
            // failed on a previous attempt.
            try {
                await axios.post('/push/subscribe', localSub.toJSON());
            } catch (syncErr) {
                console.warn('[push] failed to sync existing subscription', syncErr);
            }
            state.value = 'subscribed';
            return;
        }

        // No local subscription. This browser can be prompted,
        // regardless of whether the user has subscriptions on other
        // devices.
        state.value = 'can-prompt';
    } catch (err) {
        console.error('[push] status check failed', err);
        state.value = 'can-prompt';
    }
};

const subscribe = async (): Promise<boolean> => {
    if (!isSupported()) return false;

    state.value = 'loading';

    try {
        const vapidKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;
        if (!vapidKey) {
            console.error(
                '[push] VITE_VAPID_PUBLIC_KEY is not set. ' +
                'Add it to .env and rebuild (Vite inlines env vars at build time).',
            );
            state.value = 'can-prompt';
            return false;
        }

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
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            }));

        await axios.post('/push/subscribe', subscription.toJSON());

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
    state.value = 'subscribed';
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
    void state.value;
    return state.value === 'can-prompt' && !isDismissed();
});

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