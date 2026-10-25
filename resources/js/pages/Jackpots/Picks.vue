<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import InfoPopover from '@/components/InfoPopover.vue';
import SubLeagueModal from '@/components/Matchday/SubLeagueModal.vue';

const page = usePage();
const contest = computed(() => page.props.contest);
const initialPicks = computed(() => page.props.picks);
const subLeagues = computed(() => page.props.sub_leagues ?? []);
const leagueLabels = computed(() => page.props.league_labels ?? {});

// ────────────── Eligibility ──────────────
const isMatchday = computed(() => contest.value.is_matchday === true);
const isEligible = computed(() => contest.value.is_eligible !== false);
const isLocked = computed(() => contest.value.is_locked || contest.value.status !== 'open');
const isOpen = computed(() => contest.value.status === 'open');

const leagueLabel = computed(() => {
    return leagueLabels.value[contest.value.league_code] || contest.value.league_code;
});

// ────────────── Modal State ──────────────
const modalOpen = ref(false);
const openCreateModal = () => { modalOpen.value = true; };
const closeModal = () => { modalOpen.value = false; };

// ────────────── Picks State ──────────────
const picks = ref({});
const isProcessing = ref(false);
const restoredFromDraft = ref(false);
const pickCount = computed(() => Object.keys(picks.value).length);

// ────────────── Draft persistence ──────────────
// Local draft keyed by contest UUID. Server picks win; the local draft
// only hydrates when the server sends none. Cleared after a successful
// submit. Only active while the contest is open and the user is eligible.
const draftKey = computed(() => `contest_picks_${contest.value.uuid}`);

watch(
    initialPicks,
    (list) => {
        const next = {};
        for (const p of list ?? []) {
            next[p.leg_id] = p.selection;
        }

        if (Object.keys(next).length === 0 && !isLocked.value && isEligible.value) {
            try {
                const raw = localStorage.getItem(draftKey.value);
                if (raw) {
                    const draft = JSON.parse(raw);
                    for (const [k, v] of Object.entries(draft)) {
                        next[k] = v;
                    }
                    if (Object.keys(next).length > 0) {
                        restoredFromDraft.value = true;
                    }
                }
            } catch {
                // Malformed draft — ignore and start clean.
            }
        }

        picks.value = next;
    },
    { immediate: true },
);

watch(
    picks,
    (val) => {
        if (isLocked.value || !isEligible.value) return;
        try {
            if (Object.keys(val).length === 0) {
                localStorage.removeItem(draftKey.value);
            } else {
                localStorage.setItem(draftKey.value, JSON.stringify(val));
            }
        } catch {
            // Storage disabled or full — silently ignore.
        }
    },
    { deep: true },
);

const setPick = (legId, value) => {
    if (isLocked.value || !isEligible.value) return;
    picks.value = { ...picks.value, [legId]: value };
};

// ────────────── Countdown ──────────────
const tick = ref(0);
let ticker = null;

onMounted(() => {
    ticker = setInterval(() => tick.value++, 30_000);
});

onBeforeUnmount(() => {
    if (ticker) clearInterval(ticker);
});

const lockCountdown = computed(() => {
    void tick.value;
    const iso = contest.value.locks_at;
    if (!iso) return '';
    const ms = new Date(iso).getTime() - Date.now();
    if (ms <= 0) return 'locked';
    const mins = Math.floor(ms / 60_000);
    const hours = Math.floor(mins / 60);
    const days = Math.floor(hours / 24);
    if (days >= 1) return `${days}d ${hours % 24}h`;
    if (hours >= 1) return `${hours}h ${mins % 60}m`;
    return `${mins}m`;
});

const isUrgent = computed(() => {
    void tick.value;
    if (!isMatchday.value || isLocked.value) return false;
    const iso = contest.value.locks_at;
    if (!iso) return false;
    const ms = new Date(iso).getTime() - Date.now();
    return ms > 0 && ms < 60 * 60 * 1000;
});

