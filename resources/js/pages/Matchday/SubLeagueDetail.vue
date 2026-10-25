<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';
import BuyerTabs from '@/components/BuyerTabs.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import SubLeagueActivityFeed from '@/components/Matchday/SubLeagueActivityFeed.vue';

const page = usePage();

const subLeague = computed(() => page.props.sub_league);
const members = computed(() => page.props.members ?? []);
const matchdays = computed(() => page.props.matchdays ?? []);
const subRows = computed(() => page.props.sub_league_leaderboard ?? []);
const globalRows = computed(() => page.props.global_leaderboard ?? []);
const fromMatchday = computed(() => Number(page.props.from_matchday ?? 1));
const maxMatchday = computed(() => Number(page.props.max_matchday ?? 1));
const viewerId = computed(() => page.props.auth?.user?.id ?? null);
const activity = computed(() => page.props.activity ?? []);

// ────────────── Tabs ──────────────
const TAB_KEYS = ['leaderboard', 'members', 'matchdays', 'activity'];
const tabs = computed(() => [
    { key: 'leaderboard', label: 'Leaderboard', accent: 'amber' },
    { key: 'members', label: 'Members', accent: 'sky', badge: members.value.length },
    { key: 'matchdays', label: 'Matchdays', accent: 'purple', badge: matchdays.value.length },
    { key: 'activity', label: 'Activity', accent: 'amber' },   // <— new
]);

const readHash = () => {
    const hash = (window.location.hash || '').replace('#', '');
    return TAB_KEYS.includes(hash) ? hash : 'leaderboard';
};

const activeTab = ref<'leaderboard' | 'members' | 'matchdays'>(readHash() as any);

const onHashChange = () => {
    activeTab.value = readHash() as any;
};

onMounted(() => {
    window.addEventListener('hashchange', onHashChange);
});

onUnmounted(() => {
    window.removeEventListener('hashchange', onHashChange);
});

watch(activeTab, async (val) => {
    if (window.location.hash !== `#${val}`) {
        history.replaceState(null, '', `#${val}`);
    }
});

// ────────────── Leaderboard filter ──────────────
const leaderboardScope = ref<'sub' | 'global'>('sub');
const matchdayOptions = computed(() =>
    Array.from({ length: maxMatchday.value }, (_, i) => i + 1),
);

const setFromMatchday = (value: number) => {
    router.get(
        `/matchday/sub-leagues/${subLeague.value.id}`,
        { from_matchday: value },
        { preserveState: true, preserveScroll: true, preserveUrl: true },
    );
};

const onFromMatchdayChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    setFromMatchday(Number(target.value));
};

// ────────────── Matchdays filter ──────────────
const activeLeague = ref<string>('all');

const leaguesInMatchdays = computed(() => {
    const set = new Set<string>();
    for (const md of matchdays.value) {
        if (md.league_code) set.add(md.league_code);
    }
    return Array.from(set).sort();
});

const filteredMatchdays = computed(() => {
    if (activeLeague.value === 'all') return matchdays.value;
    return matchdays.value.filter((md: any) => md.league_code === activeLeague.value);
});

// ────────────── Helpers ──────────────
const copyInvite = () => {
    if (!navigator?.clipboard) return;
    navigator.clipboard.writeText(subLeague.value.invite_code);
};

const isViewer = (row: { user_id: number }) =>
    viewerId.value !== null && row.user_id === viewerId.value;

const rankColorClass = (rank: number) => {
    if (rank === 1) return 'text-amber-400';
    if (rank === 2) return 'text-slate-300';
    if (rank === 3) return 'text-orange-400';
    return 'text-slate-500';
};

const currentRows = computed(() =>
    leaderboardScope.value === 'sub' ? subRows.value : globalRows.value,
);

const formatDate = (iso?: string | null) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('en-KE', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};
</script>

