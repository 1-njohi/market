<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps({
    crowdWisdom: { type: Object, default: null },
    defaultLens: { type: String, default: 'all' },
    winningSelections: { type: Array, default: () => [] },
    userSelection: { type: String, default: null },
    legStatus: { type: String, default: 'pending' },
});

const isVisible = computed(() => props.crowdWisdom?.is_visible === true);
const rawCount = computed(() => props.crowdWisdom?.raw_count ?? 0);
const threshold = computed(() => props.crowdWisdom?.threshold ?? 0);

// Available lenses derived from the payload shape.
const availableLenses = computed(() => {
    if (!props.crowdWisdom?.is_visible) return [];

    const lenses: { key: string; label: string }[] = [
        { key: 'all', label: 'All voters' },
    ];

    if (props.crowdWisdom.sellers) {
        lenses.push({ key: 'sellers', label: 'Sellers only' });
    }

    if (props.crowdWisdom.top_percentiles) {
        for (const pct of Object.keys(props.crowdWisdom.top_percentiles)) {
            if (props.crowdWisdom.top_percentiles[pct]) {
                lenses.push({ key: `top_${pct}`, label: `Top ${pct}%` });
            }
        }
    }

    return lenses;
});

const activeLens = ref(props.defaultLens);

const activeDistribution = computed(() => {
    const cw = props.crowdWisdom;
    if (!cw?.is_visible) return null;

    if (activeLens.value === 'all') return cw.all;
    if (activeLens.value === 'sellers') return cw.sellers;
    if (activeLens.value.startsWith('top_')) {
        const pct = activeLens.value.replace('top_', '');
        return cw.top_percentiles?.[pct] ?? null;
    }
    return null;
});

const entries = computed(() => {
    if (!activeDistribution.value) return [];
    return Object.entries(activeDistribution.value).sort((a, b) => b[1] - a[1]);
});

const talkingPoint = computed(() => {
    if (!isVisible.value || props.legStatus === 'pending') return null;

    const topEntry = entries.value[0];
    if (!topEntry) return null;

    const [crowdPick, crowdPct] = topEntry;

    const winner = props.winningSelections[0] || 'Unknown';
    const crowdWon = props.winningSelections.includes(crowdPick);

    const isContrarian =
        props.userSelection && props.userSelection !== crowdPick;

    if (!crowdWon) {
        if (isContrarian && props.legStatus === 'won') {
            return `The seller was a contrarian here. ${crowdPct}% of the crowd backed ${crowdPick}, but the seller picked ${props.userSelection} and won.`;
        }
        if (isContrarian && props.legStatus === 'lost') {
            return `The seller tried to be a contrarian, but failed. ${crowdPct}% backed ${crowdPick}, the seller backed ${props.userSelection}, and ${winner} won.`;
        }
        return `The crowd got this one wrong. ${crowdPct}% backed ${crowdPick}, but ${winner} won.`;
    }

    if (isContrarian && props.legStatus === 'won') {
        return `The seller was right, but it was a contrarian call. ${crowdPct}% backed ${crowdPick}, yet the seller picked ${props.userSelection} and won.`;
    }
    if (isContrarian && props.legStatus === 'lost') {
        return `The crowd was right, and the seller tried to be contrarian. ${crowdPct}% backed ${crowdPick}, the seller backed ${props.userSelection}, and ${winner} won.`;
    }
    return `The crowd nailed this one. ${crowdPct}% backed ${crowdPick} and it won.`;
});
</script>

<template>
    <div class="mt-2 rounded border border-[#232d42] bg-[#0f1422] px-3 py-2">
        <!-- Below threshold -->
        <template v-if="!isVisible">
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">
                    Crowd wisdom
                </span>
                <span class="text-[9px] font-bold text-slate-600">
                    {{ rawCount }} / {{ threshold }}
                </span>
            </div>
            <div class="mt-1.5 h-1 w-full overflow-hidden rounded-full bg-[#232d42]">
                <div
                    class="h-full bg-sky-500/40 transition-all"
                    :style="{ width: `${Math.min(100, (rawCount / Math.max(1, threshold)) * 100)}%` }"
                ></div>
            </div>
            <p class="mt-1.5 text-[10px] text-slate-500">
                Appears when {{ threshold }} predictions are in.
                Currently {{ rawCount }}.
            </p>
        </template>

        <!-- Visible distribution -->
        <template v-else>
            <div class="flex items-center justify-between">
                <span class="text-[9px] font-black tracking-widest text-sky-400 uppercase">
                    Crowd wisdom
                </span>
                <select
                    v-model="activeLens"
                    class="cursor-pointer rounded border border-[#232d42] bg-[#161c2a] px-1.5 py-0.5 text-[9px] font-black tracking-widest text-slate-300 uppercase focus:border-sky-500 focus:outline-none"
                >
                    <option v-for="lens in availableLenses" :key="lens.key" :value="lens.key">
                        {{ lens.label }}
                    </option>
                </select>
            </div>

            <div v-if="entries.length === 0" class="py-2 text-center text-[10px] text-slate-500">
                No predictions from this group yet.
            </div>

            <div v-else class="mt-2 space-y-1">
                <div v-for="[selection, pct] in entries" :key="selection" class="flex items-center gap-2">
                    <span class="w-14 shrink-0 truncate font-mono text-[10px] font-bold text-slate-300">
                        {{ selection }}
                    </span>
                    <div class="h-1.5 flex-1 overflow-hidden rounded bg-[#232d42]">
                        <div class="h-full bg-sky-500/60" :style="{ width: `${pct}%` }"></div>
                    </div>
                    <span class="w-8 shrink-0 text-right font-mono text-[10px] text-white">
                        {{ pct }}%
                    </span>
                </div>
            </div>

            <!-- Talking Point -->
            <div
                v-if="talkingPoint"
                class="mt-2 border-t border-[#232d42] pt-2 text-[10px] leading-relaxed italic text-slate-300"
            >
                {{ talkingPoint }}
            </div>

            <div class="mt-2 border-t border-[#232d42] pt-1.5 text-[9px] text-slate-500">
                {{ rawCount }} predictions · weighted by confidence &amp; reputation
            </div>
        </template>
    </div>
</template>