// ────────────── Submit ──────────────
const submit = () => {
    if (isLocked.value || !isEligible.value) return;
    isProcessing.value = true;

    const payload = Object.entries(picks.value).map(([legId, selection]) => ({
        leg_id: Number(legId),
        selection,
    }));

    router.post(
        `/contests/${contest.value.uuid}/picks`,
        { picks: payload },
        {
            preserveScroll: true,
            onSuccess: () => {
                try {
                    localStorage.removeItem(draftKey.value);
                } catch {
                    // ignore
                }
                restoredFromDraft.value = false;
            },
            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
};

// ────────────── Formatting ──────────────
const formatKickoff = (iso) => {
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

// ────────────── Read-only rows ──────────────
const lockedPickRows = computed(() =>
    (contest.value.legs ?? []).map((leg, i) => {
        const selection = picks.value[leg.id] ?? null;
        const option = (leg.options ?? []).find((o) => o.value === selection);
        return {
            leg_id: leg.id,
            index: i + 1,
            market: leg.market,
            home_team: leg.fixture?.home_team ?? '',
            away_team: leg.fixture?.away_team ?? '',
            selection,
            odds: option?.odd ?? null,
        };
    }),
);

const resultsUrl = computed(() => `/contests/${contest.value.uuid}/results`);

const statusLabel = computed(() => contest.value.status ?? '');

const statusColorClass = computed(() => {
    switch (contest.value.status) {
        case 'underway':
            return 'text-amber-400';
        case 'settled':
            return 'text-emerald-400';
        case 'void':
            return 'text-slate-400';
        default:
            return 'text-slate-300';
    }
});

const statusDotClass = computed(() => {
    switch (contest.value.status) {
        case 'underway':
            return 'bg-amber-400';
        case 'settled':
            return 'bg-emerald-400';
        case 'void':
            return 'bg-slate-500';
        default:
            return 'bg-slate-500';
    }
});

const statusHint = computed(() => {
    if (contest.value.status === 'underway') {
        return isMatchday.value
            ? 'Fixtures are playing. Leaderboards update as each settles.'
            : 'Fixtures are being played. Results update as legs settle.';
    }
    if (contest.value.status === 'settled') {
        return isMatchday.value
            ? 'This matchday has settled. Season standings have been updated.'
            : 'This contest has settled. Final standings are available.';
    }
    if (isLocked.value) {
        return 'Picks are locked. Your selections are shown below.';
    }
    return 'This contest is no longer accepting picks.';
});

const backHref = computed(() =>
    isMatchday.value ? '/matchday' : `/contests/join/${contest.value.uuid}`,
);

const backLabel = computed(() =>
    isMatchday.value ? '← Matchday dashboard' : '← Back to contest',
);
</script>

<template>

    <Head :title="`Picks — ${contest.name}`" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-3xl px-2 py-12 md:px-6 lg:px-8">
            <Link :href="backHref"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">
                {{ backLabel }}
            </Link>

            <!-- Header -->
            <div class="mt-6 mb-8 border-b border-[#232d42] pb-6">
                <div class="flex items-center gap-2">
                    <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                        <template v-if="isMatchday && contest.matchday_number">
                            Matchday {{ contest.matchday_number }} ·
                        </template>
                        {{ isOpen ? 'Your picks' : 'Your picks (locked)' }}
                    </p>
                    <InfoPopover title="How picks are scored">
                        <p>
                            Pick one selection for each leg. Correct picks
                            earn the odds value as points, wrong picks earn
                            zero.
                        </p>
                        <div class="rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-[11px]">
                            <p class="text-emerald-400">
                                Correct: +odds as points
                            </p>
                            <p class="text-rose-400">Wrong: 0 points</p>
                            <p class="text-slate-400">
                                Void: 0 points, excluded
                            </p>
                        </div>
                        <p>
                            Ranked by correct count first, then total points.
                        </p>
                        <p class="text-slate-400">
                            You can change your picks any time before the
                            deadline.
                        </p>
                    </InfoPopover>
                </div>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    {{ contest.name }}
                </h1>

                <!-- Matchday context line -->
                <p v-if="isMatchday && isOpen && !isLocked && isEligible"
                    class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-400">
                    <span class="font-mono font-black text-slate-200">
                        {{ pickCount }} / {{ contest.legs.length }}
                    </span>
                    <span>picks submitted</span>
                    <span class="text-slate-600">·</span>
                    <span class="inline-flex items-center gap-1.5"
                        :class="isUrgent ? 'text-rose-400' : 'text-amber-400'">
                        <span v-if="isUrgent"
                            class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400"></span>
                        Locks in {{ lockCountdown }}
                    </span>
                </p>

                <!-- Regular contest context line -->
                <p v-else-if="!isMatchday || isEligible" class="mt-3 text-sm text-slate-400">
                    {{ pickCount }} of {{ contest.legs.length }} legs picked ·
                    Deadline {{ formatKickoff(contest.entry_deadline_at) }}
                </p>
            </div>

            <!-- Sub-league strip / Eligibility Warning (matchday only) -->
            <div v-if="isMatchday" class="mb-6">
                <!-- The Good Path: They are in at least one covering sub-league -->
                <div v-if="isEligible && subLeagues.length > 0"
                    class="rounded-lg border border-amber-500/30 bg-amber-500/5 px-4 py-2.5">
                    <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                        Competing in
                    </p>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span v-for="sub in subLeagues" :key="sub.id"
                            class="font-mono text-[11px] font-black text-slate-200">
                            {{ sub.name }}
                        </span>
                    </div>
                </div>

                <!-- The Bad Path: They lack a sub-league covering this league -->
                <div v-else-if="!isEligible" class="rounded-lg border border-rose-500/40 bg-rose-500/10 p-5 md:p-6">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <h3 class="text-sm font-black tracking-widest text-rose-400 uppercase">
                                You cannot make picks
                            </h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-300">
                                To participate in the <strong>{{ leagueLabel }}</strong> matchday, you must be a member
                                of a
                                sub-league that includes the {{ leagueLabel }}.
                            </p>
                            <button @click="openCreateModal" type="button"
                                class="mt-4 cursor-pointer rounded-lg bg-amber-500 px-5 py-2.5 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400">
                                Create a {{ leagueLabel }} Sub-league
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Draft restored notice -->
            <div v-if="restoredFromDraft && isOpen && !isLocked"
                class="mb-4 flex items-center gap-2 rounded border border-amber-500/20 bg-amber-500/5 px-3 py-2 text-[11px] text-amber-300">
                <span class="font-mono text-[10px] font-black uppercase">
                    Draft restored
                </span>
                <span class="text-slate-500">
                    — your unsaved picks from this session are back.
                </span>
            </div>

            <!-- Status card — shown whenever the contest is no longer open -->
            <div v-if="!isOpen"
                class="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-[#232d42] bg-[#161c2a] px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="inline-block h-2 w-2 shrink-0 rounded-full" :class="statusDotClass"></span>
                    <div>
                        <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Status
                        </p>
                        <p class="mt-0.5 text-sm font-black tracking-widest uppercase" :class="statusColorClass">
                            {{ statusLabel }}
                        </p>
                        <p class="mt-1 text-[11px] text-slate-500">
                            {{ statusHint }}
                        </p>
                    </div>
                </div>

                <Link :href="resultsUrl"
                    class="rounded-lg bg-amber-500 px-5 py-2.5 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400">
                    View results →
                </Link>
            </div>

            <!-- EDITABLE PICKS — contest is still open -->
            <template v-if="isOpen">
                <div class="space-y-3">
                    <div v-for="(leg, i) in contest.legs" :key="leg.id"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                    Leg {{ i + 1 }} · {{ leg.market }}
                                </p>
                                <p class="mt-1 truncate text-sm font-bold text-slate-200">
                                    {{ leg.fixture.home_team }}
                                    <span class="text-slate-500">vs</span>
                                    {{ leg.fixture.away_team }}
                                </p>
                                <p class="mt-0.5 text-[10px] text-slate-500">
                                    Kickoff
                                    {{ formatKickoff(leg.fixture.kickoff) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 justify-center">
                            <button v-for="option in leg.options" :key="option.value" type="button"
                                :disabled="isLocked || !isEligible || !!leg.distribution"
                                @click="setPick(leg.id, option.value)" :class="[
                                    'flex flex-1 min-w-[120px] items-center justify-between gap-2 rounded border px-3 py-2 text-[11px] font-bold tracking-wide uppercase transition-all',
                                    picks[leg.id] === option.value
                                        ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-400'
                                        : 'border-[#232d42] bg-[#111622] text-slate-300 hover:border-sky-500/40 hover:text-sky-400',
                                    (isLocked || !isEligible || !!leg.distribution) && picks[leg.id] !== option.value
                                        ? 'opacity-40'
                                        : '',
                                ]">
                                <span>{{ option.value }}</span>

                                <!-- Show live crowd data if the leg is revealed -->
                                <span v-if="leg.distribution && leg.distribution[option.value]"
                                    class="flex flex-col items-end leading-none">
                                    <span class="font-mono text-[9px] text-sky-400 font-black">{{
                                        leg.distribution[option.value].percentage }}%</span>
                                    <span class="font-mono text-[8px] text-slate-500 mt-0.5">{{
                                        leg.distribution[option.value].fraction }}</span>
                                </span>
                                <!-- Show odds if not yet revealed -->
                                <span v-else class="font-mono text-[10px] text-slate-500">
                                    {{ option.odd.toFixed(2) }}
                                </span>
                            </button>
                            <!-- Crowd distribution — only present after the user picks this leg -->
                            <div v-if="leg.distribution"
                                class="mt-3 rounded border border-sky-500/20 bg-sky-500/5 px-3 py-2 w-[100vw]">


                                <!-- {{  leg.distribution  }} -->
                                <p class="text-[9px] font-black tracking-widest text-sky-400 uppercase">Crowd</p>
                                <div class="mt-1 space-y-1">
                                    <div v-for="(pct, option) in leg.distribution" :key="option"
                                        class="flex items-center gap-2 text-[10px] text-emerald-400">
                                        <span class="w-14 shrink-0 font-mono font-black"
                                            :class="picks[leg.id] === option ? 'text-emerald-400' : 'text-slate-300'">
                                            {{ option }}
                                        </span>
                                        <div class="h-1.5 flex-1 overflow-hidden rounded bg-[#0f1422]">
                                            <div class="h-full bg-sky-500/60" :style="{ width: `${pct.percentage}%` }">
                                            </div>
                                        </div>
                                        <span class="w-10 shrink-0 text-right font-mono"
                                            :class="picks[leg.id] === option ? 'text-emerald-400' : 'text-slate-300'">
                                            {{ `${pct.percentage}%` }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit — sticky on mobile for one-thumb access -->
                <div v-if="!isLocked && isEligible"
                    class="sticky bottom-0 z-30 mt-6 -mx-2 border-t border-[#232d42] bg-[#070b14]/95 px-2 py-3 backdrop-blur md:static md:mx-0 md:border-0 md:bg-transparent md:px-0 md:py-0 md:backdrop-blur-none">
                    <div class="flex items-center justify-between gap-3 md:justify-end">
                        <span
                            class="font-mono text-[10px] font-bold tracking-widest text-slate-500 uppercase md:hidden">
                            {{ pickCount }} / {{ contest.legs.length }} picks
                        </span>
                        <button type="button" @click="submit" :disabled="isProcessing || pickCount === 0"
                            class="cursor-pointer rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50">
                            {{ isProcessing ? 'Saving…' : 'Save picks' }}
                        </button>
                    </div>
                </div>
            </template>

            <!-- READ-ONLY PICKS — contest is no longer open -->
            <template v-else>
                <section class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                    <div class="flex items-center justify-between border-b border-[#232d42] bg-[#111a30] px-5 py-3">
                        <span class="text-[10px] font-black tracking-widest text-emerald-400 uppercase">
                            Your picks (locked)
                        </span>
                        <span class="font-mono text-[10px] tracking-widest text-slate-500 uppercase">
                            {{ lockedPickRows.length }} legs
                        </span>
                    </div>

                    <ul class="divide-y divide-[#232d42]/60">
                        <li v-for="pick in lockedPickRows" :key="pick.leg_id"
                            class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                    Leg {{ pick.index }} · {{ pick.market }}
                                </p>
                                <p class="mt-0.5 truncate text-sm font-bold text-slate-200">
                                    {{ pick.home_team }}
                                    <span class="text-slate-500">vs</span>
                                    {{ pick.away_team }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <template v-if="pick.selection">
                                    <span
                                        class="rounded border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-[10px] font-black tracking-wide text-emerald-400 uppercase">
                                        {{ pick.selection }}
                                    </span>
                                    <span v-if="pick.odds !== null" class="font-mono text-[10px] text-amber-400">
                                        @{{ Number(pick.odds).toFixed(2) }}
                                    </span>
                                </template>
                                <span v-else class="text-[10px] font-bold tracking-widest text-slate-600 uppercase">
                                    —
                                </span>
                            </div>
                        </li>
                    </ul>

                    <div class="border-t border-[#232d42] bg-[#0a101f] px-5 py-2 text-[10px] text-slate-500">
                        Picks are locked once the deadline passes. You can no
                        longer change them.
                    </div>
                </section>
            </template>
        </div>

        <!-- SubLeague Modal for quick creation -->
        <SubLeagueModal :open="modalOpen" mode="create" :seasons="[contest.season]" :active-season="contest.season"
            :league-options="[{ code: contest.league_code, label: leagueLabel }]" @close="closeModal"
            @success="closeModal" />
    </div>
</template>