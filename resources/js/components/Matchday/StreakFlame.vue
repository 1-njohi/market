<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    streak: { type: Number, default: 0 },
});

const label = computed(() => {
    if (props.streak >= 10) return 'On fire';
    if (props.streak >= 5) return 'Hot streak';
    return 'Building momentum';
});
</script>

<template>
    <div v-if="streak >= 2"
        class="inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5"
        :title="`${label}: ${streak} matchdays in a row with a correct pick`">
        <span :class="streak >= 10 ? 'animate-bounce' : 'animate-pulse'">🔥</span>
        <span class="font-mono text-[10px] font-black text-amber-400">{{ streak }}</span>
    </div>
</template>