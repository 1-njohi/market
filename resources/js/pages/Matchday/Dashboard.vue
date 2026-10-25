<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';
import SettlementCard from '@/components/Matchday/SettlementCard.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import MatchdaySummaryPanel from '@/components/Matchday/MatchdaySummaryPanel.vue';
import SubLeagueModal from '@/components/Matchday/SubLeagueModal.vue';

const page = usePage();

const liveRows = ref([]);

const user = computed(() => page.props.user);
const notifications = computed(() => page.props.notifications);
const season = computed(() => page.props.season);
const seasons = computed(() => page.props.seasons ?? []);
const leagueOptions = computed(() => page.props.league_options ?? []);
const settlements = computed(() => page.props.unacknowledged_settlements ?? []);
const subLeagues = computed(() => page.props.sub_leagues ?? []);
const summary = computed(() => page.props.summary ?? null);

const hasSubLeagues = computed(() => subLeagues.value.length > 0);

// ────────────── Modal State ──────────────
const modalOpen = ref(false);
const modalMode = ref<'join' | 'create'>('join');

const openJoin = () => {
    modalMode.value = 'join';
    modalOpen.value = true;
};

const openCreate = () => {
    modalMode.value = 'create';
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
};

// ────────────── Countdown ticker ──────────────
// Every 30 seconds, re-evaluate lock countdown. Cheap: no HTTP, just a
// re-render of the computed strings.
const tick = ref(0);
let ticker: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    ticker = setInterval(() => tick.value++, 30_000);
});

onBeforeUnmount(() => {
    if (ticker) clearInterval(ticker);
});

// ────────────── Matchday Logic ──────────────
const openMatchdays = computed(() => page.props.open_matchdays ?? []);

const heroState = computed<'no-sub-league' | 'picks-open' | 'between'>(() => {
    if (!hasSubLeagues.value) return 'no-sub-league';
    if (openMatchdays.value.length === 0) return 'between';
    return 'picks-open';
});

const cardState = (md: any) => {
    if (md.is_locked) return 'live';
    if (md.picks_submitted === md.picks_required) return 'complete';
    if (md.picks_submitted > 0) return 'partial';
    return 'open';
};

const activeLiveMatchdays = computed(() =>
    liveRows.value.filter(r => r.is_locked || r.legs_resolved > 0)
);

const pendingOpenMatchdays = computed(() =>
    openMatchdays.value.filter(md => {
        const live = liveRows.value.find(l => l.uuid === md.uuid);
        return !live || (!live.is_locked && live.legs_resolved === 0);
    })
);

const cardCountdown = (md: any) => {
    void tick.value;
    if (!md?.locks_at) return '';
    if (md.is_locked) return 'locked';
    const ms = new Date(md.locks_at).getTime() - Date.now();
    if (ms <= 0) return 'locked';
    const mins = Math.floor(ms / 60_000);
    const hours = Math.floor(mins / 60);
    const days = Math.floor(hours / 24);
    if (days >= 1) return `${days}d ${hours % 24}h`;
    if (hours >= 1) return `${hours}h ${mins % 60}m`;
    return `${mins}m`;
};

const cardIsUrgent = (md: any) => {
    void tick.value;
    if (md.is_locked) return false;
    const ms = new Date(md.locks_at).getTime() - Date.now();
    return ms > 0 && ms < 60 * 60 * 1000;
};

const leagueLabel = (code: string) => {
    const opt = leagueOptions.value.find((o: any) => o.code === code);
    return opt?.label ?? code;
};

// ────────────── Copy-to-clipboard ──────────────
const lastCopied = ref<string | null>(null);
let copyTimer: ReturnType<typeof setTimeout> | null = null;

const copyInvite = (code: string) => {
    if (!navigator?.clipboard) return;
    navigator.clipboard.writeText(code).then(() => {
        lastCopied.value = code;
        if (copyTimer) clearTimeout(copyTimer);
        copyTimer = setTimeout(() => {
            lastCopied.value = null;
            copyTimer = null;
        }, 2000);
    });
};

onBeforeUnmount(() => {
    if (copyTimer) clearTimeout(copyTimer);
});

// ────────────── Helpers ──────────────
const formatKickoff = (iso?: string | null) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('en-KE', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};
</script>