<template>

    <Head :title="`${subLeague.name} — Matchday`" />

    <div class="flex min-h-screen flex-col bg-[#070b14] px-2 pt-6 pb-24 font-sans text-slate-200 lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-5xl space-y-6">
            <!-- Header -->
            <div class="flex w-full items-start justify-between gap-3 border-b border-gray-800/60 pb-6">
                <div class="min-w-0">
                    <Link href="/matchday"
                        class="text-[10px] font-black tracking-widest text-amber-400 uppercase transition-colors hover:text-amber-300">
                        ← Matchday dashboard
                    </Link>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                            Sub-league
                        </span>
                        <span v-if="subLeague.is_owner"
                            class="rounded-sm bg-amber-500/20 px-1.5 py-0.5 text-[8px] font-black tracking-widest text-amber-400 uppercase">
                            Owner
                        </span>
                    </div>
                    <h1 class="mt-1 text-2xl font-black tracking-wider text-white uppercase md:text-3xl">
                        {{ subLeague.name }}
                    </h1>
                    <div
                        class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                        <span>Season {{ subLeague.season }}</span>
                        <span class="text-slate-700">·</span>
                        <span>{{ subLeague.member_count }} / {{ subLeague.max_members }} members</span>
                        <span class="text-slate-700">·</span>
                        <button type="button" @click="copyInvite" :title="`Copy invite code: ${subLeague.invite_code}`"
                            class="cursor-pointer rounded border border-[#232d42] bg-[#111622] px-2 py-0.5 text-[10px] font-black tracking-wider text-slate-400 uppercase transition-colors hover:border-amber-500/50 hover:text-amber-400">
                            {{ subLeague.invite_code }}
                        </button>
                    </div>
                </div>
                <div class="flex flex-shrink-0 items-center gap-2">
                    <DashboardSwitcher active="matchday" />
                </div>
            </div>

            <!-- Tab Content -->
            <Transition appear enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div :key="activeTab" class="space-y-6">

                    <!-- ═══ Tab: Leaderboard ═══ -->
                    <template v-if="activeTab === 'leaderboard'">
                        <div
                            class="flex flex-col gap-3 rounded-lg border border-[#232d42] bg-[#111622] p-3 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center rounded border border-[#232d42] bg-[#0f1422] p-0.5">
                                <button type="button" @click="leaderboardScope = 'sub'" :class="[
                                    'cursor-pointer rounded px-4 py-1.5 text-[10px] font-black tracking-wider uppercase transition-all',
                                    leaderboardScope === 'sub'
                                        ? 'bg-amber-500 text-[#070b14]'
                                        : 'text-slate-400 hover:text-white',
                                ]">
                                    Sub-league
                                </button>
                                <button type="button" @click="leaderboardScope = 'global'" :class="[
                                    'cursor-pointer rounded px-4 py-1.5 text-[10px] font-black tracking-wider uppercase transition-all',
                                    leaderboardScope === 'global'
                                        ? 'bg-amber-500 text-[#070b14]'
                                        : 'text-slate-400 hover:text-white',
                                ]">
                                    Global
                                </button>
                            </div>

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

                        <div class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                            <div v-if="currentRows.length > 0">
                                <div
                                    class="grid grid-cols-12 gap-2 border-b border-[#232d42] bg-[#121824] px-4 py-2 text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                    <div class="col-span-2">Rank</div>
                                    <div class="col-span-6">Player</div>
                                    <div class="col-span-2 text-right">Matchdays</div>
                                    <div class="col-span-2 text-right">Points</div>
                                </div>
                                <ul class="divide-y divide-[#232d42]/40">
                                    <li v-for="row in currentRows" :key="row.user_id" :class="[
                                        'grid grid-cols-12 items-center gap-2 px-4 py-3',
                                        isViewer(row) ? 'bg-amber-500/10' : 'transition-colors hover:bg-[#111a30]/40',
                                    ]">
                                        <div class="col-span-2">
                                            <span class="font-mono text-lg font-black"
                                                :class="rankColorClass(row.rank)">
                                                #{{ row.rank }}
                                            </span>
                                        </div>
                                        <div class="col-span-6 min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="truncate font-mono text-sm font-black"
                                                    :class="isViewer(row) ? 'text-amber-300' : 'text-white'">
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
                            <div v-else class="px-6 py-12 text-center">
                                <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                                    No settled matchdays yet
                                </p>
                            </div>
                        </div>
                    </template>

                    <!-- ═══ Tab: Members ═══ -->
                    <template v-else-if="activeTab === 'members'">
                        <div class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                            <div
                                class="border-b border-[#232d42] bg-[#121824] px-4 py-2 text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                {{ members.length }} members
                            </div>
                            <ul class="divide-y divide-[#232d42]/40">
                                <li v-for="m in members" :key="m.user_id" :class="[
                                    'flex items-center justify-between gap-3 px-4 py-3',
                                    m.user_id === viewerId ? 'bg-amber-500/5' : '',
                                ]">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#0f1422] font-mono text-[10px] font-black text-slate-400">
                                            {{ m.name.charAt(0).toUpperCase() }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <span class="truncate font-mono text-sm font-black text-white">
                                                    {{ m.name }}
                                                </span>
                                                <span v-if="m.is_owner"
                                                    class="rounded-sm bg-amber-500/20 px-1 py-px text-[8px] font-black tracking-widest text-amber-400 uppercase">
                                                    Owner
                                                </span>
                                                <span v-if="m.user_id === viewerId"
                                                    class="rounded-sm bg-sky-500/20 px-1 py-px text-[8px] font-black tracking-widest text-sky-400 uppercase">
                                                    You
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="flex-shrink-0 font-mono text-[10px] text-slate-500">
                                        Joined {{ formatDate(m.joined_at) }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </template>

                    <!-- ═══ Tab: Matchdays ═══ -->
                    <template v-else-if="activeTab === 'activity'">
                        <SubLeagueActivityFeed :activity="activity" />
                    </template>

                    <!-- ═══ Tab: Matchdays ═══ -->
                    <template v-else>
                        <!-- League filter -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">Filter:</span>
                            <button type="button" @click="activeLeague = 'all'" :class="[
                                'cursor-pointer rounded border px-3 py-1 text-[10px] font-black tracking-wider uppercase transition-all',
                                activeLeague === 'all'
                                    ? 'border-amber-500 bg-amber-500 text-black'
                                    : 'border-[#232d42] bg-[#111622] text-slate-400 hover:border-amber-500/50 hover:text-amber-400'
                            ]">
                                All
                            </button>
                            <button v-for="code in leaguesInMatchdays" :key="code" @click="activeLeague = code" :class="[
                                'cursor-pointer rounded border px-3 py-1 text-[10px] font-black tracking-wider uppercase transition-all',
                                activeLeague === code
                                    ? 'border-amber-500 bg-amber-500 text-black'
                                    : 'border-[#232d42] bg-[#111622] text-slate-400 hover:border-amber-500/50 hover:text-amber-400'
                            ]">
                                {{ code }}
                            </button>
                        </div>

                        <div v-if="filteredMatchdays.length === 0"
                            class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a] px-6 py-12 text-center">
                            <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                                No matchdays found
                            </p>
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="md in filteredMatchdays" :key="md.uuid"
                                class="overflow-hidden rounded-lg border transition-colors" :class="md.is_upcoming
                                    ? 'border-amber-500/40 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-transparent'
                                    : 'border-[#232d42] bg-[#161c2a]'">
                                <div class="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[10px] font-black tracking-widest uppercase"
                                                :class="md.is_upcoming ? 'text-amber-400' : 'text-slate-500'">
                                                MD {{ md.matchday_number }} · {{ md.league_code }}
                                            </span>
                                            <span class="text-slate-600">·</span>
                                            <span class="text-[10px] font-black tracking-widest uppercase"
                                                :class="md.is_locked ? 'text-sky-400' : md.is_upcoming ? 'text-amber-400' : 'text-emerald-400'">
                                                {{ md.is_locked ? 'Locked' : md.is_upcoming ? 'Open' : 'Settled' }}
                                            </span>
                                        </div>
                                        <h3 class="mt-1 truncate font-mono text-base font-black text-white">
                                            {{ md.name }}
                                        </h3>
                                        <p class="mt-1 text-[11px] text-slate-500">
                                            <template v-if="md.is_upcoming">
                                                Locks {{ formatDate(md.locks_at) }}
                                                <template v-if="md.is_entered">
                                                    · <span class="text-amber-400">{{ md.picks_submitted }} / {{
                                                        md.picks_required }} picks</span>
                                                </template>
                                                <template v-else>
                                                    · <span class="text-rose-400">not entered</span>
                                                </template>
                                            </template>
                                            <template v-else>
                                                Settled {{ formatDate(md.settled_at) }}
                                            </template>
                                        </p>
                                    </div>

                                    <div class="flex flex-shrink-0 gap-2">
                                        <Link v-if="md.is_upcoming" :href="`/contests/${md.uuid}/picks`"
                                            class="rounded-lg bg-amber-500 px-4 py-2 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400">
                                            {{ md.is_entered ? 'Continue picks →' : 'Make picks →' }}
                                        </Link>
                                        <Link v-else :href="`/contests/${md.uuid}/results`"
                                            class="rounded-lg border border-[#232d42] bg-[#111622] px-4 py-2 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-emerald-500/50 hover:text-emerald-400">
                                            View results →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </Transition>
        </div>

        <!-- Bottom Navigation -->
        <BuyerTabs v-model="activeTab" :tabs="tabs" variant="bottom-bar" />
    </div>
</template>