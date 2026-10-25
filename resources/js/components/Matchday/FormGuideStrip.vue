<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    guide: { type: Array, default: () => [] },
});

// Reverse to chronological order for display (oldest on left)
const displayGuide = computed(() => [...props.guide].reverse());

const colorClass = (quartile: string) => {
    if (quartile === 'green') return 'bg-emerald-500';
    if (quartile === 'red') return 'bg-rose-500';
    if (quartile === 'yellow') return 'bg-amber-500';
    return 'bg-slate-700'; // none
};

const getTooltip = (md: any) => {
    if (!md.entered) return `MD ${md.matchday_number}: Not entered`;
    return `MD ${md.matchday_number}: ${md.score_correct} correct`;
};
</script>

<template>
    <div v-if="displayGuide.length > 0" class="flex items-center gap-1">
        <div v-for="md in displayGuide" :key="md.matchday_number"
            class="h-2.5 w-2.5 rounded-sm transition-opacity hover:opacity-80" :class="colorClass(md.quartile)"
            :title="getTooltip(md)">
        </div>
    </div>
</template>