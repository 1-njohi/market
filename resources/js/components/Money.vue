<template>
    <span class="font-mono tabular-nums">
        <span v-if="showCurrency" :class="currencyClass">{{ currency }}</span>
        <span :class="amountClass">{{ formatted }}</span>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: [Number, String], default: 0 },
    currency: { type: String, default: 'KES' },
    /** Fixed decimals. Pass null to preserve trailing zeros = off. */
    decimals: { type: Number, default: 2 },
    /** Prefix a "+" for positive values (useful for ledgers). */
    signed: { type: Boolean, default: false },
    /** Render the currency code before the number. */
    showCurrency: { type: Boolean, default: true },
    currencyClass: { type: String, default: 'mr-1 text-slate-400' },
    amountClass: { type: String, default: '' },
});

const numeric = computed(() => Number(props.value ?? 0));

const formatted = computed(() => {
    const n = numeric.value;
    const abs = Math.abs(n).toLocaleString(undefined, {
        minimumFractionDigits: props.decimals,
        maximumFractionDigits: props.decimals,
    });

    if (props.signed && n > 0) return `+${abs}`;
    if (n < 0) return `-${abs}`;
    return abs;
});
</script>