<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import InfoPopover from '@/components/InfoPopover.vue';

const page = usePage();
const contest = computed(() => page.props.contest);
const standings = computed(() => page.props.standings);

const viewerId = computed(() => page.props.auth?.user?.id ?? null);

const legById = computed(() => {
    const map = {};
    for (const leg of contest.value.legs ?? []) {
        map[leg.id] = leg;
    }
    return map;
});

const viewerPicksByLeg = computed(() => {
    const map = {};
    const entry = page.props.viewer_entry;
    if (entry?.picks) {
        for (const p of entry.picks) {
            map[p.leg_id] = p;
        }
    }
    return map;
});

const formatKickoff = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('en-KE', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

const relativeTime = (iso) => {
    if (!iso) return '';
    const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
    return new Date(iso).toLocaleDateString('en-KE', {
        day: '2-digit',
        month: 'short',
    });
};

const totalLegs = computed(() => contest.value.legs?.length ?? 0);

const playedLegs = computed(
    () =>
        contest.value.legs?.filter((l) => l.status !== 'pending').length ?? 0,
);

const rankDelta = (row) => {
    if (row.rank_previous === null || row.rank === null) return 0;
    if (row.rank_previous === row.rank) return 0;
    return row.rank_previous - row.rank;
};

// ── Viewer detection ────────────────────────────────────────────────────

const isViewer = (userId) =>
    viewerId.value !== null && userId === viewerId.value;

const viewerRow = computed(() => {
    const id = viewerId.value;
    if (id === null) return null;
    const inTop = standings.value.find((r) => r.user_id === id);
    if (inTop) return inTop;
    return page.props.viewer_entry ?? null;
});

const viewerInTop = computed(() => page.props.viewer_in_top === true);

const totalEntries = computed(() => page.props.total_entries ?? 0);

// ── Search ──────────────────────────────────────────────────────────────

const searchQuery = ref('');
const searchResult = ref(null);
const searchLoading = ref(false);
const searchError = ref(null);

const clearSearch = () => {
    searchQuery.value = '';
    searchResult.value = null;
    searchError.value = null;
};

const submitSearch = async () => {
    const code = searchQuery.value.trim();
    if (!code) {
        clearSearch();
        return;
    }

    searchLoading.value = true;
    searchError.value = null;
    searchResult.value = null;

    try {
        const { data } = await axios.get(
            `/contests/${contest.value.uuid}/entries/search`,
            { params: { code } },
        );

        if (data.entry) {
            searchResult.value = data.entry;
        } else {
            searchError.value = 'No player with that code is in this contest.';
        }
    } catch (err) {
        searchError.value =
            err.response?.data?.message ?? 'Search failed. Try again.';
    } finally {
        searchLoading.value = false;
    }
};

// ── Jump to me ──────────────────────────────────────────────────────────

const viewerPinnedRef = ref(null);

const jumpToMe = () => {
    const el = viewerPinnedRef.value;
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

// ── Compare modal ───────────────────────────────────────────────────────

const compareTarget = ref(null);

const canCompare = (row) => {
    if (!viewerRow.value) return false;
    if (!row) return false;
    if (isViewer(row.user_id)) return false;
    return true;
};

const openCompare = (row) => {
    if (!canCompare(row)) return;
    compareTarget.value = row;
};

const closeCompare = () => {
    compareTarget.value = null;
};

const comparisonRows = computed(() => {
    const viewer = viewerRow.value;
    const target = compareTarget.value;
    if (!viewer || !target) return [];

    const viewerPicks = {};
    for (const p of viewer.picks ?? []) viewerPicks[p.leg_id] = p;
    const targetPicks = {};
    for (const p of target.picks ?? []) targetPicks[p.leg_id] = p;

    return (contest.value.legs ?? []).map((leg, i) => ({
        leg,
        index: i + 1,
        viewerPick: viewerPicks[leg.id] ?? null,
        targetPick: targetPicks[leg.id] ?? null,
    }));
});

const comparisonVerdict = (row) => {
    if (row.leg.status === 'void') return 'void';
    if (row.leg.status === 'pending') return 'pending';
    if (!row.viewerPick || !row.targetPick) return 'no_pick';

    const viewerCorrect = row.viewerPick.result === 'correct';
    const targetCorrect = row.targetPick.result === 'correct';
    const sameSelection = row.viewerPick.selection === row.targetPick.selection;

    if (sameSelection) {
        return viewerCorrect ? 'both_right' : 'both_wrong';
    }
    if (viewerCorrect && !targetCorrect) return 'you_got_it';
    if (!viewerCorrect && targetCorrect) return 'they_got_it';
    return 'neither';
};

const verdictLabel = (v) =>
    ({
        both_right: 'Both right',
        both_wrong: 'Both missed',
        you_got_it: 'You got it',
        they_got_it: 'They got it',
        neither: 'Neither right',
        void: 'Void leg',
        pending: 'In play',
        no_pick: 'Not picked',
    })[v] ?? '';

const verdictClass = (v) =>
    ({
        both_right: 'text-emerald-400',
        both_wrong: 'text-rose-400',
        you_got_it: 'text-emerald-400',
        they_got_it: 'text-rose-400',
        neither: 'text-rose-400',
        void: 'text-slate-500',
        pending: 'text-slate-500 italic',
        no_pick: 'text-slate-500 italic',
    })[v] ?? 'text-slate-500';

// ── Leg drill-down modal ────────────────────────────────────────────────

const legPicksCache = ref({});
const legPicksModalLegId = ref(null);
const legPicksLoading = ref(false);
const legPicksLoadingMore = ref(false);
const legPicksError = ref(null);

const legPicksModalLeg = computed(() => {
    if (legPicksModalLegId.value === null) return null;
    return legById.value[legPicksModalLegId.value] ?? null;
});

const legPicksModalData = computed(() => {
    if (legPicksModalLegId.value === null) return null;
    return legPicksCache.value[legPicksModalLegId.value] ?? null;
});

const legPicksModalHasMore = computed(() => {
    const d = legPicksModalData.value;
    if (!d) return false;
    return d.current_page < d.last_page;
});

const openLegPicks = async (leg) => {
    const total = leg.stats?.total_picks ?? 0;
    if (total === 0) return;

    legPicksModalLegId.value = leg.id;
    legPicksError.value = null;

    const cached = legPicksCache.value[leg.id];
    if (cached && cached.frozen) return;

    legPicksLoading.value = true;
    try {
        const { data } = await axios.get(
            `/contests/${contest.value.uuid}/legs/${leg.id}/picks`,
            { params: { page: 1 } },
        );

        legPicksCache.value = {
            ...legPicksCache.value,
            [leg.id]: {
                summary: data.summary,
                picks: data.picks.data,
                current_page: data.picks.current_page,
                last_page: data.picks.last_page,
                frozen: leg.status !== 'pending',
            },
        };
    } catch (err) {
        legPicksError.value =
            err.response?.data?.message ?? 'Could not load leg picks.';
    } finally {
        legPicksLoading.value = false;
    }
};

const loadMoreLegPicks = async () => {
    const legId = legPicksModalLegId.value;
    if (legId === null) return;

    const cached = legPicksCache.value[legId];
    if (!cached) return;

    legPicksLoadingMore.value = true;
    try {
        const { data } = await axios.get(
            `/contests/${contest.value.uuid}/legs/${legId}/picks`,
            { params: { page: cached.current_page + 1 } },
        );

        legPicksCache.value = {
            ...legPicksCache.value,
            [legId]: {
                ...cached,
                picks: [...cached.picks, ...data.picks.data],
                current_page: data.picks.current_page,
                last_page: data.picks.last_page,
            },
        };
    } catch (err) {
        legPicksError.value =
            err.response?.data?.message ?? 'Could not load more picks.';
    } finally {
        legPicksLoadingMore.value = false;
    }
};

const closeLegPicks = () => {
    legPicksModalLegId.value = null;
    legPicksError.value = null;
};

const legPicksModalOpen = computed(() => legPicksModalLegId.value !== null);

// ── Leg difficulty presentation ─────────────────────────────────────────

const legDifficulty = (leg) => {
    const stats = leg.stats ?? { total_picks: 0, correct_picks: 0 };
    const total = stats.total_picks;
    const correct = stats.correct_picks;

    if (leg.status === 'void') {
        return { label: 'Void', class: 'text-slate-500' };
    }

    if (leg.status === 'pending') {
        return {
            label: total > 0 ? `${total} picked` : '—',
            class: 'text-slate-500 italic',
        };
    }

    if (total === 0) {
        return { label: '—', class: 'text-slate-600' };
    }

    if (correct === 0) {
        return { label: 'Nobody got this', class: 'text-rose-400' };
    }

    if (correct === total) {
        return { label: 'All got this', class: 'text-emerald-400' };
    }

    const ratio = correct / total;
    const cls =
        ratio < 1 / 3
            ? 'text-orange-400'
            : ratio < 2 / 3
              ? 'text-slate-400'
              : 'text-emerald-400';

    return { label: `${correct}/${total} got this`, class: cls };
};

const legRowIsClickable = (leg) => (leg.stats?.total_picks ?? 0) > 0;

// ── Modal lifecycle — scroll lock and ESC ───────────────────────────────

const anyModalOpen = computed(
    () => compareTarget.value !== null || legPicksModalOpen.value,
);

watch(anyModalOpen, (val) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = val ? 'hidden' : '';
});

const onKeydown = (e) => {
    if (e.key !== 'Escape') return;
    if (compareTarget.value !== null) {
        closeCompare();
        return;
    }
    if (legPicksModalOpen.value) {
        closeLegPicks();
    }
};

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKeydown);
});

