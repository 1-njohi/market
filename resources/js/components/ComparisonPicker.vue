<template>
    <div
        class="flex items-center rounded border border-[#232d42] bg-[#111622] p-0.5"
    >
        <button
            v-for="p in periods"
            :key="p"
            type="button"
            @click="$emit('update:modelValue', p)"
            :class="[
                'cursor-pointer rounded px-2 py-1 font-mono text-[9px] font-black tracking-widest uppercase transition-all',
                modelValue === p
                    ? 'bg-sky-500 text-[#070b14] shadow-[0_0_8px_rgba(14,165,233,0.3)]'
                    : 'text-slate-500 hover:text-white',
            ]"
            :title="labelFor(p)"
        >
            {{ shortLabel(p) }}
        </button>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: { type: String, required: true },
    periods: {
        type: Array,
        default: () => ['24h', '7d', '30d', '90d', 'ytd', 'all'],
    },
});

defineEmits(['update:modelValue']);

const LABELS = {
    '24h': 'Last 24 hours',
    '7d': 'Last 7 days',
    '30d': 'Last 30 days',
    '90d': 'Last 90 days',
    ytd: 'Year to date',
    all: 'All time',
};

const SHORT_LABELS = {
    '24h': '24H',
    '7d': '7D',
    '30d': '30D',
    '90d': '90D',
    ytd: 'YTD',
    all: 'ALL',
};

const labelFor = (p) => LABELS[p] ?? p;
const shortLabel = (p) => SHORT_LABELS[p] ?? p.toUpperCase();
</script>