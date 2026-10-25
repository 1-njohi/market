\<template>
    <div class="no-scrollbar flex max-w-full items-center gap-0.5 overflow-x-auto rounded-xl border border-white/[0.08] bg-white/[0.04] p-1" role="group" aria-label="Time period">
        <button
            v-for="p in periods"
            :key="p"
            type="button"
            @click="$emit('update:modelValue', p)"
            :aria-pressed="modelValue === p"
            :class="[
                'min-h-9 min-w-10 cursor-pointer rounded-lg px-3 text-xs font-bold transition-all focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none',
                modelValue === p
                    ? 'bg-sky-400 text-[#070b14] shadow-[0_6px_16px_-6px_rgba(56,189,248,0.7)]'
                    : 'text-slate-400 hover:bg-white/[0.06] hover:text-white',
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
    '24h': '24h',
    '7d': '7d',
    '30d': '30d',
    '90d': '90d',
    ytd: 'YTD',
    all: 'All',
};

const labelFor = (p) => LABELS[p] ?? p;
const shortLabel = (p) => SHORT_LABELS[p] ?? p.toUpperCase();
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>