// ── Sticky mobile bar ───────────────────────────────────────────────────

const showStickyBar = ref(false);

const onScroll = () => {
    showStickyBar.value = window.scrollY > 240;
};

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
});

// ── Per-entry pick visibility ───────────────────────────────────────────

const expandedByUser = ref({});

const isExpanded = (userId) => expandedByUser.value[userId] === true;

const toggleExpanded = (userId) => {
    expandedByUser.value = {
        ...expandedByUser.value,
        [userId]: !expandedByUser.value[userId],
    };
};

// ── Rank presentation ───────────────────────────────────────────────────

const rankColorClass = (rank) => {
    if (rank === 1) return 'text-amber-400';
    if (rank === 2) return 'text-slate-300';
    if (rank === 3) return 'text-orange-400';
    return 'text-slate-500';
};

const avatarRingClass = (rank) => {
    if (rank === 1) return 'ring-2 ring-amber-400/70';
    if (rank === 2) return 'ring-2 ring-slate-300/60';
    if (rank === 3) return 'ring-2 ring-orange-400/60';
    return 'ring-1 ring-[#232d42]';
};
</script>

<template>
    <Head :title="`Results — ${contest.name}`" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-4xl px-2 py-12 pb-32 md:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 border-b border-[#232d42] pb-6">
                <div class="flex items-center gap-2">
                    <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                        Contest Results
                    </p>
                    <InfoPopover title="How these results are calculated">
                        <p>
                            Each correct pick earns its odds value. Correct picks
                            are multiplied together to give your total units.
                            Ties are broken by total units, then by who joined
                            earliest.
                        </p>
                        <p>
                            Void legs are excluded from scoring — they neither
                            help nor hurt.
                        </p>
                        <p class="text-slate-400">
                            Tap a leg to see who picked what. Tap a row to
                            expand picks. VS compares slates.
                        </p>
                    </InfoPopover>
                </div>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    {{ contest.name }}
                </h1>
                <p class="mt-3 text-sm text-slate-400">
                    Hosted by {{ contest.host_name }}

                    <template v-if="contest.status === 'settled'">
                        · Final · Settled {{ relativeTime(contest.settled_at) }}
                        <span class="text-slate-600">
                            ({{ formatKickoff(contest.settled_at) }})
                        </span>
                    </template>

                    <template v-else-if="contest.status === 'underway'">
                        ·
                        <span class="inline-flex items-center gap-1.5 text-amber-400">
                            <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-amber-400"></span>
                            Live · {{ playedLegs }} of {{ totalLegs }} fixtures played
                        </span>
                    </template>

                    <template v-if="totalEntries > 0">
                        · {{ totalEntries }} {{ totalEntries === 1 ? 'entry' : 'entries' }}
                    </template>
                </p>
            </div>

            <!-- Legs summary -->
            <div class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#111622]/40">
                <div class="border-b border-[#232d42] bg-[#111a30] px-4 py-2.5">
                    <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                        Legs
                    </span>
                </div>
                <ul class="divide-y divide-[#232d42]/60">
                    <li v-for="(leg, i) in contest.legs" :key="leg.id">
                        <!-- Clickable Link for Finished Fixtures -->
                        <div v-if="leg.fixture.status === 'FT' || leg.fixture.status === 'AET' || leg.fixture.status === 'PEN'"
                             class="group flex w-full items-center gap-3 px-4 py-2.5 transition-colors hover:bg-[#1a2235]">
                            
                            <Link :href="`/fixtures/${leg.fixture.id}/results`" class="flex min-w-0 flex-1 items-center gap-3" :title="`View match results for ${leg.fixture.home_team} vs ${leg.fixture.away_team}`">
                                <div class="min-w-0 flex-1 text-left">
                                    <div class="text-[11px] text-slate-400 transition-colors group-hover:text-sky-400">
                                        <span class="text-slate-600">#{{ i + 1 }}</span>
                                        {{ leg.fixture.home_team }} vs {{ leg.fixture.away_team }}
                                        <span class="text-sky-400/70 ml-1 text-[10px]">↗</span>
                                    </div>
                                    <div class="mt-0.5 text-[10px] font-bold tracking-wider text-slate-500 uppercase">
                                        {{ leg.market }}
                                    </div>
                                </div>
                                <span class="shrink-0 font-mono text-[10px] font-black" :class="legDifficulty(leg).class">
                                    {{ legDifficulty(leg).label }}
                                </span>
                                <span v-if="viewerPicksByLeg[leg.id]" class="shrink-0 font-mono text-sm font-black" :class="{
                                        'text-emerald-400': viewerPicksByLeg[leg.id].result === 'correct',
                                        'text-rose-400': viewerPicksByLeg[leg.id].result === 'incorrect',
                                        'text-slate-500': viewerPicksByLeg[leg.id].result === 'void' || viewerPicksByLeg[leg.id].result === 'pending',
                                    }">
                                    <template v-if="viewerPicksByLeg[leg.id].result === 'correct'">✓</template>
                                    <template v-else-if="viewerPicksByLeg[leg.id].result === 'incorrect'">✗</template>
                                    <template v-else-if="viewerPicksByLeg[leg.id].result === 'void'">V</template>
                                    <template v-else>—</template>
                                </span>
                                <span class="shrink-0 font-mono text-[11px] font-bold" :class="leg.status === 'void' ? 'text-slate-500' : leg.status === 'pending' ? 'text-slate-500 italic' : 'text-emerald-400'">
                                    {{ leg.status === 'void' ? 'VOID' : leg.status === 'pending' ? 'PENDING' : leg.result_selection }}
                                </span>
                            </Link>

                            <button v-if="legRowIsClickable(leg)" type="button" @click="openLegPicks(leg)" class="shrink-0 cursor-pointer rounded border border-[#232d42] bg-[#111622] px-2 py-1 font-mono text-[10px] font-black tracking-wider text-slate-400 uppercase transition-colors hover:border-sky-500/60 hover:text-sky-300" title="See who picked what">
                                Who →
                            </button>
                        </div>

                        <!-- Non-clickable button for Pending Fixtures -->
                        <button v-else type="button" :disabled="!legRowIsClickable(leg)" @click="openLegPicks(leg)"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors"
                            :class="legRowIsClickable(leg) ? 'cursor-pointer hover:bg-[#1a2235]' : 'cursor-default'">
                            <div class="min-w-0 flex-1">
                                <div class="text-[11px] text-slate-400">
                                    <span class="text-slate-600">#{{ i + 1 }}</span>
                                    {{ leg.fixture.home_team }} vs {{ leg.fixture.away_team }}
                                </div>
                                <div class="mt-0.5 text-[10px] font-bold tracking-wider text-slate-500 uppercase">
                                    {{ leg.market }}
                                </div>
                            </div>
                            <span class="shrink-0 font-mono text-[10px] font-black" :class="legDifficulty(leg).class">
                                {{ legDifficulty(leg).label }}
                            </span>
                            <span v-if="viewerPicksByLeg[leg.id]" class="shrink-0 font-mono text-sm font-black" :class="{
                                    'text-emerald-400': viewerPicksByLeg[leg.id].result === 'correct',
                                    'text-rose-400': viewerPicksByLeg[leg.id].result === 'incorrect',
                                    'text-slate-500': viewerPicksByLeg[leg.id].result === 'void' || viewerPicksByLeg[leg.id].result === 'pending',
                                }">
                                <template v-if="viewerPicksByLeg[leg.id].result === 'correct'">✓</template>
                                <template v-else-if="viewerPicksByLeg[leg.id].result === 'incorrect'">✗</template>
                                <template v-else-if="viewerPicksByLeg[leg.id].result === 'void'">V</template>
                                <template v-else>—</template>
                            </span>
                            <span class="shrink-0 font-mono text-[11px] font-bold" :class="leg.status === 'void' ? 'text-slate-500' : leg.status === 'pending' ? 'text-slate-500 italic' : 'text-emerald-400'">
                                {{ leg.status === 'void' ? 'VOID' : leg.status === 'pending' ? 'PENDING' : leg.result_selection }}
                            </span>
                            <span v-if="legRowIsClickable(leg)" class="shrink-0 select-none text-[10px] text-slate-600" aria-hidden="true">
                                ▸
                            </span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Search + jump-to-me -->
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z" />
                        </svg>
                    </div>
                    <input v-model="searchQuery" @keyup.enter="submitSearch" type="text" placeholder="Find a player by code…" class="w-full rounded-lg border border-[#232d42] bg-[#111622] py-2.5 pr-24 pl-10 font-mono text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-colors focus:border-sky-500 focus:outline-none" />
                    <button v-if="searchQuery || searchResult || searchError" type="button" @click="clearSearch" class="absolute inset-y-0 right-16 flex cursor-pointer items-center px-2 text-[10px] font-black tracking-widest text-slate-500 uppercase transition-colors hover:text-slate-300">
                        Clear
                    </button>
                    <button type="button" @click="submitSearch" :disabled="searchLoading || !searchQuery.trim()" class="absolute inset-y-0 right-0 m-1 cursor-pointer rounded bg-[#242f48] px-3 text-[10px] font-black tracking-widest text-slate-200 uppercase transition-colors hover:bg-[#2e3c5c] disabled:cursor-not-allowed disabled:opacity-40">
                        {{ searchLoading ? '…' : 'Find' }}
                    </button>
                </div>

                <button v-if="viewerRow && !viewerInTop" type="button" @click="jumpToMe" class="shrink-0 cursor-pointer rounded-lg border border-sky-500/50 bg-[#16223a] px-3 py-2 text-[10px] font-black tracking-widest text-sky-300 uppercase transition-colors hover:bg-[#1c2c4a]">
                    Jump to me
                </button>
            </div>

            <!-- Search error -->
            <div v-if="searchError" class="mb-4 rounded-lg border border-rose-500/30 bg-rose-500/5 px-3 py-2 text-[11px] text-rose-400">
                {{ searchError }}
            </div>

            <!-- Search result card -->
            <div v-if="searchResult" class="mb-4">
                <div class="mb-2 flex items-center gap-2 text-[9px] font-black tracking-widest text-sky-400 uppercase">
                    <span>Search result</span>
                    <span class="h-px flex-1 bg-sky-500/20"></span>
                </div>

                <div :class="[
                        'overflow-hidden rounded-lg border bg-[#161c2a]',
                        isViewer(searchResult.user_id)
                            ? 'border-sky-500/60 bg-[#16223a]'
                            : 'border-sky-500/40',
                    ]">
                    <div class="flex items-start gap-2 p-3 md:items-center md:p-4">
                        <button type="button" @click="toggleExpanded(searchResult.user_id)" :aria-expanded="isExpanded(searchResult.user_id)" class="-m-2 flex min-w-0 flex-1 cursor-pointer items-start gap-2 rounded p-2 text-left transition-colors hover:bg-[#1a2235] md:items-center md:gap-4">
                            <!-- Rank -->
                            <div class="flex w-10 shrink-0 flex-col items-start md:w-16 md:flex-row md:items-center md:gap-1">
                                <span class="font-mono text-base leading-none font-black md:text-lg" :class="rankColorClass(searchResult.rank)">
                                    {{ searchResult.rank !== null ? `#${searchResult.rank}` : '—' }}
                                </span>
                                <span v-if="rankDelta(searchResult) > 0" class="mt-1 font-mono text-[9px] leading-none font-black text-emerald-400 md:mt-0 md:text-[10px]">
                                    ▲{{ rankDelta(searchResult) }}
                                </span>
                                <span v-else-if="rankDelta(searchResult) < 0" class="mt-1 font-mono text-[9px] leading-none font-black text-rose-400 md:mt-0 md:text-[10px]">
                                    ▼{{ Math.abs(rankDelta(searchResult)) }}
                                </span>
                            </div>

                            <img :src="searchResult.avatar" :alt="searchResult.name" class="h-9 w-9 shrink-0 rounded-full object-cover md:h-10 md:w-10" :class="avatarRingClass(searchResult.rank)" />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate font-mono text-sm font-black" :class="isViewer(searchResult.user_id) ? 'text-sky-300' : 'text-white'">
                                        {{ searchResult.name }}
                                    </span>
                                    <span v-if="isViewer(searchResult.user_id)" class="shrink-0 rounded-sm bg-sky-500/20 px-1 py-px text-[8px] font-black tracking-widest text-sky-300 uppercase">
                                        You
                                    </span>
                                    <span v-if="searchResult.is_verified" class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full bg-sky-500 text-[7px] font-black text-[#070b14]">✓</span>
                                </div>
                                <p class="mt-0.5 truncate font-mono text-[10px] text-slate-500">
                                    {{ searchResult.code }}
                                </p>

                                <!-- Mobile-only inline stats -->
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 font-mono text-[11px] md:hidden">
                                    <span class="font-black text-white">
                                        {{ searchResult.correct }} <span class="font-bold text-slate-500">correct</span>
                                    </span>
                                    <span class="font-black text-amber-400">
                                        {{ searchResult.units.toFixed(2) }}<span class="font-bold text-slate-500">u</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Desktop-only stats grid -->
                            <div class="hidden shrink-0 grid-cols-2 gap-4 text-right md:grid">
                                <div>
                                    <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">Correct</span>
                                    <span class="mt-0.5 block font-mono text-lg font-black text-white">{{ searchResult.correct }}</span>
                                </div>
                                <div>
                                    <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">Units</span>
                                    <span class="mt-0.5 block font-mono text-lg font-black text-amber-400">{{ searchResult.units.toFixed(2) }}</span>
                                </div>
                            </div>
                            
                            <!-- Reputation Badge -->
                            <div v-if="searchResult.reputation_points !== undefined && searchResult.reputation_points !== 0" class="flex flex-col items-center justify-center md:mr-4">
                                <div class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 font-mono text-[9px] font-black" :class="searchResult.reputation_points > 0 ? 'border-amber-500/30 bg-amber-500/10 text-amber-400' : 'border-rose-500/30 bg-rose-500/10 text-rose-400'" :title="`Reputation points: ${searchResult.reputation_points}`">
                                    <span>⭐</span>
                                    <span>{{ searchResult.reputation_points > 0 ? '+' : '' }}{{ searchResult.reputation_points }}</span>
                                </div>
                            </div>

                            <span class="mt-1 shrink-0 self-start text-[10px] select-none text-slate-500 md:mt-0 md:self-center md:text-xs" aria-hidden="true">
                                {{ isExpanded(searchResult.user_id) ? '▾' : '▸' }}
                            </span>
                        </button>

                        <button v-if="canCompare(searchResult)" type="button" @click="openCompare(searchResult)" title="Compare their picks with yours" class="shrink-0 cursor-pointer self-start rounded border border-[#232d42] bg-[#111622] px-2 py-1 font-mono text-[10px] font-black tracking-wider text-slate-400 uppercase transition-colors hover:border-sky-500/60 hover:text-sky-300 md:self-center">
                            VS
                        </button>
                    </div>

                    <div v-if="isExpanded(searchResult.user_id)" class="border-t border-[#232d42]/60 bg-[#0f1422] px-4 py-2">
                        <div v-if="!searchResult.picks || searchResult.picks.length === 0" class="py-3 text-center text-[10px] text-slate-600 italic">
                            No picks submitted.
                        </div>
                        <div v-for="pick in searchResult.picks" :key="pick.leg_id" class="flex items-center justify-between gap-3 border-b border-[#232d42]/30 py-1.5 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[10px] text-slate-500">
                                    {{ legById[pick.leg_id]?.fixture.home_team }} vs {{ legById[pick.leg_id]?.fixture.away_team }}
                                </div>
                                <div class="truncate text-[9px] font-bold tracking-wider text-slate-600 uppercase">
                                    {{ legById[pick.leg_id]?.market ?? '' }}
                                </div>
                            </div>
                            <span class="flex shrink-0 items-center gap-2">
                                <span class="font-mono text-[10px]" :class="pick.result === 'correct' ? 'text-emerald-400' : pick.result === 'void' ? 'text-slate-500' : pick.result === 'pending' ? 'text-slate-500 italic' : 'text-rose-400'">
                                    {{ pick.selection }}
                                </span>
                                <span class="font-mono text-[10px] text-slate-600">@{{ pick.odds.toFixed(2) }}</span>
                                <span :class="['flex h-4 w-4 items-center justify-center rounded-sm font-mono text-[9px] font-black', pick.result === 'correct' ? 'bg-emerald-500 text-[#070b14]' : pick.result === 'void' ? 'bg-slate-500 text-white' : pick.result === 'pending' ? 'bg-slate-700 text-slate-500' : 'bg-rose-500 text-white']">
                                    {{ pick.result === 'correct' ? '✓' : pick.result === 'void' ? 'V' : pick.result === 'pending' ? '·' : '✗' }}
                                </span>
                            </span>
                        </div>

                        <div class="pt-3 pb-1 flex items-center justify-between">
                            <Link :href="`/profile/${searchResult.code}`" class="inline-flex items-center gap-1 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300 hover:underline">
                                View profile →
                            </Link>
                            <span v-if="searchResult.reputation_points !== 0" class="font-mono text-[10px] text-slate-500">
                                Rep: {{ searchResult.reputation_points }} pts
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Standings — top slice -->
            <div class="space-y-3">
                <div v-for="row in standings" :key="row.user_id" :class="[
                        'overflow-hidden rounded-lg border bg-[#161c2a] transition-colors',
                        isViewer(row.user_id) ? 'border-sky-500/60 bg-[#16223a]' : 'border-[#232d42]',
                    ]">
                    <div class="flex items-start gap-2 p-3 md:items-center md:p-4">
                        <button type="button" @click="toggleExpanded(row.user_id)" :aria-expanded="isExpanded(row.user_id)" class="-m-2 flex min-w-0 flex-1 cursor-pointer items-start gap-2 rounded p-2 text-left transition-colors hover:bg-[#1a2235] md:items-center md:gap-4">
                            <!-- Rank -->
                            <div class="flex w-10 shrink-0 flex-col items-start md:w-16 md:flex-row md:items-center md:gap-1">
                                <span class="font-mono text-base leading-none font-black md:text-lg" :class="rankColorClass(row.rank)">
                                    {{ row.rank !== null ? `#${row.rank}` : '—' }}
                                </span>
                                <span v-if="rankDelta(row) > 0" class="mt-1 font-mono text-[9px] leading-none font-black text-emerald-400 md:mt-0 md:text-[10px]" :title="`Up ${rankDelta(row)} since the last fixture`">
                                    ▲{{ rankDelta(row) }}
                                </span>
                                <span v-else-if="rankDelta(row) < 0" class="mt-1 font-mono text-[9px] leading-none font-black text-rose-400 md:mt-0 md:text-[10px]" :title="`Down ${Math.abs(rankDelta(row))} since the last fixture`">
                                    ▼{{ Math.abs(rankDelta(row)) }}
                                </span>
                            </div>

                            <img :src="row.avatar" :alt="row.name" class="h-9 w-9 shrink-0 rounded-full object-cover md:h-10 md:w-10" :class="[avatarRingClass(row.rank), row.correct === 0 && contest.status === 'settled' ? 'opacity-60' : '']" />

                            <div class="min-w-0 flex-1" :class="row.correct === 0 && contest.status === 'settled' ? 'opacity-70' : ''">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate font-mono text-sm font-black" :class="isViewer(row.user_id) ? 'text-sky-300' : 'text-white'">
                                        {{ row.name }}
                                    </span>
                                    <span v-if="isViewer(row.user_id)" class="shrink-0 rounded-sm bg-sky-500/20 px-1 py-px text-[8px] font-black tracking-widest text-sky-300 uppercase">You</span>
                                    <span v-if="row.is_verified" class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full bg-sky-500 text-[7px] font-black text-[#070b14]">✓</span>
                                </div>
                                <p class="mt-0.5 truncate font-mono text-[10px] text-slate-500">{{ row.code }}</p>

                                <!-- Mobile-only inline stats -->
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 font-mono text-[11px] md:hidden">
                                    <span class="font-black text-white">
                                        {{ row.correct }} <span class="font-bold text-slate-500">correct</span>
                                    </span>
                                    <span class="font-black text-amber-400">
                                        {{ row.units.toFixed(2) }}<span class="font-bold text-slate-500">u</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Desktop-only stats grid -->
                            <div class="hidden shrink-0 items-center gap-4 text-right md:flex">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">Correct</span>
                                        <span class="mt-0.5 block font-mono text-lg font-black text-white">{{ row.correct }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">Units</span>
                                        <span class="mt-0.5 block font-mono text-lg font-black text-amber-400">{{ row.units.toFixed(2) }}</span>
                                    </div>
                                </div>

                                <!-- Reputation badge -->
                                <div v-if="row.reputation_points !== undefined && row.reputation_points !== 0" class="flex flex-col items-center justify-center">
                                    <div class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 font-mono text-[9px] font-black" :class="row.reputation_points > 0 ? 'border-amber-500/30 bg-amber-500/10 text-amber-400' : 'border-rose-500/30 bg-rose-500/10 text-rose-400'" :title="`Reputation points: ${row.reputation_points}`">
                                        <span>⭐</span>
                                        <span>{{ row.reputation_points > 0 ? '+' : '' }}{{ row.reputation_points }}</span>
                                        <span v-if="row.global_rank !== null" class="opacity-60">#{{ row.global_rank }}</span>
                                    </div>
                                </div>
                            </div>

                            <span class="mt-1 shrink-0 self-start text-[10px] select-none text-slate-500 md:mt-0 md:self-center md:text-xs" aria-hidden="true">
                                {{ isExpanded(row.user_id) ? '▾' : '▸' }}
                            </span>
                        </button>

                        <button v-if="canCompare(row)" type="button" @click="openCompare(row)" title="Compare their picks with yours" class="shrink-0 cursor-pointer self-start rounded border border-[#232d42] bg-[#111622] px-2 py-1 font-mono text-[10px] font-black tracking-wider text-slate-400 uppercase transition-colors hover:border-sky-500/60 hover:text-sky-300 md:self-center">
                            VS
                        </button>
                    </div>

                    <div v-if="isExpanded(row.user_id)" class="border-t border-[#232d42]/60 bg-[#0f1422] px-4 py-2">
                        <div v-if="!row.picks || row.picks.length === 0" class="py-3 text-center text-[10px] text-slate-600 italic">
                            No picks submitted.
                        </div>
                        <div v-for="pick in row.picks" :key="pick.leg_id" class="flex items-center justify-between gap-3 border-b border-[#232d42]/30 py-1.5 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[10px] text-slate-500">
                                    {{ legById[pick.leg_id]?.fixture.home_team }} vs {{ legById[pick.leg_id]?.fixture.away_team }}
                                </div>
                                <div class="truncate text-[9px] font-bold tracking-wider text-slate-600 uppercase">
                                    {{ legById[pick.leg_id]?.market ?? '' }}
                                </div>
                            </div>
                            <span class="flex shrink-0 items-center gap-2">
                                <span class="font-mono text-[10px]" :class="pick.result === 'correct' ? 'text-emerald-400' : pick.result === 'void' ? 'text-slate-500' : pick.result === 'pending' ? 'text-slate-500 italic' : 'text-rose-400'">
                                    {{ pick.selection }}
                                </span>
                                <span class="font-mono text-[10px] text-slate-600">@{{ pick.odds.toFixed(2) }}</span>
                                <span :class="['flex h-4 w-4 items-center justify-center rounded-sm font-mono text-[9px] font-black', pick.result === 'correct' ? 'bg-emerald-500 text-[#070b14]' : pick.result === 'void' ? 'bg-slate-500 text-white' : pick.result === 'pending' ? 'bg-slate-700 text-slate-500' : 'bg-rose-500 text-white']">
                                    {{ pick.result === 'correct' ? '✓' : pick.result === 'void' ? 'V' : pick.result === 'pending' ? '·' : '✗' }}
                                </span>
                            </span>
                        </div>

                        <div class="pt-3 pb-1 flex items-center justify-between">
                            <Link :href="`/profile/${row.code}`" class="inline-flex items-center gap-1 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300 hover:underline">
                                View profile →
                            </Link>
                            <span v-if="row.reputation_points !== 0" class="font-mono text-[10px] text-slate-500">
                                Rep: {{ row.reputation_points }} pts
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top-10 cutoff indicator -->
            <div v-if="totalEntries > standings.length" class="mt-4 rounded-lg border border-dashed border-[#232d42] px-4 py-3 text-center text-[10px] text-slate-500">
                Showing top {{ standings.length }} of {{ totalEntries }} entries. Use the search above to find any player.
            </div>

            <!-- Pinned viewer row (when outside top slice) -->
            <div v-if="viewerRow && !viewerInTop" ref="viewerPinnedRef" class="mt-4">
                <div class="mb-2 flex items-center gap-2 text-[9px] font-black tracking-widest text-sky-400 uppercase">
                    <span>You · #{{ viewerRow.rank ?? '—' }}</span>
                    <span class="h-px flex-1 bg-sky-500/20"></span>
                </div>

                <div class="overflow-hidden rounded-lg border border-sky-500/60 bg-[#16223a]">
                    <div class="flex items-center gap-2 p-3 md:p-4">
                        <button type="button" @click="toggleExpanded(viewerRow.user_id)" :aria-expanded="isExpanded(viewerRow.user_id)" class="-m-2 flex min-w-0 flex-1 cursor-pointer items-start gap-2 rounded p-2 text-left transition-colors hover:bg-[#1c2c4a] md:items-center md:gap-4">
                            <!-- Rank -->
                            <div class="flex w-10 shrink-0 flex-col items-start md:w-16 md:flex-row md:items-center md:gap-1">
                                <span class="font-mono text-base leading-none font-black md:text-lg" :class="rankColorClass(viewerRow.rank)">
                                    {{ viewerRow.rank !== null ? `#${viewerRow.rank}` : '—' }}
                                </span>
                                <span v-if="rankDelta(viewerRow) > 0" class="mt-1 font-mono text-[9px] leading-none font-black text-emerald-400 md:mt-0 md:text-[10px]">
                                    ▲{{ rankDelta(viewerRow) }}
                                </span>
                                <span v-else-if="rankDelta(viewerRow) < 0" class="mt-1 font-mono text-[9px] leading-none font-black text-rose-400 md:mt-0 md:text-[10px]">
                                    ▼{{ Math.abs(rankDelta(viewerRow)) }}
                                </span>
                            </div>

                            <img :src="viewerRow.avatar" :alt="viewerRow.name" class="h-9 w-9 shrink-0 rounded-full object-cover md:h-10 md:w-10" :class="avatarRingClass(viewerRow.rank)" />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate font-mono text-sm font-black text-sky-300">
                                        {{ viewerRow.name }}
                                    </span>
                                    <span class="shrink-0 rounded-sm bg-sky-500/20 px-1 py-px text-[8px] font-black tracking-widest text-sky-300 uppercase">
                                        You
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate font-mono text-[10px] text-slate-500">
                                    {{ viewerRow.code }}
                                </p>

                                <!-- Mobile-only inline stats -->
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 font-mono text-[11px] md:hidden">
                                    <span class="font-black text-white">
                                        {{ viewerRow.correct }} <span class="font-bold text-slate-500">correct</span>
                                    </span>
                                    <span class="font-black text-amber-400">
                                        {{ viewerRow.units.toFixed(2) }}<span class="font-bold text-slate-500">u</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Desktop-only stats grid -->
                            <div class="hidden shrink-0 items-center gap-4 text-right md:flex">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">Correct</span>
                                        <span class="mt-0.5 block font-mono text-lg font-black text-white">{{ viewerRow.correct }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">Units</span>
                                        <span class="mt-0.5 block font-mono text-lg font-black text-amber-400">{{ viewerRow.units.toFixed(2) }}</span>
                                    </div>
                                </div>

                                <!-- Reputation badge -->
                                <div v-if="viewerRow.reputation_points !== undefined && viewerRow.reputation_points !== 0" class="flex flex-col items-center justify-center">
                                    <div class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 font-mono text-[9px] font-black" :class="viewerRow.reputation_points > 0 ? 'border-amber-500/30 bg-amber-500/10 text-amber-400' : 'border-rose-500/30 bg-rose-500/10 text-rose-400'" :title="`Reputation points: ${viewerRow.reputation_points}`">
                                        <span>⭐</span>
                                        <span>{{ viewerRow.reputation_points > 0 ? '+' : '' }}{{ viewerRow.reputation_points }}</span>
                                        <span v-if="viewerRow.global_rank !== null" class="opacity-60">#{{ viewerRow.global_rank }}</span>
                                    </div>
                                </div>
                            </div>

                            <span class="mt-1 shrink-0 self-start text-[10px] select-none text-slate-500 md:mt-0 md:self-center md:text-xs" aria-hidden="true">
                                {{ isExpanded(viewerRow.user_id) ? '▾' : '▸' }}
                            </span>
                        </button>
                    </div>

                    <div v-if="isExpanded(viewerRow.user_id)" class="border-t border-[#232d42]/60 bg-[#0f1422] px-4 py-2">
                        <div v-for="pick in viewerRow.picks" :key="pick.leg_id" class="flex items-center justify-between gap-3 border-b border-[#232d42]/30 py-1.5 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[10px] text-slate-500">
                                    {{ legById[pick.leg_id]?.fixture.home_team }} vs {{ legById[pick.leg_id]?.fixture.away_team }}
                                </div>
                                <div class="truncate text-[9px] font-bold tracking-wider text-slate-600 uppercase">
                                    {{ legById[pick.leg_id]?.market ?? '' }}
                                </div>
                            </div>
                            <span class="flex shrink-0 items-center gap-2">
                                <span class="font-mono text-[10px]" :class="pick.result === 'correct' ? 'text-emerald-400' : pick.result === 'void' ? 'text-slate-500' : pick.result === 'pending' ? 'text-slate-500 italic' : 'text-rose-400'">
                                    {{ pick.selection }}
                                </span>
                                <span class="font-mono text-[10px] text-slate-600">@{{ pick.odds.toFixed(2) }}</span>
                                <span :class="['flex h-4 w-4 items-center justify-center rounded-sm font-mono text-[9px] font-black', pick.result === 'correct' ? 'bg-emerald-500 text-[#070b14]' : pick.result === 'void' ? 'bg-slate-500 text-white' : pick.result === 'pending' ? 'bg-slate-700 text-slate-500' : 'bg-rose-500 text-white']">
                                    {{ pick.result === 'correct' ? '✓' : pick.result === 'void' ? 'V' : pick.result === 'pending' ? '·' : '✗' }}
                                </span>
                            </span>
                        </div>

                        <div class="pt-3 pb-1 flex items-center justify-between">
                            <Link :href="`/profile/${viewerRow.code}`" class="inline-flex items-center gap-1 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300 hover:underline">
                                View profile →
                            </Link>
                            <span v-if="viewerRow.reputation_points !== 0" class="font-mono text-[10px] text-slate-500">
                                Rep: {{ viewerRow.reputation_points }} pts
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center">
                <Link :href="`/contests/join/${contest.uuid}`" class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">
                    ← Back to contest
                </Link>
            </div>
        </div>

        <!-- Sticky mobile bar -->
        <Transition enter-active-class="transition-transform duration-200 ease-out" enter-from-class="translate-y-full" enter-to-class="translate-y-0" leave-active-class="transition-transform duration-150 ease-in" leave-from-class="translate-y-0" leave-to-class="translate-y-full">
            <div v-if="viewerRow && showStickyBar && compareTarget === null && !legPicksModalOpen" class="fixed inset-x-0 bottom-0 z-40 border-t border-[#232d42] bg-[#0f1422]/95 px-4 py-2.5 backdrop-blur md:hidden">
                <div class="mx-auto flex max-w-4xl items-center gap-3">
                    <span class="font-mono text-lg font-black" :class="rankColorClass(viewerRow.rank)">
                        {{ viewerRow.rank !== null ? `#${viewerRow.rank}` : '—' }}
                    </span>
                    <span v-if="rankDelta(viewerRow) > 0" class="font-mono text-[10px] font-black text-emerald-400">▲{{ rankDelta(viewerRow) }}</span>
                    <span v-else-if="rankDelta(viewerRow) < 0" class="font-mono text-[10px] font-black text-rose-400">▼{{ Math.abs(rankDelta(viewerRow)) }}</span>

                    <span class="min-w-0 flex-1 truncate font-mono text-xs font-black text-sky-300">
                        {{ viewerRow.name }}
                    </span>

                    <span class="font-mono text-xs font-black text-white">
                        {{ viewerRow.correct }} <span class="text-slate-500">correct</span>
                    </span>
                    <span class="font-mono text-xs font-black text-amber-400">
                        {{ viewerRow.units.toFixed(2) }} <span class="text-slate-500">u</span>
                    </span>
                </div>
            </div>
        </Transition>

        <!-- Compare modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-150 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="compareTarget && viewerRow" class="fixed inset-0 z-[60] flex items-end justify-center bg-black/70 backdrop-blur-sm md:items-center md:p-6" @click.self="closeCompare">
                    <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-[#232d42] bg-[#0f1422] md:rounded-2xl">
                        <div class="flex items-center justify-between gap-4 border-b border-[#232d42] bg-[#121824] px-4 py-3 md:px-5 md:py-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-black tracking-widest text-amber-400 uppercase">Comparison</p>
                                <div class="mt-1 flex items-baseline gap-2 truncate">
                                    <span class="truncate font-mono text-sm font-black text-sky-300">You</span>
                                    <span class="text-xs text-slate-500">vs</span>
                                    <span class="truncate font-mono text-sm font-black text-white">{{ compareTarget.name }}</span>
                                </div>
                                <div class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1 font-mono text-[10px]">
                                    <span class="text-sky-300">
                                        You: {{ viewerRow.correct }} correct
                                        <span class="text-slate-500">·</span>
                                        {{ viewerRow.units.toFixed(2) }}u
                                    </span>
                                    <span class="text-slate-500">|</span>
                                    <span class="text-slate-300">
                                        {{ compareTarget.name }}: {{ compareTarget.correct }} correct
                                        <span class="text-slate-500">·</span>
                                        {{ compareTarget.units.toFixed(2) }}u
                                    </span>
                                </div>
                            </div>
                            <button type="button" @click="closeCompare" aria-label="Close" class="shrink-0 cursor-pointer rounded border border-[#232d42] px-2 py-1 font-mono text-xs font-black text-slate-400 transition-colors hover:border-rose-500/60 hover:text-rose-400">✕</button>
                        </div>

                        <div class="flex-1 overflow-y-auto px-4 py-3 md:px-5">
                            <div v-for="row in comparisonRows" :key="row.leg.id" class="border-b border-[#232d42]/50 py-3 last:border-b-0">
                                <div class="min-w-0">
                                    <div class="truncate text-[11px] font-bold text-slate-300">
                                        <span class="text-slate-600">#{{ row.index }}</span>
                                        {{ row.leg.fixture.home_team }} vs {{ row.leg.fixture.away_team }}
                                    </div>
                                    <div class="mt-0.5 text-[9px] font-bold tracking-wider text-slate-500 uppercase">{{ row.leg.market }}</div>
                                </div>

                                <div class="mt-2 grid grid-cols-2 gap-2 text-[11px]">
                                    <div class="rounded border border-sky-500/30 bg-[#16223a] px-2.5 py-2">
                                        <div class="text-[9px] font-black tracking-widest text-sky-300 uppercase">You</div>
                                        <div v-if="row.viewerPick" class="mt-0.5 flex items-center gap-1.5">
                                            <span class="font-mono font-black" :class="row.viewerPick.result === 'correct' ? 'text-emerald-400' : row.viewerPick.result === 'void' ? 'text-slate-500' : row.viewerPick.result === 'pending' ? 'text-slate-500 italic' : 'text-rose-400'">
                                                {{ row.viewerPick.selection }}
                                            </span>
                                            <span class="font-mono text-[9px] text-slate-600">@{{ row.viewerPick.odds.toFixed(2) }}</span>
                                        </div>
                                        <div v-else class="mt-0.5 text-[10px] text-slate-600 italic">Not picked</div>
                                    </div>

                                    <div class="rounded border border-[#232d42] bg-[#14192a] px-2.5 py-2">
                                        <div class="truncate text-[9px] font-black tracking-widest text-slate-400 uppercase">{{ compareTarget.name }}</div>
                                        <div v-if="row.targetPick" class="mt-0.5 flex items-center gap-1.5">
                                            <span class="font-mono font-black" :class="row.targetPick.result === 'correct' ? 'text-emerald-400' : row.targetPick.result === 'void' ? 'text-slate-500' : row.targetPick.result === 'pending' ? 'text-slate-500 italic' : 'text-rose-400'">
                                                {{ row.targetPick.selection }}
                                            </span>
                                            <span class="font-mono text-[9px] text-slate-600">@{{ row.targetPick.odds.toFixed(2) }}</span>
                                        </div>
                                        <div v-else class="mt-0.5 text-[10px] text-slate-600 italic">Not picked</div>
                                    </div>
                                </div>
                                <div class="mt-1.5 text-[10px] font-black">
                                    <span :class="verdictClass(comparisonVerdict(row))">{{ verdictLabel(comparisonVerdict(row)) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Leg picks drill-down modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-150 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="legPicksModalLeg && legPicksModalOpen" class="fixed inset-0 z-[60] flex items-end justify-center bg-black/70 backdrop-blur-sm md:items-center md:p-6" @click.self="closeLegPicks">
                    <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-[#232d42] bg-[#0f1422] md:rounded-2xl">
                        <div class="border-b border-[#232d42] bg-[#121824] px-4 py-3 md:px-5 md:py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[9px] font-black tracking-widest text-sky-400 uppercase">Who picked what</p>
                                    <div class="mt-1 truncate text-sm font-black text-white">
                                        {{ legPicksModalLeg.fixture.home_team }} vs {{ legPicksModalLeg.fixture.away_team }}
                                    </div>
                                    <div class="mt-0.5 truncate text-[10px] font-bold tracking-wider text-slate-500 uppercase">{{ legPicksModalLeg.market }}</div>
                                    <div class="mt-2 flex items-baseline gap-2">
                                        <span class="font-mono text-[11px] font-bold" :class="legPicksModalLeg.status === 'void' ? 'text-slate-500' : legPicksModalLeg.status === 'pending' ? 'text-slate-500 italic' : 'text-emerald-400'">
                                            {{ legPicksModalLeg.status === 'void' ? 'VOID' : legPicksModalLeg.status === 'pending' ? 'PENDING' : `Result: ${legPicksModalLeg.result_selection}` }}
                                        </span>
                                        <span class="font-mono text-[10px] font-black" :class="legDifficulty(legPicksModalLeg).class">
                                            {{ legDifficulty(legPicksModalLeg).label }}
                                        </span>
                                    </div>
                                </div>
                                <button type="button" @click="closeLegPicks" aria-label="Close" class="shrink-0 cursor-pointer rounded border border-[#232d42] px-2 py-1 font-mono text-xs font-black text-slate-400 transition-colors hover:border-rose-500/60 hover:text-rose-400">✕</button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto">
                            <div v-if="legPicksLoading" class="px-4 py-10 text-center text-[11px] text-slate-500">Loading…</div>
                            <div v-else-if="legPicksError" class="m-4 rounded-lg border border-rose-500/30 bg-rose-500/5 px-3 py-2 text-[11px] text-rose-400">{{ legPicksError }}</div>
                            <template v-else-if="legPicksModalData">
                                <div class="border-b border-[#232d42]/60 bg-[#0f1422] px-4 py-3 md:px-5">
                                    <div class="mb-2 text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                        Crowd split · {{ legPicksModalData.summary.total_picks }} {{ legPicksModalData.summary.total_picks === 1 ? 'pick' : 'picks' }}
                                    </div>
                                    <div class="flex flex-wrap gap-1.5">
                                        <div v-for="b in legPicksModalData.summary.breakdown" :key="b.selection" class="flex items-center gap-1.5 rounded-full border px-2.5 py-1 font-mono text-[10px] font-black" :class="b.is_correct ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400' : 'border-[#232d42] bg-[#14192a] text-slate-400'">
                                            <span>{{ b.selection }}</span>
                                            <span class="text-slate-500">×{{ b.count }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div v-if="legPicksModalData.picks.length === 0" class="px-4 py-6 text-center text-[11px] text-slate-500 italic">No picks submitted on this leg yet.</div>
                                    <div v-for="p in legPicksModalData.picks" :key="p.user_id" class="flex items-center gap-3 border-b border-[#232d42]/30 px-4 py-2.5 last:border-b-0 md:px-5">
                                        <span class="w-10 shrink-0 font-mono text-[11px] font-black" :class="rankColorClass(p.rank)">{{ p.rank !== null ? `#${p.rank}` : '—' }}</span>
                                        <img :src="p.avatar" :alt="p.name" class="h-7 w-7 shrink-0 rounded-full object-cover" :class="avatarRingClass(p.rank)" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="truncate font-mono text-[11px] font-black" :class="isViewer(p.user_id) ? 'text-sky-300' : 'text-white'">{{ p.name }}</span>
                                                <span v-if="isViewer(p.user_id)" class="rounded-sm bg-sky-500/20 px-1 py-px text-[7px] font-black tracking-widest text-sky-300 uppercase">You</span>
                                                <span v-if="p.is_verified" class="flex h-3 w-3 items-center justify-center rounded-full bg-sky-500 text-[6px] font-black text-[#070b14]">✓</span>
                                            </div>
                                            <p class="mt-0.5 font-mono text-[9px] text-slate-600">{{ p.code }}</p>
                                        </div>
                                        <span class="flex shrink-0 items-center gap-2">
                                            <span class="font-mono text-[10px]" :class="p.result === 'correct' ? 'text-emerald-400' : p.result === 'void' ? 'text-slate-500' : p.result === 'pending' ? 'text-slate-500 italic' : 'text-rose-400'">{{ p.selection }}</span>
                                            <span class="font-mono text-[10px] text-slate-600">@{{ p.odds.toFixed(2) }}</span>
                                            <span :class="['flex h-4 w-4 items-center justify-center rounded-sm font-mono text-[9px] font-black', p.result === 'correct' ? 'bg-emerald-500 text-[#070b14]' : p.result === 'void' ? 'bg-slate-500 text-white' : p.result === 'pending' ? 'bg-slate-700 text-slate-500' : 'bg-rose-500 text-white']">{{ p.result === 'correct' ? '✓' : p.result === 'void' ? 'V' : p.result === 'pending' ? '·' : '✗' }}</span>
                                        </span>
                                    </div>

                                    <div v-if="legPicksModalHasMore" class="px-4 py-3 text-center md:px-5">
                                        <button type="button" @click="loadMoreLegPicks" :disabled="legPicksLoadingMore" class="cursor-pointer rounded border border-[#232d42] bg-[#111622] px-4 py-2 font-mono text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/60 hover:text-sky-300 disabled:cursor-not-allowed disabled:opacity-40">
                                            {{ legPicksLoadingMore ? '…' : `Load more (${legPicksModalData.picks.length} of ${legPicksModalData.summary.total_picks})` }}
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>