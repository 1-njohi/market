<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const props = defineProps({
    jackpot: { type: Object, required: true },
    picks:   { type: Array, default: () => [] },
    digest:  { type: Object, default: null },
    live_narrative: { type: Object, default: null }
});

const liveStyles = {
    positive: 'border-l-4 border-emerald-400/70 bg-emerald-400/[0.06] text-slate-200',
    negative: 'border-l-4 border-rose-400/70 bg-rose-400/[0.06] text-slate-200',
    urgent: 'border-l-4 border-amber-400/80 bg-amber-400/10 text-amber-100',
    neutral: 'border-l-4 border-slate-400/60 bg-white/[0.04] text-slate-300',
};

const liveIcons = {
    positive: '↑',
    negative: '↓',
    urgent: '!',
    neutral: '·',
};

const user = computed(() => page.props.auth?.user);

const picksByLeg = computed(() => {
    const map: Record<number, string> = {};
    for (const p of props.picks) map[p.leg_id] = p.selection;
    return map;
});

const isOpen = computed(() => !props.jackpot.is_locked);
const isSettled = computed(() => props.jackpot.status === 'settled');

const enter = () => router.post(`/jackpots/${props.jackpot.uuid}/enter`);

const formatKickoff = (iso?: string | null) =>
    iso ? new Date(iso).toLocaleString('en-KE', { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false }) : 'TBD';

// Colors for narrative types
const narrativeStyles: Record<string, string> = {
    positive: 'border-l-4 border-emerald-400/70 bg-emerald-400/[0.06] text-slate-200',
    negative: 'border-l-4 border-rose-400/70 bg-rose-400/[0.06] text-slate-200',
    neutral: 'border-l-4 border-slate-400/60 bg-white/[0.04] text-slate-300',
};

const narrativeIcon: Record<string, string> = {
    positive: '↑',
    negative: '↓',
    neutral: '·',
};

const narrativeIconClass: Record<string, string> = {
    positive: 'text-emerald-300',
    negative: 'text-rose-300',
    neutral: 'text-slate-400',
};
</script>

