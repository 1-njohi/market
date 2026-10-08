import { ref, onMounted, onUnmounted } from 'vue';

const now = ref(Date.now());
let subscribers = 0;
let interval = null;

const start = () => {
    if (interval) return;
    interval = setInterval(() => {
        now.value = Date.now();
    }, 30_000);
};

const stop = () => {
    if (interval && subscribers === 0) {
        clearInterval(interval);
        interval = null;
    }
};

export function useTicker() {
    onMounted(() => {
        subscribers++;
        start();
    });
    onUnmounted(() => {
        subscribers--;
        stop();
    });
    return { now };
}