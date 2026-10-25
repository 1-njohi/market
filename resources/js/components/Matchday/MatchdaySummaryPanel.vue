<script setup lang="ts">
import { computed } from 'vue';
import Panel from '@/components/Panel.vue';
import SampleMetricCard from '@/components/SampleMetricCard.vue';
import FormGuideStrip from '@/components/Matchday/FormGuideStrip.vue';

const props = defineProps({
    summary: { type: Object, default: null },
    season: { type: String, default: '' },
    formGuide: { type: Array, default: () => [] },
});

const isFresh = computed(() => !props.summary);

const accuracyLabel = computed(() =>
    props.summary?.pick_accuracy != null
        ? `${props.summary.pick_accuracy.toFixed(1)}%`
        : '—',
);

const ppmLabel = computed(() =>
    props.summary?.points_per_matchday != null
        ? props.summary.points_per_matchday.toFixed(1)
        : '—',
);

const completionLabel = computed(() =>
    props.summary?.completion_pct != null
        ? `${props.summary.completion_pct.toFixed(0)}%`
        : '—',
);
</script>

<template>
    <Panel :title="`SEASON SUMMARY [${season}]`" accent="amber">
        <!-- Fresh state — mirror the seller console's SampleMetricCard -->
        <div v-if="isFresh" class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
            <SampleMetricCard label="Pick Accuracy" sample="68%" sublabel="Correct picks / submitted"
                subsample="152 / 224" hint="Unlocks after your first matchday settles" />
            <SampleMetricCard label="Points per Matchday" sample="4.3" sublabel="Season average" subsample="MD 1 – 7"
                hint="Unlocks after your first matchday settles" />
        </div>

        <!-- Populated state -->
        <div v-else-if="summary">
            <div class="grid grid-cols-2 gap-4 p-4 md:grid-cols-3">
                <!-- Pick accuracy -->
                <div>
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Pick Accuracy
                    </span>
                    <div class="mt-1 font-mono text-3xl font-black text-white">
                        {{ accuracyLabel }}
                    </div>
                    <span class="mt-0.5 block font-mono text-[9px] text-slate-600">
                        {{ summary.picks_submitted }} picks submitted
                    </span>
                </div>

                <!-- Points per matchday -->
                <div>
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Points / Matchday
                    </span>
                    <div class="mt-1 font-mono text-3xl font-black text-amber-400">
                        {{ ppmLabel }}
                    </div>
                    <span class="mt-0.5 block font-mono text-[9px] text-slate-600">
                        across {{ summary.matchdays_played }} matchdays
                    </span>
                </div>

                <!-- Current streak -->
                <div>
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Current Streak
                    </span>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="font-mono text-3xl font-black"
                            :class="summary.current_streak >= 3 ? 'text-emerald-400' : summary.current_streak > 0 ? 'text-white' : 'text-slate-500'">
                            {{ summary.current_streak }}
                        </span>
                        <!-- Form Guide Strip -->
                        <FormGuideStrip :guide="formGuide" class="mt-1" />
                    </div>
                    <span class="mt-0.5 block font-mono text-[9px] text-slate-600">
                        {{ summary.current_streak === 1 ? 'matchday' : 'matchdays' }} with ≥ 1 correct
                    </span>
                </div>
            </div>

            <!-- Footer row — mirrors the seller console's 3-column footer -->
            <div class="grid grid-cols-3 divide-x divide-[#232d42]/60 border-t border-[#232d42]/60">
                <div class="p-3 text-center">
                    <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">
                        Matchdays Played
                    </span>
                    <span class="mt-1 block font-mono text-sm font-black text-white">
                        {{ summary.matchdays_played }}
                    </span>
                </div>
                <div class="p-3 text-center">
                    <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">
                        Avg Picks / MD
                    </span>
                    <span class="mt-1 block font-mono text-sm font-black text-white">
                        {{ summary.avg_picks_per_matchday?.toFixed(1) ?? '—' }}
                    </span>
                </div>
                <div class="p-3 text-center">
                    <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">
                        Completion
                    </span>
                    <span class="mt-1 block font-mono text-sm font-black text-sky-400">
                        {{ completionLabel }}
                    </span>
                </div>
            </div>
        </div>
    </Panel>
</template>