<template>

    <Head title="Matchday League" />

    <div
        class="flex min-h-screen flex-col bg-[#070b14] px-2 pt-6 pb-24 font-sans text-slate-200 selection:bg-amber-500/30 selection:text-white lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <div class="flex w-full items-start justify-between gap-3 border-b border-gray-800/60 pb-6">
                <div class="flex min-w-0 items-center gap-3 md:gap-4">
                    <div class="relative flex-shrink-0">
                        <img :src="user.avatar" :alt="user.name"
                            class="h-12 w-12 rounded border border-amber-500/30 bg-[#111622] md:h-14 md:w-14" />
                        <span v-if="user.is_verified"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[8px] font-black text-[#070b14]">✓</span>
                    </div>
                    <div class="min-w-0">
                        <div
                            class="flex items-center gap-2 text-[10px] font-black tracking-widest text-amber-400 uppercase">
                            <span>Matchday League</span>
                            <span v-if="season" class="text-slate-600">· {{ season }}</span>
                        </div>
                        <h1
                            class="mt-0.5 truncate font-mono text-lg font-black tracking-tight text-white uppercase md:text-xl">
                            {{ user.name }}
                            <StreakFlame v-if="summary" :streak="summary.current_streak" />
                        </h1>
                        <p class="truncate text-[10px] font-medium text-slate-400">
                            Code: {{ user.code }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-shrink-0 items-center gap-2">
                    <DashboardSwitcher active="matchday" />
                    <!-- <NotificationBell :initial-unread-count="notifications.unread_count" /> -->
                </div>
            </div>

            <!-- ═══════════════ SETTLEMENT CARDS ═══════════════ -->
            <div v-if="settlements.length > 0" class="space-y-3">
                <SettlementCard v-for="s in settlements" :key="s.entry_id" :settlement="s" />
            </div>

            <!-- ═══════════════ HERO ═══════════════ -->

            <!-- ─── State: no sub-league ─── -->
            <div v-if="heroState === 'no-sub-league'"
                class="overflow-hidden rounded-lg border border-amber-500/30 bg-gradient-to-br from-amber-500/10 via-transparent to-transparent">
                <div class="p-6 md:p-8">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                            Join the matchday league
                        </span>
                        <InfoPopover title="How matchday works">
                            <p>
                                Each matchday, every player picks one selection
                                per fixture — usually 10 across a weekend.
                                Correct picks score points; the season
                                leaderboard ranks everyone by total points.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">
                                    Sub-leagues:
                                </span>
                                playing requires joining a private pool of
                                friends. Sub-leagues exist only for a season
                                — you and your friends start fresh each year.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">
                                    Free to play.
                                </span>
                                No entry fee. Bragging rights only.
                            </p>
                            <p class="text-slate-400">
                                Once you're in a sub-league, you're
                                automatically entered into every matchday
                                that hasn't locked yet.
                            </p>
                        </InfoPopover>
                    </div>
                    <h2 class="mt-3 text-2xl font-black tracking-wide text-white uppercase md:text-3xl">
                        Play with your people
                    </h2>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-400">
                        Matchday is a weekly picks competition. Join a
                        sub-league with friends, submit your picks before
                        each weekend's lock, and track a season-long
                        leaderboard.
                    </p>

                    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                        <button type="button" @click="openJoin"
                            class="cursor-pointer rounded-lg bg-amber-500 px-6 py-3 text-center text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400">
                            Join with invite code
                        </button>
                        <button type="button" @click="openCreate"
                            class="cursor-pointer rounded-lg border border-[#232d42] bg-[#111622] px-6 py-3 text-center text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-amber-500/50 hover:text-amber-400">
                            Create a sub-league
                        </button>
                    </div>
                </div>

                <!-- Previews of open matchdays (if any are open) -->
                <div v-if="openMatchdays.length > 0" class="border-t border-amber-500/20 bg-[#0f1422]/50">
                    <div v-for="md in openMatchdays" :key="md.uuid"
                        class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 md:px-8 border-b border-gray-800/40 last:border-0">
                        <div class="min-w-0">
                            <p class="text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                {{ leagueLabel(md.league_code) }} · MD {{ md.matchday_number }}
                            </p>
                            <p class="mt-0.5 truncate font-mono text-[11px] font-bold text-slate-300">
                                {{ md.name }}
                                <span class="text-slate-600">
                                    · {{ md.picks_required }} fixtures
                                </span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-[11px] font-black"
                                :class="cardIsUrgent(md) ? 'text-amber-400' : 'text-slate-400'">
                                Locks in {{ cardCountdown(md) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live strip — appears when any entered matchday is underway -->
            <div v-else-if="liveRows.length > 0" class="space-y-3">
                <h3 class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    Live now
                </h3>
                <MatchdayLiveStrip v-for="row in liveRows" :key="row.uuid" :row="row" />
            </div>
            <!-- ─── State: one or more matchdays open ─── -->
            <div v-else-if="heroState === 'picks-open'" class="space-y-3">
                <div v-for="md in openMatchdays" :key="md.uuid"
                    class="overflow-hidden rounded-lg border transition-colors" :class="cardState(md) === 'live'
                        ? 'border-sky-500/40 bg-gradient-to-br from-sky-500/10 via-transparent to-transparent'
                        : 'border-amber-500/40 bg-gradient-to-br from-amber-500/15 via-amber-500/5 to-transparent'">
                    <div class="flex flex-col gap-5 p-6 md:flex-row md:items-center md:justify-between md:p-8">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                                    {{ leagueLabel(md.league_code) }} · MD {{ md.matchday_number }}
                                </span>
                                <span class="text-slate-600">·</span>

                                <template v-if="md.is_locked">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                        <span
                                            class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-sky-400"></span>
                                        Live
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="text-[10px] font-black tracking-widest uppercase"
                                        :class="cardIsUrgent(md) ? 'text-rose-400' : 'text-slate-400'">
                                        Locks in {{ cardCountdown(md) }}
                                    </span>
                                    <span v-if="cardIsUrgent(md)"
                                        class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400"></span>
                                </template>
                            </div>

                            <h2 class="mt-2 text-2xl font-black tracking-wide text-white uppercase md:text-3xl">
                                {{ md.name }}
                            </h2>

                            <p v-if="!md.is_locked" class="mt-2 text-sm leading-relaxed text-slate-400">
                                <span class="font-mono font-black text-slate-200">
                                    {{ md.picks_submitted }} / {{ md.picks_required }}
                                </span>
                                picks submitted
                                <span v-if="md.picks_submitted === md.picks_required" class="text-emerald-400">· all
                                    done</span>
                                <span v-else-if="md.picks_submitted > 0" class="text-amber-400">
                                    · {{ md.picks_required - md.picks_submitted }} remaining
                                </span>
                            </p>
                            <p v-else class="mt-2 text-sm leading-relaxed text-slate-400">
                                Picks are locked. Fixtures are playing now — leaderboards update as each one settles.
                            </p>

                            <p class="mt-1 text-[11px] text-slate-500">
                                Lock at {{ formatKickoff(md.locks_at) }}
                            </p>
                        </div>

                        <div class="flex-shrink-0">
                            <Link :href="`/contests/${md.uuid}/picks`"
                                :class="md.is_locked
                                    ? 'inline-block cursor-pointer rounded-lg border border-sky-500/50 bg-[#111622] px-6 py-3 text-center text-[11px] font-black tracking-widest text-sky-300 uppercase transition-colors hover:bg-[#16223a]'
                                    : 'block cursor-pointer rounded-lg bg-amber-500 px-6 py-3 text-center text-[11px] font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95'">
                                <template v-if="md.is_locked">View your picks →</template>
                                <template v-else-if="md.picks_submitted === 0">Submit picks →</template>
                                <template v-else-if="md.picks_submitted === md.picks_required">Review picks →</template>
                                <template v-else>Continue picks →</template>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── State: between matchdays ─── -->
            <div v-else class="overflow-hidden rounded-lg border border-[#232d42] bg-[#111622]">
                <div class="p-6 text-center md:p-8">
                    <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Between matchdays
                    </p>
                    <h2 class="mt-2 text-xl font-black tracking-wide text-white uppercase md:text-2xl">
                        No matchday open right now
                    </h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-slate-400">
                        The next matchday will appear here once it's
                        scheduled. Your season points carry over — nothing
                        to do until then.
                    </p>
                </div>
            </div>

            <!-- ═══════════════ SEASON SUMMARY ═══════════════ -->
            <MatchdaySummaryPanel v-if="hasSubLeagues" :summary="summary" :season="season"
                :form-guide="page.props.form_guide ?? []" />

            <!-- ═══════════════ SUB-LEAGUES ═══════════════ -->
            <div v-if="hasSubLeagues" class="space-y-4">
                <div
                    class="flex flex-col items-start justify-between gap-3 border-b border-gray-800/60 pb-4 md:flex-row md:items-center">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-black tracking-widest text-white uppercase">
                            Your sub-leagues
                        </h3>
                        <span class="font-mono text-[10px] font-bold text-slate-500">
                            [{{ subLeagues.length }}]
                        </span>
                    </div>
                    <div class="flex items-center gap-4">
                        <button type="button" @click="openCreate" data-testid="create-sub-league-link"
                            class="cursor-pointer text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300">
                            + Create one
                        </button>
                        <button type="button" @click="openJoin"
                            class="cursor-pointer text-[10px] font-black tracking-widest text-amber-400 uppercase transition-colors hover:text-amber-300">
                            Join another →
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div v-for="sub in subLeagues" :key="sub.id"
                        class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                        <!-- Header -->
                        <div
                            class="flex items-start justify-between gap-3 border-b border-[#232d42] bg-[#121824] px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="truncate font-mono text-sm font-black tracking-wide text-white">
                                        {{ sub.name }}
                                    </h4>
                                    <span v-if="sub.is_owner"
                                        class="rounded-sm bg-amber-500/20 px-1.5 py-0.5 text-[8px] font-black tracking-widest text-amber-400 uppercase">
                                        Owner
                                    </span>
                                </div>
                                <p class="mt-0.5 font-mono text-[10px] text-slate-500">
                                    {{ sub.member_count }} /
                                    {{ sub.max_members }} members
                                </p>
                            </div>
                            <button type="button" @click="copyInvite(sub.invite_code)"
                                :title="`Copy invite code: ${sub.invite_code}`"
                                class="group flex cursor-pointer items-center gap-1.5 rounded border border-[#232d42] bg-[#111622] px-2 py-1 font-mono text-[10px] font-black tracking-wider text-slate-400 uppercase transition-colors hover:border-amber-500/50 hover:text-amber-400">
                                <span>{{ sub.invite_code }}</span>
                                <span v-if="lastCopied === sub.invite_code" class="text-emerald-400">
                                    ✓
                                </span>
                            </button>

                            <Link :href="`/matchday/sub-leagues/${sub.id}`"
                                    class="text-[9px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300">
                                    View →
                                </Link>
                        </div>

                        <!-- Leaderboard preview -->
                        <div v-if="sub.leaderboard_preview.length > 0">
                            <div class="flex items-center justify-between border-b border-[#232d42]/60 px-4 py-2">
                                <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                    Leaderboard
                                </span>
                            </div>
                            <ul class="divide-y divide-[#232d42]/40">
                                <li v-for="row in sub.leaderboard_preview" :key="row.user_id"
                                    class="flex items-center gap-3 px-4 py-2" :class="row.user_id === user.id
                                        ? 'bg-amber-500/5'
                                        : ''
                                        ">
                                    <span class="w-6 flex-shrink-0 text-center font-mono text-[11px] font-black" :class="row.rank === 1
                                        ? 'text-amber-400'
                                        : row.rank === 2
                                            ? 'text-slate-300'
                                            : row.rank === 3
                                                ? 'text-orange-400'
                                                : 'text-slate-500'
                                        ">
                                        #{{ row.rank }}
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-[11px] font-bold" :class="row.user_id === user.id
                                        ? 'text-amber-300'
                                        : 'text-slate-300'
                                        ">
                                        {{ row.name }}
                                    </span>
                                    <span class="font-mono text-[10px] text-slate-500">
                                        {{ row.matchdays_played }} md
                                    </span>
                                    <span class="font-mono text-[11px] font-black text-white">
                                        {{ row.points }}
                                        <span class="text-slate-600 text-[9px]">pts</span>
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <div v-else class="px-4 py-6 text-center text-[10px] text-slate-600 italic">
                            No settled matchdays yet.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════ HOW IT WORKS (only when no sub-leagues) ═══════════════ -->
            <div v-if="!hasSubLeagues" class="rounded-lg border border-[#232d42] bg-[#111622] p-6 md:p-8">
                <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    How matchday works
                </p>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-500/20 font-mono text-[11px] font-black text-amber-400">1</span>
                        <h4 class="mt-2 text-[11px] font-black tracking-widest text-white uppercase">
                            Join a sub-league
                        </h4>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">
                            A private pool of friends for the whole season.
                            Paste an invite code, or create your own and
                            share the code.
                        </p>
                    </div>
                    <div>
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-500/20 font-mono text-[11px] font-black text-amber-400">2</span>
                        <h4 class="mt-2 text-[11px] font-black tracking-widest text-white uppercase">
                            Submit picks
                        </h4>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">
                            Every matchday, pick one selection per fixture.
                            Submit before the lock, or leave some blank —
                            missing picks score zero.
                        </p>
                    </div>
                    <div>
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-500/20 font-mono text-[11px] font-black text-amber-400">3</span>
                        <h4 class="mt-2 text-[11px] font-black tracking-widest text-white uppercase">
                            Climb the board
                        </h4>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">
                            Points accumulate across matchdays. The season
                            leaderboard shows where you stand against the
                            pool — and against everyone else.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <SubLeagueModal :open="modalOpen" :mode="modalMode" :seasons="seasons" :active-season="season"
            :league-options="leagueOptions" @close="closeModal" />
    </div>
</template>