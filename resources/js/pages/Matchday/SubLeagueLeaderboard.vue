<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';
import InfoPopover from '@/components/InfoPopover.vue';

const page = usePage();

const subLeague = computed(() => page.props.sub_league);
const fromMatchday = computed(() => Number(page.props.from_matchday ?? 1));
const maxMatchday = computed(() => Number(page.props.max_matchday ?? 1));
const subRows = computed(() => page.props.sub_league_leaderboard ?? []);
const globalRows = computed(() => page.props.global_leaderboard ?? []);
const viewerId = computed(() => page.props.auth?.user?.id ?? null);

// ────────────── Tabs ──────────────
const activeTab = ref<'sub' | 'global'>('sub');

// ────────────── from_matchday filter ──────────────
const matchdayOptions = computed(() =>
    Array.from({ length: maxMatchday.value }, (_, i) => i + 1),
);

const setFromMatchday = (value: number) => {
    router.get(
        `/matchday/sub-leagues/${subLeague.value.id}/leaderboard`,
        { from_matchday: value },
        { preserveState: true, preserveScroll: true },
    );
};

const onFromMatchdayChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    setFromMatchday(Number(target.value));
};

// ────────────── Row helpers ──────────────
const isViewer = (row: { user_id: number }) =>
    viewerId.value !== null && row.user_id === viewerId.value;

const rankColorClass = (rank: number) => {
    if (rank === 1) return 'text-amber-400';
    if (rank === 2) return 'text-slate-300';
    if (rank === 3) return 'text-orange-400';
    return 'text-slate-500';
};

const currentRows = computed(() =>
    activeTab.value === 'sub' ? subRows.value : globalRows.value,
);
</script>

