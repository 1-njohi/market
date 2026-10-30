<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';
import SettlementCard from '@/components/Matchday/SettlementCard.vue';
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

// ────────────── Modal state ──────────────
const modalOpen = ref(false);
const modalMode = ref<'join' | 'create'>('join');
const openJoin = () => { modalMode.value = 'join'; modalOpen.value = true; };
const openCreate = () => { modalMode.value = 'create'; modalOpen.value = true; };
const closeModal = () => { modalOpen.value = false; };

// ────────────── Countdown ticker ──────────────
const tick = ref(0);
let ticker: ReturnType<typeof setInterval> | null = null;
onMounted(() => { ticker = setInterval(() => tick.value++, 30_000); });
onBeforeUnmount(() => { if (ticker) clearInterval(ticker); });

// ────────────── Matchday logic ──────────────
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
    liveRows.value.filter((r: any) => r.is_locked || r.legs_resolved > 0)
);

const pendingOpenMatchdays = computed(() =>
    openMatchdays.value.filter((md: any) => {
        const live = liveRows.value.find((l: any) => l.uuid === md.uuid);
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

// ────────────── Copy invite ──────────────
const lastCopied = ref<string | null>(null);
let copyTimer: ReturnType<typeof setTimeout> | null = null;
const copyInvite = (code: string) => {
    if (!navigator?.clipboard) return;
    navigator.clipboard.writeText(code).then(() => {
        lastCopied.value = code;
        if (copyTimer) clearTimeout(copyTimer);
        copyTimer = setTimeout(() => { lastCopied.value = null; copyTimer = null; }, 2000);
    });
};
onBeforeUnmount(() => { if (copyTimer) clearTimeout(copyTimer); });

// ────────────── Helpers ──────────────
const formatKickoff = (iso?: string | null) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('en-KE', {
        weekday: 'short', day: '2-digit', month: 'short',
        hour: '2-digit', minute: '2-digit', hour12: false,
    });
};
</script>

<template>
    <Head title="Matchday League" />

    <div class="flex min-h-screen flex-col bg-[#070b14] px-4 pt-6 pb-28 font-sans text-slate-200 selection:bg-amber-500/30 selection:text-white lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-7xl space-y-6">

            <!-- ═══════════════ HEADER ═══════════════ -->
            <header class="flex w-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3.5">
                    <div class="relative shrink-0">
                        <img
                            :src="user.avatar"
                            :alt="user.name"
                            class="h-12 w-12 rounded-full bg-[#111622] object-cover ring-2 ring-white/10 md:h-14 md:w-14"
                        />
                        <span
                            v-if="user.is_verified"
                            class="absolute -right-0.5 -bottom-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-[#070b14] bg-amber-400 text-[10px] font-black text-[#070b14]"
                            title="Verified"
                        >✓</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-amber-400">
                            Matchday League
                            <span v-if="season" class="text-slate-500"> · {{ season }}</span>
                        </p>
                        <h1 class="truncate text-xl font-black tracking-tight text-white md:text-2xl">
                            {{ user.name }}
                        </h1>
                        <p class="truncate text-xs text-slate-500">
                            Code {{ user.code }}
                            <template v-if="summary">
                                · {{ summary.current_streak > 0 ? `${summary.current_streak}-match streak` : 'no streak' }}
                            </template>
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <DashboardSwitcher active="play" />
                </div>
            </header>

            <!-- ═══════════════ SETTLEMENT CARDS ═══════════════ -->
            <div v-if="settlements.length > 0" class="space-y-3">
                <SettlementCard v-for="s in settlements" :key="s.entry_id" :settlement="s" />
            </div>

            <!-- ═══════════════ HERO — no sub-league ═══════════════ -->
            <section
                v-if="heroState === 'no-sub-league'"
                class="relative overflow-hidden rounded-3xl border border-amber-400/25 bg-gradient-to-br from-amber-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6 md:p-8"
            >
                <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-amber-400/15 blur-3xl" aria-hidden="true"></div>

                <div class="relative">
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-semibold text-amber-400">Join the matchday league</p>
                        <InfoPopover title="How matchday works" label="How does matchday work?">
                            <p>
                                Each matchday, every player picks one selection
                                per fixture — usually 10 across a weekend.
                                Correct picks score points; the season
                                leaderboard ranks everyone by total points.
                            </p>
                            <p>
                                <span class="font-bold text-slate-100">Sub-leagues:</span>
                                playing requires joining a private pool of
                                friends. Sub-leagues exist only for a season
                                — you and your friends start fresh each year.
                            </p>
                            <p>
                                <span class="font-bold text-slate-100">Free to play.</span>
                                No entry fee. Bragging rights only.
                            </p>
                            <p class="text-slate-400">
                                Once you're in a sub-league, you're
                                automatically entered into every matchday
                                that hasn't locked yet.
                            </p>
                        </InfoPopover>
                    </div>

                    <h2 class="mt-2 text-2xl font-black tracking-tight text-white md:text-3xl">
                        Play with your people
                    </h2>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-400">
                        Matchday is a weekly picks competition. Join a
                        sub-league with friends, submit your picks before
                        each weekend's lock, and track a season-long
                        leaderboard.
                    </p>

                    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            @click="openJoin"
                            class="inline-flex min-h-12 items-center justify-center rounded-xl bg-amber-500 px-6 text-sm font-black text-[#070b14] transition-all duration-200 hover:bg-amber-400 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]"
                        >
                            Join with invite code
                        </button>
                        <button
                            type="button"
                            @click="openCreate"
                            class="inline-flex min-h-12 items-center justify-center rounded-xl border border-white/[0.08] bg-[#0b1220] px-6 text-sm font-bold text-slate-300 transition-colors hover:border-amber-400/40 hover:bg-amber-400/5 hover:text-amber-300"
                        >
                            Create a sub-league
                        </button>
                    </div>
                </div>

                <!-- Open matchdays preview -->
                <div v-if="openMatchdays.length > 0" class="relative mt-6 -mx-5 -mb-5 sm:-mx-6 sm:-mb-6 md:-mx-8 md:-mb-8 rounded-b-3xl border-t border-amber-400/20 bg-black/25">
                    <div
                        v-for="md in openMatchdays"
                        :key="md.uuid"
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.06] px-5 py-3 last:border-0 sm:px-6 md:px-8"
                    >
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                {{ leagueLabel(md.league_code) }} · MD {{ md.matchday_number }}
                            </p>
                            <p class="mt-0.5 truncate font-mono text-xs font-bold text-slate-300">
                                {{ md.name }}
                                <span class="text-slate-600">· {{ md.picks_required }} fixtures</span>
                            </p>
                        </div>
                        <span
                            class="shrink-0 font-mono text-xs font-black"
                            :class="cardIsUrgent(md) ? 'text-amber-300' : 'text-slate-400'"
                        >
                            Locks in {{ cardCountdown(md) }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ HERO — live ═══════════════ -->
            <div v-else-if="liveRows.length > 0" class="space-y-3">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                    Live now
                </h3>
                <MatchdayLiveStrip v-for="row in liveRows" :key="row.uuid" :row="row" />
            </div>

            <!-- ═══════════════ HERO — picks open ═══════════════ -->
            <div v-else-if="heroState === 'picks-open'" class="space-y-3">
                <div
                    v-for="md in openMatchdays"
                    :key="md.uuid"
                    class="relative overflow-hidden rounded-3xl border p-5 sm:p-6 md:p-8"
                    :class="cardState(md) === 'live'
                        ? 'border-sky-400/25 bg-gradient-to-br from-sky-500/15 via-[#0b1220] to-[#0b1220]'
                        : 'border-amber-400/25 bg-gradient-to-br from-amber-500/15 via-[#0b1220] to-[#0b1220]'"
                >
                    <div
                        class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full blur-3xl"
                        :class="cardState(md) === 'live' ? 'bg-sky-400/15' : 'bg-amber-400/15'"
                        aria-hidden="true"
                    ></div>

                    <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-amber-400">
                                    {{ leagueLabel(md.league_code) }} · MD {{ md.matchday_number }}
                                </span>
                                <span class="text-slate-600">·</span>

                                <template v-if="md.is_locked">
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-sky-400">
                                        <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-sky-400"></span>
                                        Live
                                    </span>
                                </template>
                                <template v-else>
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest"
                                        :class="cardIsUrgent(md) ? 'text-rose-400' : 'text-slate-400'"
                                    >
                                        Locks in {{ cardCountdown(md) }}
                                    </span>
                                    <span
                                        v-if="cardIsUrgent(md)"
                                        class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400"
                                    ></span>
                                </template>
                            </div>

                            <h2 class="mt-2 text-2xl font-black tracking-tight text-white md:text-3xl">
                                {{ md.name }}
                            </h2>

                            <p v-if="!md.is_locked" class="mt-2 text-sm leading-relaxed text-slate-400">
                                <span class="font-mono font-black text-slate-200">
                                    {{ md.picks_submitted }} / {{ md.picks_required }}
                                </span>
                                picks submitted
                                <span v-if="md.picks_submitted === md.picks_required" class="text-emerald-400">
                                    · all done
                                </span>
                                <span v-else-if="md.picks_submitted > 0" class="text-amber-300">
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

                        <div class="shrink-0">
                            <Link
                                :href="`/contests/${md.uuid}/picks`"
                                :class="md.is_locked
                                    ? 'inline-flex min-h-12 items-center justify-center rounded-xl border border-sky-400/40 bg-[#0b1220] px-6 text-sm font-bold text-sky-300 transition-colors hover:bg-sky-400/10'
                                    : 'inline-flex min-h-12 items-center justify-center rounded-xl bg-amber-500 px-6 text-sm font-black text-[#070b14] transition-all duration-200 hover:bg-amber-400 active:scale-[0.98]'"
                            >
                                <template v-if="md.is_locked">View your picks →</template>
                                <template v-else-if="md.picks_submitted === 0">Submit picks →</template>
                                <template v-else-if="md.picks_submitted === md.picks_required">Review picks →</template>
                                <template v-else>Continue picks →</template>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════ HERO — between matchdays ═══════════════ -->
            <div v-else class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-8 text-center">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                    Between matchdays
                </p>
                <h2 class="mt-2 text-xl font-black tracking-tight text-white md:text-2xl">
                    No matchday open right now
                </h2>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-slate-400">
                    The next matchday will appear here once it's scheduled.
                    Your season points carry over — nothing to do until then.
                </p>
            </div>

            <!-- ═══════════════ SEASON SUMMARY ═══════════════ -->
            <MatchdaySummaryPanel
                v-if="hasSubLeagues"
                :summary="summary"
                :season="season"
                :form-guide="page.props.form_guide ?? []"
            />

            <!-- ═══════════════ SUB-LEAGUES ═══════════════ -->
            <section v-if="hasSubLeagues" class="space-y-3">
                <div class="flex flex-col items-start justify-between gap-3 border-b border-white/[0.06] pb-4 md:flex-row md:items-center">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-black uppercase tracking-widest text-white">
                            Your sub-leagues
                        </h3>
                        <span class="font-mono text-xs font-bold text-slate-500">
                            [{{ subLeagues.length }}]
                        </span>
                    </div>
                    <div class="flex items-center gap-4">
                        <button
                            type="button"
                            @click="openCreate"
                            class="text-[10px] font-black uppercase tracking-widest text-sky-400 transition-colors hover:text-sky-300"
                        >
                            + Create one
                        </button>
                        <button
                            type="button"
                            @click="openJoin"
                            class="text-[10px] font-black uppercase tracking-widest text-amber-400 transition-colors hover:text-amber-300"
                        >
                            Join another →
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div
                        v-for="sub in subLeagues"
                        :key="sub.id"
                        class="overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]"
                    >
                        <div class="flex items-start justify-between gap-3 border-b border-white/[0.06] px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="truncate font-mono text-sm font-black tracking-tight text-white">
                                        {{ sub.name }}
                                    </h4>
                                    <span
                                        v-if="sub.is_owner"
                                        class="rounded bg-amber-400/20 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-widest text-amber-300"
                                    >
                                        Owner
                                    </span>
                                </div>
                                <p class="mt-0.5 font-mono text-[11px] text-slate-500">
                                    {{ sub.member_count }} / {{ sub.max_members }} members
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="copyInvite(sub.invite_code)"
                                :title="`Copy invite code: ${sub.invite_code}`"
                                class="group flex items-center gap-1.5 rounded-lg border border-white/[0.08] bg-[#111622] px-2 py-1 font-mono text-[10px] font-black tracking-wider text-slate-400 uppercase transition-colors hover:border-amber-400/40 hover:text-amber-300"
                            >
                                <span>{{ sub.invite_code }}</span>
                                <span v-if="lastCopied === sub.invite_code" class="text-emerald-400">✓</span>
                            </button>

                            <Link
                                :href="`/matchday/sub-leagues/${sub.id}`"
                                class="inline-flex min-h-9 items-center rounded-lg px-2 text-[10px] font-black uppercase tracking-widest text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200"
                            >
                                View →
                            </Link>
                        </div>

                        <div v-if="sub.leaderboard_preview.length > 0">
                            <div class="flex items-center justify-between border-b border-white/[0.06] px-4 py-2">
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-500">
                                    Leaderboard
                                </span>
                            </div>
                            <ul class="divide-y divide-white/[0.04]">
                                <li
                                    v-for="row in sub.leaderboard_preview"
                                    :key="row.user_id"
                                    class="flex items-center gap-3 px-4 py-2"
                                    :class="row.user_id === user.id ? 'bg-amber-500/5' : ''"
                                >
                                    <span
                                        class="w-6 shrink-0 text-center font-mono text-[11px] font-black"
                                        :class="row.rank === 1
                                            ? 'text-amber-300'
                                            : row.rank === 2
                                                ? 'text-slate-300'
                                                : row.rank === 3
                                                    ? 'text-orange-400'
                                                    : 'text-slate-500'"
                                    >
                                        #{{ row.rank }}
                                    </span>
                                    <span
                                        class="min-w-0 flex-1 truncate text-[11px] font-bold"
                                        :class="row.user_id === user.id ? 'text-amber-200' : 'text-slate-300'"
                                    >
                                        {{ row.name }}
                                    </span>
                                    <span class="font-mono text-[10px] text-slate-500">
                                        {{ row.matchdays_played }} md
                                    </span>
                                    <span class="font-mono text-[11px] font-black text-white">
                                        {{ row.points }}
                                        <span class="text-[9px] text-slate-600">pts</span>
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <div v-else class="px-4 py-6 text-center text-[10px] italic text-slate-600">
                            No settled matchdays yet.
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ HOW IT WORKS (no sub-leagues yet) ═══════════════ -->
            <section v-if="!hasSubLeagues" class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-6 md:p-8">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                    How matchday works
                </p>
                <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-400/20 font-mono text-[11px] font-black text-amber-300">1</span>
                        <h4 class="mt-2 text-[11px] font-black uppercase tracking-widest text-white">
                            Join a sub-league
                        </h4>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">
                            A private pool of friends for the whole season.
                            Paste an invite code, or create your own and
                            share the code.
                        </p>
                    </div>
                    <div>
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-400/20 font-mono text-[11px] font-black text-amber-300">2</span>
                        <h4 class="mt-2 text-[11px] font-black uppercase tracking-widest text-white">
                            Submit picks
                        </h4>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">
                            Every matchday, pick one selection per fixture.
                            Submit before the lock, or leave some blank —
                            missing picks score zero.
                        </p>
                    </div>
                    <div>
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-400/20 font-mono text-[11px] font-black text-amber-300">3</span>
                        <h4 class="mt-2 text-[11px] font-black uppercase tracking-widest text-white">
                            Climb the board
                        </h4>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">
                            Points accumulate across matchdays. The season
                            leaderboard shows where you stand against the
                            pool — and against everyone else.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <SubLeagueModal
            :open="modalOpen"
            :mode="modalMode"
            :seasons="seasons"
            :active-season="season"
            :league-options="leagueOptions"
            @close="closeModal"
        />
    </div>
</template>