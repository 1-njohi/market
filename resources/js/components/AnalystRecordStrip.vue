<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    record: {
        settled_count: number;
        won: number;
        lost: number;
        voided: number;
        open: number;
        total_profit_units: number;
        roi: number | null;
        win_rate: number | null;
    };
    variant?: 'compact' | 'default';
}>();

const showRates = computed(() => props.record.roi !== null && props.record.settled_count >= 10);

const profitLabel = computed(() => {
    const p = props.record.total_profit_units;
    if (p === 0) return '0.00u';
    return `${p > 0 ? '+' : '−'}${Math.abs(p).toFixed(2)}u`;
});

const roiLabel = computed(() => {
    if (props.record.roi === null) return '—';
    const r = props.record.roi;
    return `${r > 0 ? '+' : ''}${r.toFixed(1)}%`;
});

const roiTone = computed(() => {
    if (props.record.roi === null || props.record.roi === 0) return 'text-slate-400';
    return props.record.roi > 0 ? 'text-emerald-400' : 'text-red-400';
});
</script>

<template>
    <div
        :class="[
            'flex flex-wrap items-center gap-x-5 gap-y-2 rounded-lg border border-[#232d42] bg-[#111622] px-4 py-3',
            variant === 'compact' ? 'text-xs' : 'text-sm',
        ]"
    >
        <div class="flex items-baseline gap-1.5">
            <span class="font-mono font-black text-white">
                {{ record.won }}–{{ record.lost }}
            </span>
            <span v-if="record.voided > 0" class="text-[10px] font-bold uppercase tracking-widest text-slate-500">
                · {{ record.voided }} void
            </span>
        </div>

        <div v-if="record.open > 0" class="flex items-center gap-1">
            <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span>
            <span class="text-[10px] font-bold uppercase tracking-widest text-sky-400">
                {{ record.open }} open
            </span>
        </div>

        <div class="flex items-baseline gap-1">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Units</span>
            <span
                class="font-mono font-black"
                :class="record.total_profit_units > 0 ? 'text-emerald-400' : (record.total_profit_units < 0 ? 'text-red-400' : 'text-slate-400')"
            >
                {{ profitLabel }}
            </span>
        </div>

        <div class="flex items-baseline gap-1">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">ROI</span>
            <span class="font-mono font-black" :class="roiTone">{{ roiLabel }}</span>
        </div>

        <p v-if="!showRates" class="text-[10px] font-medium text-slate-500">
            ROI unlocks at 10 settled picks.
        </p>
    </div>
</template>