<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AnalystPickCard from '@/components/AnalystPickCard.vue';

const props = defineProps<{
    current?: any[];
    leaderboard?: any[];
    combined_record?: any;
}>();

const safeCurrent = computed(() => Array.isArray(props.current) ? props.current : []);

const safeLeaderboard = computed(() => Array.isArray(props.leaderboard) ? props.leaderboard : []);

// Defensive default: any missing field renders as 0 rather than
// crashing the whole homepage. `roi` stays null so the strip shows "—".
const safeRecord = computed(() => ({
    won:                props.combined_record?.won                ?? 0,
    lost:               props.combined_record?.lost               ?? 0,
    voided:             props.combined_record?.voided             ?? 0,
    open:               props.combined_record?.open               ?? 0,
    settled_count:      props.combined_record?.settled_count      ?? 0,
    total_profit_units: props.combined_record?.total_profit_units ?? 0,
    roi:                props.combined_record?.roi                ?? null,
    win_rate:           props.combined_record?.win_rate           ?? null,
}));

const hasContent = computed(() => safeCurrent.value.length > 0 || safeLeaderboard.value.length > 0);
</script>

<template>
    <section v-if="hasContent" class="mx-auto w-full max-w-7xl px-4 py-10 md:px-6 lg:px-8">
        <header class="mb-5 flex flex-wrap items-end justify-between gap-3 border-b border-[#232d42] pb-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-400">
                    This week's top picks
                </p>
                <h2 class="mt-1 text-xl font-black uppercase tracking-wider text-white md:text-2xl">
                    Our Analysts
                </h2>
            </div>
            <div class="flex gap-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                <Link href="/analysts" class="hover:text-sky-400">Directory →</Link>
                <Link href="/analyst-picks" class="text-sky-400 hover:underline">Full archive →</Link>
            </div>
        </header>

        <div v-if="safeCurrent.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <AnalystPickCard
                v-for="pick in safeCurrent"
                :key="pick.id"
                :pick="pick"
                variant="compact"
            />
        </div>
        <p v-else class="text-sm text-slate-500">
            No open picks this week.
            <Link href="/analyst-picks" class="text-sky-400 hover:underline">Browse the archive →</Link>
        </p>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-[#232d42] bg-[#111622] p-3">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                <span class="font-bold text-white">Combined Analyst Record</span>
                <span class="text-slate-500">·</span>
                <span class="font-mono text-white">{{ safeRecord.won }}–{{ safeRecord.lost }}</span>
                <span v-if="safeRecord.voided > 0" class="text-[10px] font-bold uppercase tracking-widest text-slate-500">
                    · {{ safeRecord.voided }} void
                </span>
                <span class="text-slate-500">·</span>
                <span class="font-mono"
                    :class="safeRecord.total_profit_units > 0
                        ? 'text-emerald-400'
                        : (safeRecord.total_profit_units < 0 ? 'text-red-400' : 'text-slate-400')">
                    {{ safeRecord.total_profit_units > 0 ? '+' : '' }}{{ safeRecord.total_profit_units.toFixed(2) }}u
                </span>
                <template v-if="safeRecord.roi !== null">
                    <span class="text-slate-500">·</span>
                    <span class="font-mono"
                        :class="safeRecord.roi > 0 ? 'text-emerald-400' : (safeRecord.roi < 0 ? 'text-red-400' : 'text-slate-400')">
                        {{ safeRecord.roi > 0 ? '+' : '' }}{{ safeRecord.roi }}% ROI
                    </span>
                </template>
                <span v-if="safeRecord.open > 0" class="flex items-center gap-1">
                    <span class="text-slate-500">·</span>
                    <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-sky-400">
                        {{ safeRecord.open }} open
                    </span>
                </span>
            </div>
            <Link href="/analysts"
                class="text-[10px] font-black uppercase tracking-widest text-sky-400 hover:underline">
                See all analysts →
            </Link>
        </div>
    </section>
</template>