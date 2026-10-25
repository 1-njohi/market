<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    points: { type: Number, default: 0 },
    rank: { type: Number, default: null },
    compact: { type: Boolean, default: false },
});

// Show the badge for anyone who has a reputation, even if it's negative.
// Only hide if they have exactly 0 points (meaning they haven't made any picks).
const hasReputation = computed(() => props.points !== 0);

const tone = computed(() => {
    if (!hasReputation.value) return 'text-slate-500 border-slate-500/20 bg-slate-500/5';
    
    if (props.points > 0) {
        if (props.rank !== null && props.rank <= 100) {
            return 'text-amber-400 border-amber-500/30 bg-amber-500/10';
        }
        if (props.rank !== null && props.rank <= 1000) {
            return 'text-sky-400 border-sky-500/30 bg-sky-500/10';
        }
        return 'text-emerald-400 border-emerald-500/30 bg-emerald-500/10';
    }
    
    // Negative reputation
    return 'text-rose-400 border-rose-500/30 bg-rose-500/10';
});

const fmt = (n: number) => n.toLocaleString('en-KE');

const label = computed(() => {
    if (props.points > 0) return '⭐';
    return '📉'; // Downward trend icon for negative reputation
});
</script>

<template>
    <div v-if="hasReputation" :class="['inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[9px] font-black tracking-widest uppercase', tone]">
        <span>{{ label }}</span>
        <span v-if="rank !== null && !compact">#{{ rank }} ·</span>
        <span>{{ fmt(points) }} pts</span>
    </div>
</template>