<template>
    <span
        v-if="value !== null && value !== undefined"
        :class="[
            'rounded px-1.5 py-0.5 font-mono text-[9px] font-black tracking-wide uppercase',
            chipClass,
        ]"
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
    if (v === 0) return 'bg-slate-500/10 text-slate-400';

    const positive = props.invert ? v < 0 : v > 0;
    return positive
        ? 'bg-emerald-500/10 text-emerald-400'
        : 'bg-rose-500/10 text-rose-400';
});
</script>