<template>
    <span
        v-if="value !== null && value !== undefined"
        :class="['rounded-full px-2 py-0.5 text-xs font-bold tabular-nums', chipClass]"
    >
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, default: null },
    suffix: { type: String, default: '%' },
    /** For "lower is better" metrics like refund rate. */
    invert: { type: Boolean, default: false },
});

const label = computed(() => {
    if (props.value === null || props.value === undefined) return '';
    const sign = props.value > 0 ? '+' : '';
    return `${sign}${props.value}${props.suffix}`;
});

const chipClass = computed(() => {
    const v = props.value ?? 0;
    if (v === 0) return 'bg-slate-400/10 text-slate-400';

    const positive = props.invert ? v < 0 : v > 0;
    return positive
        ? 'bg-emerald-400/10 text-emerald-300'
        : 'bg-rose-400/10 text-rose-300';
});
</script>