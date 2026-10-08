<template>
    <span
        class="font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase"
        :title="absoluteTitle"
    >
        {{ display }}
    </span>
</template>

<script setup>
import { computed } from 'vue';
import { useTicker } from '@/composables/useTicker';

const props = defineProps({
    at: { type: [String, Number, Date], required: true },
});

const { now } = useTicker();

const parsedAt = computed(() => {
    const t = new Date(props.at).getTime();
    return Number.isNaN(t) ? null : t;
});

const display = computed(() => {
    if (!parsedAt.value) return 'Updated —';
    const diffMs = now.value - parsedAt.value;
    const diffSec = Math.floor(diffMs / 1000);

    if (diffSec < 30) return 'Updated just now';
    if (diffSec < 60) return `Updated ${diffSec}s ago`;

    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) return `Updated ${diffMin}m ago`;

    const diffHr = Math.floor(diffMin / 60);
    if (diffHr < 24) return `Updated ${diffHr}h ago`;

    return 'Updated ' + new Date(parsedAt.value).toLocaleDateString();
});

const absoluteTitle = computed(() => {
    if (!parsedAt.value) return '';
    return new Date(parsedAt.value).toLocaleString();
});
</script>