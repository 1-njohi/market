import { onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';

/**
 * Polls /wallet/balance and exposes:
 *  - balance: current value
 *  - previous: value before last change
 *  - direction: 'up' | 'down' | null (cleared after 3s)
 *
 * Polling pauses when the tab is hidden.
 */
export function useLiveBalance(initial = 0, { intervalMs = 20_000 } = {}) {
    const balance = ref(Number(initial) || 0);
    const previous = ref(null);
    const direction = ref(null);

    let timer = null;
    let pulseTimer = null;

    const fetch = async () => {
        try {
            const { data } = await axios.get('/wallet/balance', {
                headers: { Accept: 'application/json' },
            });
            const next = Number(data?.balance ?? 0);
            if (next !== balance.value) {
                previous.value = balance.value;
                direction.value = next > balance.value ? 'up' : 'down';
                balance.value = next;

                if (pulseTimer) clearTimeout(pulseTimer);
                pulseTimer = setTimeout(() => {
                    direction.value = null;
                }, 3000);
            }
        } catch {
            // silent — network blips shouldn't flash the UI
        }
    };

    const onVisibility = () => {
        if (document.hidden) {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        } else if (!timer) {
            fetch();
            timer = setInterval(fetch, intervalMs);
        }
    };

    onMounted(() => {
        fetch();
        timer = setInterval(fetch, intervalMs);
        document.addEventListener('visibilitychange', onVisibility);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
        if (pulseTimer) clearTimeout(pulseTimer);
        document.removeEventListener('visibilitychange', onVisibility);
    });

    return { balance, previous, direction };
}