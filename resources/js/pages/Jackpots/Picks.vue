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
            return 'text-amber-300';
        case 'settled':
            return 'text-emerald-300';
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
    isMatchday.value ? 'Matchday dashboard' : 'Back to contest',
);

// How far through the legs you are, for the progress bar (display only).
const progressPct = computed(() => {
    const total = contest.value.legs?.length ?? 0;
    return total > 0 ? Math.min(100, (pickCount.value / total) * 100) : 0;
});

// Option buttons: same look as the odds buttons everywhere else (full class strings for Tailwind).
const OPT_BTN =
    'flex min-h-14 w-full cursor-pointer flex-col items-center justify-center gap-0.5 rounded-xl border px-2 text-center transition-all duration-150 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95 disabled:cursor-not-allowed';
const OPT_OFF = 'border-white/[0.08] bg-white/[0.04] text-white hover:border-amber-300/50 hover:bg-white/[0.09]';
const OPT_ON = 'border-amber-400 bg-amber-400 text-[#070b14] shadow-[0_8px_20px_-8px_rgba(251,191,36,0.8)]';
</script>

<template>

    <Head :title="`Picks — ${contest.name}`" />

    <div class="min-h-screen bg-[#070b14] font-sans text-slate-300">
        <div class="mx-auto w-full max-w-3xl px-4 pt-6 pb-16 md:px-6 lg:px-8">
            <Link
                :href="backHref"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl pr-3 text-sm font-bold text-slate-400 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                {{ backLabel }}
            </Link>

            <!-- Header -->
            <header class="mt-4 mb-8">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-amber-400">
                        <template v-if="isMatchday && contest.matchday_number">Matchday {{ contest.matchday_number }} · </template>
                        {{ isOpen ? 'Your picks' : 'Your picks (locked)' }}
                    </p>
                    <InfoPopover title="How picks are scored">
                        <p>
                            Pick one selection for each leg. Correct picks
                            earn the odds value as points, wrong picks earn
                            zero.
                        </p>
                        <div class="rounded-2xl border border-white/10 bg-black/30 px-4 py-3 text-sm">
                            <p class="text-emerald-300">Correct: +odds as points</p>
                            <p class="text-rose-300">Wrong: 0 points</p>
                            <p class="text-slate-400">Void: 0 points, excluded</p>
                        </div>
                        <p>Ranked by correct count first, then total points.</p>
                        <p class="text-slate-400">You can change your picks any time before the deadline.</p>
                    </InfoPopover>
                </div>
                <h1 class="mt-1 text-4xl font-black tracking-tight text-white md:text-5xl">{{ contest.name }}</h1>

                <!-- Matchday context line -->
                <p v-if="isMatchday && isOpen && !isLocked && isEligible" class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-400">
                    <span><span class="text-base font-black text-white tabular-nums">{{ pickCount }} / {{ contest.legs.length }}</span> picks submitted</span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold"
                        :class="isUrgent ? 'bg-rose-400/10 text-rose-300' : 'bg-amber-400/10 text-amber-300'"
                    >
                        <span v-if="isUrgent" class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400 motion-reduce:animate-none"></span>
                        Locks in {{ lockCountdown }}
                    </span>
                </p>

                <!-- Regular contest context line -->
                <p v-else-if="!isMatchday || isEligible" class="mt-4 text-sm text-slate-400">
                    <span class="font-black text-white tabular-nums">{{ pickCount }}</span> of {{ contest.legs.length }} legs picked · Deadline {{ formatKickoff(contest.entry_deadline_at) }}
                </p>
            </header>

            <!-- Sub-league strip / Eligibility Warning (matchday only) -->
            <div v-if="isMatchday" class="mb-6">
                <!-- The good path: in at least one covering sub-league -->
                <div v-if="isEligible && subLeagues.length > 0" class="rounded-2xl border border-amber-400/25 bg-amber-400/[0.06] px-5 py-3.5">
                    <p class="text-xs font-semibold text-amber-300">Competing in</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <span v-for="sub in subLeagues" :key="sub.id" class="rounded-lg bg-white/[0.07] px-2.5 py-1 text-sm font-bold text-slate-100">{{ sub.name }}</span>
                    </div>
                </div>

                <!-- The bad path: no sub-league covering this league -->
                <div v-else-if="!isEligible" class="rounded-3xl border border-rose-400/30 bg-rose-400/[0.08] p-5 md:p-6" role="alert">
                    <div class="flex items-start gap-3.5">
                        <span class="text-2xl" aria-hidden="true">⚠️</span>
                        <div>
                            <h3 class="text-lg font-black tracking-tight text-rose-200">You can't make picks yet</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-slate-300">
                                To take part in the <strong class="text-white">{{ leagueLabel }}</strong> matchday, you must be a member of a sub-league that includes the {{ leagueLabel }}.
                            </p>
                            <button
                                @click="openCreateModal"
                                type="button"
                                class="mt-4 inline-flex min-h-12 cursor-pointer items-center rounded-xl bg-amber-400 px-6 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]"
                            >
                                Create a {{ leagueLabel }} sub-league
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Draft restored notice -->
            <div v-if="restoredFromDraft && isOpen && !isLocked" class="mb-4 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-2xl border border-amber-400/20 bg-amber-400/[0.06] px-4 py-3 text-sm text-amber-200" role="status">
                <span class="font-bold">Draft restored</span>
                <span class="text-slate-400">— your unsaved picks from this session are back.</span>
            </div>

            <!-- Status card: shown whenever the contest is no longer open -->
            <div v-if="!isOpen" class="mb-8 flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5">
                <div class="flex items-start gap-3.5">
                    <span class="mt-2 inline-block h-2.5 w-2.5 shrink-0 rounded-full" :class="statusDotClass"></span>
                    <div>
                        <p class="text-xs text-slate-500">Status</p>
                        <p class="mt-0.5 text-lg font-black capitalize" :class="statusColorClass">{{ statusLabel }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ statusHint }}</p>
                    </div>
                </div>

                <Link
                    :href="resultsUrl"
                    class="inline-flex min-h-12 items-center rounded-xl bg-amber-400 px-6 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]"
                >
                    View results →
                </Link>
            </div>

            <!-- EDITABLE PICKS: contest is still open -->
            <template v-if="isOpen">
                <div class="space-y-4">
                    <section v-for="(leg, i) in contest.legs" :key="leg.id" class="overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]">
                        <div class="px-5 pt-5">
                            <p class="text-xs font-semibold text-slate-500">Leg {{ i + 1 }} · {{ leg.market }}</p>
                            <p class="mt-1 text-lg font-bold text-white">
                                {{ leg.fixture.home_team }}
                                <span class="font-normal text-slate-500">vs</span>
                                {{ leg.fixture.away_team }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">Kickoff {{ formatKickoff(leg.fixture.kickoff) }}</p>
                        </div>

                        <div class="p-5 pt-4">
                            <div class="grid grid-cols-[repeat(auto-fit,minmax(7.5rem,1fr))] gap-2">
                                <button
                                    v-for="option in leg.options"
                                    :key="option.value"
                                    type="button"
                                    :disabled="isLocked || !isEligible || !!leg.distribution"
                                    :aria-pressed="picks[leg.id] === option.value"
                                    @click="setPick(leg.id, option.value)"
                                    :class="[
                                        OPT_BTN,
                                        picks[leg.id] === option.value ? OPT_ON : OPT_OFF,
                                        (isLocked || !isEligible || !!leg.distribution) && picks[leg.id] !== option.value ? 'opacity-40' : '',
                                    ]"
                                >
                                    <span class="w-full truncate text-sm font-bold">{{ option.value }}</span>

                                    <!-- Live crowd data if the leg is revealed -->
                                    <span v-if="leg.distribution && leg.distribution[option.value]" class="flex flex-col items-center leading-tight">
                                        <span class="text-sm font-black tabular-nums" :class="picks[leg.id] === option.value ? 'text-[#070b14]' : 'text-sky-300'">{{ leg.distribution[option.value].percentage }}%</span>
                                        <span class="text-xs tabular-nums" :class="picks[leg.id] === option.value ? 'text-[#070b14]/70' : 'text-slate-500'">{{ leg.distribution[option.value].fraction }}</span>
                                    </span>
                                    <!-- Odds if not yet revealed -->
                                    <span v-else class="text-sm font-black tabular-nums" :class="picks[leg.id] === option.value ? 'text-[#070b14]/80' : 'text-slate-400'">
                                        {{ option.odd.toFixed(2) }}
                                    </span>
                                </button>
                            </div>

                            <!-- Crowd distribution: only present after the user picks this leg -->
                            <div v-if="leg.distribution" class="mt-4 rounded-2xl border border-sky-400/20 bg-sky-400/[0.05] p-4">
                                <p class="text-sm font-bold text-sky-300">The crowd</p>
                                <div class="mt-2.5 space-y-2.5">
                                    <div v-for="(pct, option) in leg.distribution" :key="option" class="flex items-center gap-3 text-sm">
                                        <span class="w-20 shrink-0 truncate" :class="picks[leg.id] === option ? 'font-black text-amber-300' : 'text-slate-300'">{{ option }}</span>
                                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-white/[0.07]">
                                            <div class="h-full rounded-full" :class="picks[leg.id] === option ? 'bg-amber-400/80' : 'bg-sky-400/60'" :style="{ width: `${pct.percentage}%` }"></div>
                                        </div>
                                        <span class="w-11 shrink-0 text-right font-bold tabular-nums" :class="picks[leg.id] === option ? 'text-amber-300' : 'text-slate-300'">{{ `${pct.percentage}%` }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Submit: sticky so the thumb never has to hunt for it -->
                <div
                    v-if="!isLocked && isEligible"
                    class="sticky bottom-0 z-30 mt-6 -mx-4 border-t border-white/[0.08] bg-[#070b14]/90 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur-xl md:static md:mx-0 md:border-0 md:bg-transparent md:p-0 md:backdrop-blur-none"
                >
                    <div class="flex items-center justify-between gap-4 md:justify-end">
                        <div class="min-w-0 flex-1 md:hidden">
                            <p class="text-sm font-bold text-white tabular-nums">{{ pickCount }} / {{ contest.legs.length }} picks</p>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-white/[0.08]" aria-hidden="true">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-200 transition-all duration-300" :style="{ width: progressPct + '%' }"></div>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="submit"
                            :disabled="isProcessing || pickCount === 0"
                            :aria-busy="isProcessing"
                            class="inline-flex min-h-12 shrink-0 cursor-pointer items-center rounded-xl bg-amber-400 px-8 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all duration-200 hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isProcessing ? 'Saving…' : 'Save picks' }}
                        </button>
                    </div>
                </div>
            </template>

            <!-- READ-ONLY PICKS: contest is no longer open -->
            <template v-else>
                <section class="overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]">
                    <div class="flex items-center justify-between border-b border-white/[0.06] bg-black/25 px-5 py-3.5">
                        <h2 class="text-base font-bold text-emerald-300">Your picks (locked)</h2>
                        <span class="text-xs text-slate-500 tabular-nums">{{ lockedPickRows.length }} legs</span>
                    </div>

                    <ul class="divide-y divide-white/[0.06]">
                        <li v-for="pick in lockedPickRows" :key="pick.leg_id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-500">Leg {{ pick.index }} · {{ pick.market }}</p>
                                <p class="mt-0.5 truncate text-base font-bold text-white">
                                    {{ pick.home_team }}
                                    <span class="font-normal text-slate-500">vs</span>
                                    {{ pick.away_team }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <template v-if="pick.selection">
                                    <span class="rounded-lg border border-amber-400/40 bg-amber-400/10 px-3 py-1.5 text-sm font-black text-amber-200">{{ pick.selection }}</span>
                                    <span v-if="pick.odds !== null" class="text-sm font-black text-slate-300 tabular-nums">@{{ Number(pick.odds).toFixed(2) }}</span>
                                </template>
                                <span v-else class="text-sm font-bold text-slate-600">—</span>
                            </div>
                        </li>
                    </ul>

                    <div class="border-t border-white/[0.06] bg-black/25 px-5 py-3 text-sm text-slate-500">Picks are locked once the deadline passes. You can no longer change them.</div>
                </section>
            </template>
        </div>

        <!-- SubLeague Modal for quick creation -->
        <SubLeagueModal
            :open="modalOpen"
            mode="create"
            :seasons="[contest.season]"
            :active-season="contest.season"
            :league-options="[{ code: contest.league_code, label: leagueLabel }]"
            @close="closeModal"
            @success="closeModal"
        />
    </div>
</template>