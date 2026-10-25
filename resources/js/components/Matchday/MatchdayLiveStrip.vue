<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    row: { type: Object, required: true },
});

const isLive = computed(() => props.row.legs_resolved > 0);
const isLocked = computed(() => props.row.is_locked);

const accentClass = computed(() =>
    isLocked.value
        ? 'border-sky-500/40 bg-gradient-to-r from-sky-500/10 via-transparent to-transparent'
        : 'border-[#232d42] bg-[#111622]'
);

const badgeClass = computed(() =>
    isLocked.value ? 'text-sky-400' : 'text-slate-400'
);

const dotClass = computed(() =>
    isLocked.value ? 'bg-sky-400 animate-pulse' : 'bg-slate-500'
);
const statusLabel = computed(() =>
    props.row.status === 'underway' ? 'Live' : props.row.status === 'locked' ? 'Locked' : 'Open'
);

const accentClass = computed(() =>
    props.row.status === 'underway'
        ? 'border-sky-500/40 bg-gradient-to-r from-sky-500/10 via-transparent to-transparent'
        : 'border-[#232d42] bg-[#111622]'
);

const badgeClass = computed(() =>
    props.row.status === 'underway'
        ? 'text-sky-400'
        : 'text-slate-400'
);

const dotClass = computed(() =>
    props.row.status === 'underway' ? 'bg-sky-400 animate-pulse' : 'bg-slate-500'
);

const rankDeltaLabel = computed(() => {
    if (!props.row.rank_previous || !props.row.rank_current) return null;
    if (props.row.rank_delta === 0) return { text: 'no change', class: 'text-slate-500' };
    if (props.row.rank_delta > 0) return { text: `↑ ${props.row.rank_delta}`, class: 'text-emerald-400' };
    return { text: `↓ ${Math.abs(props.row.rank_delta)}`, class: 'text-rose-400' };
});
</script>

<template>
    <div class="rounded-lg border p-4 md:p-5" :class="accentClass">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-black tracking-widest uppercase"
                        :class="badgeClass">
                        <span class="inline-block h-1.5 w-1.5 rounded-full" :class="dotClass"></span>
                        {{ isLive ? 'Live' : isLocked ? 'Locked' : 'Open' }} · MD {{ row.matchday_number }} · {{
                        row.league_code }}
                    </span>
                </div>
                <h3 class="mt-1 truncate font-mono text-sm font-black text-white">
                    {{ row.name }}
                </h3>
                <div class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1 font-mono text-[11px]">
                    <span class="text-emerald-400">
                        <span class="font-black">{{ row.correct }}</span> correct
                    </span>
                    <span class="text-rose-400">
                        <span class="font-black">{{ row.incorrect }}</span> incorrect
                    </span>
                    <span class="text-slate-400">
                        <span class="font-black">{{ row.pending }}</span> pending
                    </span>
                    <span class="text-slate-500">·</span>
                    <span class="text-slate-500">
                        {{ row.legs_resolved }} / {{ row.legs_total }} legs resolved
                    </span>
                </div>
                <div v-if="row.rank_current" class="mt-2 flex items-center gap-3 font-mono text-[11px]">
                    <span class="text-slate-400">
                        Rank <span class="font-black text-white">#{{ row.rank_current }}</span>
                    </span>
                    <span v-if="rankDeltaLabel" :class="rankDeltaLabel.class"
                        class="text-[10px] font-black tracking-widest uppercase">
                        {{ rankDeltaLabel.text }}
                    </span>
                </div>
            </div>
            <Link :href="`/contests/${row.uuid}/picks`"
                class="flex-shrink-0 rounded-lg border border-sky-500/50 bg-[#111622] px-4 py-2 text-[10px] font-black tracking-widest text-sky-300 uppercase transition-colors hover:bg-[#16223a]">
                View picks →
            </Link>
        </div>
    </div>
</template>