<template>
    <Head :title="`${jackpot.name} — Jackpot`" />

    <div class="min-h-screen bg-[#070b14] px-4 pt-6 pb-16 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/jackpots"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl pr-3 text-sm font-bold text-slate-400 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                All jackpots
            </Link>

            <!-- Header -->
            <header class="mt-4 mb-8">
                <p class="text-sm font-semibold text-purple-300">
                    Jackpot · {{ jackpot.legs.length }} legs · {{ jackpot.participant_count }} entered
                </p>
                <h1 class="mt-1 text-4xl font-black tracking-tight text-white md:text-5xl">{{ jackpot.name }}</h1>
                <p v-if="jackpot.description" class="mt-3 text-base leading-relaxed text-slate-400">{{ jackpot.description }}</p>
                <p class="mt-3 text-sm text-slate-500">
                    Locks {{ formatKickoff(jackpot.locks_at) }}
                    <span v-if="jackpot.is_locked" class="ml-1 rounded-full bg-rose-400/10 px-2 py-0.5 text-xs font-bold text-rose-300">Locked</span>
                </p>
            </header>

            <!-- ═══ LIVE NARRATIVE (locked, in progress) ═══ -->
            <div v-if="live_narrative && !isSettled" class="mb-8 space-y-4">
                <section class="relative overflow-hidden rounded-3xl border border-sky-400/25 bg-gradient-to-br from-sky-500/10 via-[#0b1220] to-[#0b1220] p-5 sm:p-6">
                    <div class="pointer-events-none absolute -top-16 -right-12 h-44 w-44 rounded-full bg-sky-400/15 blur-3xl" aria-hidden="true"></div>

                    <div class="relative flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2.5 text-xl font-black tracking-tight text-white">
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60 motion-reduce:animate-none"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                </span>
                                Jackpot live
                            </h2>
                            <p class="mt-1 text-sm text-slate-400">
                                {{ live_narrative.scoreboard.legs_resolved }} of {{ live_narrative.scoreboard.total_legs }} legs resolved
                            </p>
                        </div>
                        <Link
                            :href="`/contests/${jackpot.uuid}/results`"
                            class="inline-flex min-h-11 shrink-0 items-center rounded-xl border border-emerald-400/40 bg-emerald-400/10 px-4 text-sm font-bold text-emerald-200 transition-all hover:bg-emerald-400 hover:text-[#070b14] focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:outline-none active:scale-[0.98]"
                        >
                            Full results →
                        </Link>
                    </div>

                    <dl class="relative mt-5 grid grid-cols-3 gap-3">
                        <div class="rounded-2xl bg-black/25 p-4 text-center">
                            <dt class="text-xs text-slate-500">You</dt>
                            <dd class="mt-1 text-3xl font-black text-white tabular-nums">{{ live_narrative.scoreboard.user_correct }}</dd>
                        </div>
                        <div class="rounded-2xl bg-black/25 p-4 text-center">
                            <dt class="text-xs text-slate-500">Crowd</dt>
                            <dd class="mt-1 text-3xl font-black text-sky-300 tabular-nums">{{ live_narrative.scoreboard.crowd_correct }}</dd>
                        </div>
                        <div class="rounded-2xl bg-black/25 p-4 text-center">
                            <dt class="text-xs text-slate-500">Pending</dt>
                            <dd class="mt-1 text-3xl font-black text-amber-300 tabular-nums">{{ live_narrative.scoreboard.legs_pending }}</dd>
                        </div>
                    </dl>
                </section>

                <!-- Live talking points -->
                <section v-if="live_narrative.talking_points.length" class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 sm:p-6">
                    <h3 class="mb-3 text-base font-bold text-sky-300">What's happening</h3>
                    <ul class="space-y-2.5">
                        <li v-for="(point, i) in live_narrative.talking_points" :key="i" class="rounded-r-xl py-2.5 pr-3 pl-3.5 text-sm leading-relaxed" :class="liveStyles[point.type] || liveStyles.neutral">
                            <span class="mr-2 font-black">{{ liveIcons[point.type] || '·' }}</span>
                            {{ point.text }}
                        </li>
                    </ul>
                </section>

                <!-- ═══ SWING LEGS WATCHLIST ═══ -->
                <section v-if="live_narrative?.swing_legs?.length" class="rounded-3xl border border-amber-400/25 bg-[#0b1220] p-5 sm:p-6">
                    <h3 class="mb-3 flex items-center gap-2 text-base font-bold text-amber-300">
                        <span aria-hidden="true">⚡</span> Swing legs to watch
                    </h3>

                    <ul class="space-y-2.5">
                        <li v-for="swing in live_narrative.swing_legs" :key="swing.leg_id" class="flex flex-col justify-between gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4 md:flex-row md:items-center">
                            <!-- Fixture -->
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-white">{{ swing.fixture }}</p>
                                <p class="mt-0.5 text-xs text-slate-500" v-if="swing.kickoff">
                                    {{ new Date(swing.kickoff).toLocaleString('en-KE', { weekday: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                                <!-- User Pick -->
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">You</span>
                                    <span class="rounded-lg bg-sky-400/15 px-2.5 py-1 text-xs font-black text-sky-300">{{ swing.user_pick }}</span>
                                </div>

                                <!-- Crowd Pick -->
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">Crowd</span>
                                    <span class="rounded-lg bg-rose-400/15 px-2.5 py-1 text-xs font-black text-rose-300">
                                        {{ swing.crowd_pick }} <span class="font-semibold opacity-70 tabular-nums">{{ swing.crowd_pct }}%</span>
                                    </span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- ═══ SETTLED DIGEST ═══ -->
            <div v-if="(isSettled && digest)" class="mb-8 space-y-4">
                <!-- Score summary -->
                <section class="relative overflow-hidden rounded-3xl border border-emerald-400/25 bg-gradient-to-br from-emerald-500/10 via-[#0b1220] to-[#0b1220] p-5 sm:p-6">
                    <div class="pointer-events-none absolute -top-16 -right-12 h-44 w-44 rounded-full bg-emerald-400/15 blur-3xl" aria-hidden="true"></div>

                    <div class="relative flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-black tracking-tight text-white">Jackpot settled</h2>
                            <p class="mt-1 text-sm text-slate-400">
                                Here's how your ticket compared to {{ digest.totals.total_entrants.toLocaleString() }} other entrants.
                            </p>
                        </div>
                        <Link
                            :href="`/contests/${jackpot.uuid}/results`"
                            class="inline-flex min-h-11 shrink-0 items-center rounded-xl bg-emerald-400 px-4 text-sm font-black text-[#070b14] transition-all hover:bg-emerald-300 focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:outline-none active:scale-[0.98]"
                        >
                            Full results →
                        </Link>
                    </div>

                    <dl class="relative mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="rounded-2xl bg-black/25 p-4 text-center">
                            <dt class="text-xs text-slate-500">Your score</dt>
                            <dd class="mt-1 text-3xl font-black text-white tabular-nums">
                                {{ digest.totals.user_score }} <span class="text-base font-bold text-slate-500">/ {{ jackpot.legs.length }}</span>
                            </dd>
                        </div>
                        <div class="rounded-2xl bg-black/25 p-4 text-center">
                            <dt class="text-xs text-slate-500">Crowd score</dt>
                            <dd class="mt-1 text-3xl font-black text-sky-300 tabular-nums">
                                {{ digest.totals.crowd_score }} <span class="text-base font-bold text-slate-500">/ {{ jackpot.legs.length }}</span>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-amber-400/25 bg-amber-400/[0.07] p-4 text-center">
                            <dt class="text-xs text-amber-300">Contrarian accuracy</dt>
                            <dd class="mt-1 text-3xl font-black text-amber-300 tabular-nums">{{ digest.totals.contrarian_accuracy }}%</dd>
                            <p class="mt-0.5 text-xs text-slate-500 tabular-nums">{{ digest.totals.contrarian_wins }}/{{ digest.totals.contrarian_attempts }} against the crowd</p>
                        </div>
                        <div class="rounded-2xl bg-black/25 p-4 text-center">
                            <dt class="text-xs text-slate-500">Result</dt>
                            <dd
                                class="mt-1 text-3xl font-black"
                                :class="digest.totals.user_score > digest.totals.crowd_score ? 'text-emerald-300' : digest.totals.user_score < digest.totals.crowd_score ? 'text-rose-300' : 'text-slate-300'"
                            >
                                {{ digest.totals.user_score > digest.totals.crowd_score ? 'Beat' : digest.totals.user_score < digest.totals.crowd_score ? 'Below' : 'Tied' }}
                            </dd>
                            <p class="mt-0.5 text-xs text-slate-500">vs. crowd consensus</p>
                        </div>
                    </dl>
                </section>

                <!-- Narrative talking points -->
                <section v-if="digest.narrative && digest.narrative.length" class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 sm:p-6">
                    <h3 class="mb-3 text-base font-bold text-sky-300">What happened</h3>
                    <ul class="space-y-2.5">
                        <li v-for="(point, i) in digest.narrative" :key="i" class="rounded-r-xl py-2.5 pr-3 pl-3.5 text-sm leading-relaxed" :class="narrativeStyles[point.type] || narrativeStyles.neutral">
                            <span class="mr-2 font-black" :class="narrativeIconClass[point.type] || narrativeIconClass.neutral">{{ narrativeIcon[point.type] || '·' }}</span>
                            {{ point.text }}
                        </li>
                    </ul>
                </section>
            </div>

            <!-- ═══ UNSETTLED CTA (Guest / Non-entrant) ═══ -->
            <section v-else-if="!user" class="mb-6 rounded-3xl border border-amber-400/25 bg-amber-400/[0.06] p-5 sm:p-6">
                <p class="text-base text-slate-300"><strong class="text-white">Log in</strong> to enter this jackpot and reveal the crowd's picks.</p>
                <Link
                    href="/login"
                    class="mt-4 inline-flex min-h-12 items-center rounded-xl bg-amber-400 px-6 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]"
                >
                    Log in
                </Link>
            </section>

            <section v-else-if="!jackpot.is_entered && isOpen" class="mb-6 rounded-3xl border border-amber-400/30 bg-gradient-to-br from-amber-400/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6">
                <h2 class="text-xl font-black tracking-tight text-white">Enter the crowd</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-300">
                    Submit your ticket to reveal the crowd's consensus. Once you pick a leg, it's locked — you can't change it after seeing the percentages.
                </p>
                <button
                    type="button"
                    @click="enter"
                    class="mt-5 inline-flex min-h-12 cursor-pointer items-center gap-2 rounded-xl bg-amber-400 px-7 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]"
                >
                    Enter jackpot
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </button>
            </section>

            <section v-else-if="jackpot.is_entered && isOpen" class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-sky-400/25 bg-sky-400/[0.06] p-5">
                <p class="text-base font-semibold text-slate-200">You're in.</p>
                <Link
                    :href="`/contests/${jackpot.uuid}/picks`"
                    class="inline-flex min-h-11 items-center rounded-xl bg-sky-400 px-5 text-sm font-black text-[#070b14] transition-all hover:bg-sky-300 focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:outline-none active:scale-[0.98]"
                >
                    Make your picks →
                </Link>
            </section>

            <!-- ═══ LEGS ═══ -->
            <section>
                <h2 class="mb-3 text-lg font-bold text-white">Fixtures</h2>
                <ul class="divide-y divide-white/[0.06] overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]">
                    <li v-for="(leg, i) in jackpot.legs" :key="leg.id" class="px-5 py-5">
                        <!-- Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-500">Leg {{ i + 1 }} · {{ leg.market }}</p>

                                <!-- Clickable Link if fixture is finished -->
                                <Link
                                    v-if="leg.fixture.status === 'FT' || leg.fixture.status === 'AET' || leg.fixture.status === 'PEN'"
                                    :href="`/fixtures/${leg.fixture.id}/results`"
                                    class="group mt-1 flex items-center gap-1.5"
                                    :title="`See match results for ${leg.fixture.home_team} vs ${leg.fixture.away_team}`"
                                >
                                    <p class="truncate text-base font-bold text-white transition-colors group-hover:text-sky-300">
                                        {{ leg.fixture.home_team }} <span class="font-normal text-slate-500">vs</span> {{ leg.fixture.away_team }}
                                    </p>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5 shrink-0 text-slate-600 transition-colors group-hover:text-sky-300" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                                <p v-else class="mt-1 truncate text-base font-bold text-white">
                                    {{ leg.fixture.home_team }} <span class="font-normal text-slate-500">vs</span> {{ leg.fixture.away_team }}
                                </p>
                            </div>

                            <!-- User pick -->
                            <div v-if="digest && digest.legs[i]?.user_pick.selection" class="shrink-0 text-right">
                                <span class="text-xs text-slate-500">You</span>
                                <div
                                    class="mt-0.5 rounded-lg border px-2.5 py-1 text-xs font-black"
                                    :class="digest.legs[i].user_pick.won ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-rose-400/40 bg-rose-400/10 text-rose-300'"
                                >
                                    {{ digest.legs[i].user_pick.selection }} {{ digest.legs[i].user_pick.won ? '✓' : '✕' }}
                                </div>
                            </div>
                            <div v-else-if="picksByLeg[leg.id]" class="shrink-0 rounded-lg border border-sky-400/40 bg-sky-400/10 px-2.5 py-1 text-xs font-black text-sky-300">
                                {{ picksByLeg[leg.id] }}
                            </div>
                        </div>

                        <!-- Distribution (settled only) -->
                        <div v-if="digest" class="mt-4 space-y-2.5">
                            <div v-for="dist in digest.legs[i].distribution" :key="dist.selection" class="flex items-center gap-3">
                                <span class="w-20 shrink-0 text-sm text-slate-300">
                                    {{ dist.selection }}
                                    <span v-if="digest.legs[i].winning_selections.includes(dist.selection)" class="text-emerald-300">✓</span>
                                </span>
                                <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-white/[0.07]">
                                    <div
                                        class="h-full rounded-full"
                                        :class="digest.legs[i].winning_selections.includes(dist.selection) ? 'bg-emerald-400/80' : 'bg-sky-400/50'"
                                        :style="{ width: `${dist.percentage}%` }"
                                    ></div>
                                </div>
                                <span class="w-11 shrink-0 text-right text-sm font-bold text-white tabular-nums">{{ dist.percentage }}%</span>
                            </div>

                            <p v-if="digest.legs[i].user_pick.is_contrarian" class="mt-2 rounded-xl bg-amber-400/10 px-3 py-2 text-sm font-semibold text-amber-200">
                                ⚡ Contrarian pick: you went against the {{ digest.legs[i].crowd_majority.percentage }}% majority.
                            </p>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>