<template>

    <Head :title="`${subLeague.name} — Leaderboard`" />

    <div class="flex min-h-screen flex-col bg-[#070b14] px-2 pt-6 pb-24 font-sans text-slate-200 lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-5xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <div class="flex w-full items-start justify-between gap-3 border-b border-gray-800/60 pb-6">
                <div class="min-w-0">
                    <Link href="/matchday"
                        class="text-[10px] font-black tracking-widest text-amber-400 uppercase transition-colors hover:text-amber-300">
                        ← Matchday dashboard
                    </Link>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                            Sub-league Leaderboard
                        </span>
                        <InfoPopover title="How the leaderboard works">
                            <p>
                                <span class="font-bold text-slate-200">
                                    Sub-league view:
                                </span>
                                points summed across every settled matchday
                                between you and the other members of this
                                pool. Ties broken by who joined the
                                sub-league earliest.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">
                                    Global view:
                                </span>
                                same scoring, across every user with a
                                matchday entry for the season. Ties broken
                                by user id.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">
                                    Points:
                                </span>
                                one per correct pick. Missing picks score
                                zero. Void legs are excluded.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">
                                    From matchday:
                                </span>
                                filter to count only matchdays from a given
                                number onward. Useful for a "second half of
                                the season" view.
                            </p>
                        </InfoPopover>
                    </div>
                    <h1 class="mt-1 text-2xl font-black tracking-wider text-white uppercase md:text-3xl">
                        {{ subLeague.name }}
                    </h1>
                    <p class="mt-1 font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                        Season {{ subLeague.season }}
                    </p>
                </div>

                <div class="flex flex-shrink-0 items-center gap-2">
                    <DashboardSwitcher active="matchday" />
                </div>
            </div>

            <!-- ═══════════════ CONTROLS ═══════════════ -->
            <div
                class="flex flex-col gap-3 rounded-lg border border-[#232d42] bg-[#111622] p-3 md:flex-row md:items-center md:justify-between">
                <!-- Tabs -->
                <div class="flex items-center rounded border border-[#232d42] bg-[#0f1422] p-0.5">
                    <button type="button" @click="activeTab = 'sub'" :class="[
                        'cursor-pointer rounded px-4 py-1.5 text-[10px] font-black tracking-wider uppercase transition-all',
                        activeTab === 'sub'
                            ? 'bg-amber-500 text-[#070b14]'
                            : 'text-slate-400 hover:text-white',
                    ]">
                        Sub-league
                        <span class="ml-1 font-mono text-[9px] opacity-70">{{ subRows.length }}</span>
                    </button>
                    <button type="button" @click="activeTab = 'global'" :class="[
                        'cursor-pointer rounded px-4 py-1.5 text-[10px] font-black tracking-wider uppercase transition-all',
                        activeTab === 'global'
                            ? 'bg-amber-500 text-[#070b14]'
                            : 'text-slate-400 hover:text-white',
                    ]">
                        Global
                        <span class="ml-1 font-mono text-[9px] opacity-70">{{ globalRows.length }}</span>
                    </button>
                </div>

                <!-- from_matchday -->
                <div class="flex items-center gap-2">
                    <label for="from_matchday"
                        class="font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase">
                        From matchday
                    </label>
                    <select id="from_matchday" :value="fromMatchday" @change="onFromMatchdayChange"
                        class="cursor-pointer rounded border border-[#232d42] bg-[#0f1422] px-2 py-1 font-mono text-[11px] font-black text-white focus:border-amber-500 focus:outline-none">
                        <option v-for="n in matchdayOptions" :key="n" :value="n">
                            MD {{ n }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- ═══════════════ LEADERBOARD TABLE ═══════════════ -->
            <div class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <div v-if="currentRows.length > 0">
                    <!-- Header row -->
                    <div
                        class="grid grid-cols-12 gap-2 border-b border-[#232d42] bg-[#121824] px-4 py-2 text-[9px] font-black tracking-widest text-slate-500 uppercase">
                        <div class="col-span-2">Rank</div>
                        <div class="col-span-6">Player</div>
                        <div class="col-span-2 text-right">Matchdays</div>
                        <div class="col-span-2 text-right">Points</div>
                    </div>

                    <!-- Rows -->
                    <ul class="divide-y divide-[#232d42]/40">
                        <li v-for="row in currentRows" :key="row.user_id" :class="[
                            'grid grid-cols-12 items-center gap-2 px-4 py-3',
                            isViewer(row)
                                ? 'bg-amber-500/10'
                                : 'transition-colors hover:bg-[#111a30]/40',
                        ]">
                            <div class="col-span-2">
                                <span class="font-mono text-lg font-black" :class="rankColorClass(row.rank)">
                                    #{{ row.rank }}
                                </span>
                            </div>

                            <div class="col-span-6 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate font-mono text-sm font-black" :class="isViewer(row)
                                        ? 'text-amber-300'
                                        : 'text-white'
                                        ">
                                        {{ row.name }}
                                    </span>
                                    <span v-if="isViewer(row)"
                                        class="rounded-sm bg-amber-500/20 px-1 py-px text-[8px] font-black tracking-widest text-amber-400 uppercase">
                                        You
                                    </span>
                                </div>
                            </div>

                            <div class="col-span-2 text-right font-mono text-[11px] text-slate-500">
                                {{ row.matchdays_played }}
                            </div>

                            <div class="col-span-2 text-right font-mono text-sm font-black text-white">
                                {{ row.points }}
                                <span class="text-[10px] text-slate-500">pts</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Empty state -->
                <div v-else class="px-6 py-12 text-center">
                    <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        No settled matchdays yet
                    </p>
                    <p class="mx-auto mt-2 max-w-md text-[11px] leading-relaxed text-slate-500">
                        {{
                            activeTab === 'sub'
                                ? "Once matchdays start settling, this is where you'll see how you stack up against your sub - league."
                        : "The global board fills in as soon as the season's first matchday settles."
                        }}
                    </p>
                </div>
            </div>

            <!-- ═══════════════ FOOTER ═══════════════ -->
            <div class="text-center">
                <Link href="/matchday"
                    class="text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300">
                    ← Back to matchday dashboard
                </Link>
            </div>
        </div>
    </div>
</template>