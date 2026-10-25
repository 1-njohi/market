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
    positive: 'border-l-4 border-emerald-500/60 bg-emerald-500/5 text-slate-200',
    negative: 'border-l-4 border-rose-500/60 bg-rose-500/5 text-slate-200',
    urgent: 'border-l-4 border-amber-500/80 bg-amber-500/10 text-amber-100',
    neutral: 'border-l-4 border-slate-500/60 bg-slate-500/5 text-slate-300',
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
    positive: 'border-l-4 border-emerald-500/60 bg-emerald-500/5 text-slate-200',
    negative: 'border-l-4 border-rose-500/60 bg-rose-500/5 text-slate-200',
    neutral: 'border-l-4 border-slate-500/60 bg-slate-500/5 text-slate-300',
};

const narrativeIcon: Record<string, string> = {
    positive: '↑',
    negative: '↓',
    neutral: '·',
};

const narrativeIconClass: Record<string, string> = {
    positive: 'text-emerald-400',
    negative: 'text-rose-400',
    neutral: 'text-slate-400',
};
</script>

<template>
    <Head :title="`${jackpot.name} — Jackpot`" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <Link href="/jackpots"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">
                ← All jackpots
            </Link>

            <!-- Header -->
            <header class="mt-6 mb-8 border-b border-[#232d42] pb-6">
                <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                    Jackpot · {{ jackpot.legs.length }} legs · {{ jackpot.participant_count }} entered
                </p>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    {{ jackpot.name }}
                </h1>
                <p v-if="jackpot.description" class="mt-2 text-sm text-slate-400">{{ jackpot.description }}</p>
                <p class="mt-3 text-[11px] text-slate-500">
                    Locks {{ formatKickoff(jackpot.locks_at) }}
                    <span v-if="jackpot.is_locked" class="text-rose-400">· Locked</span>
                </p>
            </header>

            <!-- ═══ LIVE NARRATIVE (locked, in progress) ═══ -->
            <div v-if="live_narrative && !isSettled" class="mb-8 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black tracking-widest text-emerald-400 uppercase">Jackpot Live
                            </h2>
                            <p class="mt-1 text-[11px] text-slate-400">
                                <!-- Here's how your ticket compared to {{ digest?.totals?.total_entrants?.toLocaleString() || 0 }}
                                other entrants. -->
                            </p>
                        </div>
                        <Link :href="`/contests/${jackpot.uuid}/results`"
                            class="flex-shrink-0 rounded-lg bg-emerald-500 px-4 py-2 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-emerald-400">
                            View Full Results →
                        </Link>
                    </div>

                <!-- Score strip -->
                <div class="rounded-lg border border-sky-500/30 bg-gradient-to-br from-sky-500/10 to-transparent p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="mt-1 text-[11px] text-slate-400">
                                {{ live_narrative.scoreboard.legs_resolved }} of {{ live_narrative.scoreboard.total_legs
                                }} legs resolved
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-3 text-center">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">You</span>
                            <p class="mt-1 text-2xl font-black text-white">{{ live_narrative.scoreboard.user_correct }}
                            </p>
                        </div>
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-3 text-center">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Crowd</span>
                            <p class="mt-1 text-2xl font-black text-sky-400">{{ live_narrative.scoreboard.crowd_correct
                                }}</p>
                        </div>
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-3 text-center">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Pending</span>
                            <p class="mt-1 text-2xl font-black text-amber-400">{{ live_narrative.scoreboard.legs_pending
                                }}</p>
                        </div>
                    </div>
                </div>

                <!-- Live talking points -->
                <div v-if="live_narrative.talking_points.length"
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                    <h3 class="text-[10px] font-black tracking-widest text-sky-400 uppercase mb-3">
                        What's happening
                    </h3>
                    <ul class="space-y-3">
                        <li v-for="(point, i) in live_narrative.talking_points" :key="i"
                            class="rounded-r-md py-2 pl-3 pr-2 text-[12px] leading-relaxed"
                            :class="liveStyles[point.type] || liveStyles.neutral">
                            <span class="mr-2 font-black">{{ liveIcons[point.type] || '·' }}</span>
                            {{ point.text }}
                        </li>
                    </ul>
                </div>

                <!-- ═══ SWING LEGS WATCHLIST ═══ -->
                <div v-if="live_narrative?.swing_legs?.length"
                    class="rounded-lg border border-amber-500/30 bg-[#161c2a] p-5">
                    <h3 class="text-[10px] font-black tracking-widest text-amber-400 uppercase mb-3 flex items-center gap-2">
                        <span class="text-sm">⚡</span> Swing Legs to Watch
                    </h3>

                    <ul class="space-y-3">
                        <li v-for="swing in live_narrative.swing_legs" :key="swing.leg_id"
                            class="flex flex-col md:flex-row md:items-center justify-between gap-3 rounded border border-[#232d42] bg-[#0f1422] p-3">

                            <!-- Fixture Name -->
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-bold text-white">
                                    {{ swing.fixture }}
                                </p>
                                <p class="text-[10px] text-slate-500 mt-0.5" v-if="swing.kickoff">
                                    {{ new Date(swing.kickoff).toLocaleString('en-KE', {
                                        weekday: 'short', hour:
                                    '2-digit', minute: '2-digit' }) }}
                                </p>
                            </div>

                            <!-- User Pick -->
                            <div class="flex items-center gap-2 shrink-0 md:w-32">
                                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest w-8">You</span>
                                <span class="rounded bg-sky-500/20 px-2 py-1 text-[10px] font-black uppercase text-sky-400">
                                    {{ swing.user_pick }}
                                </span>
                            </div>

                            <!-- Crowd Pick -->
                            <div class="flex items-center gap-2 shrink-0 md:w-40 md:justify-end">
                                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest w-10">Crowd</span>
                                <span class="rounded bg-rose-500/20 px-2 py-1 text-[10px] font-black uppercase text-rose-400">
                                    {{ swing.crowd_pick }} <span class="opacity-70 text-[9px]">{{ swing.crowd_pct }}%</span>
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ═══ SETTLED DIGEST ═══ -->
            <div v-if="(isSettled && digest)" class="mb-8 space-y-6">
                <!-- Score summary -->
                <div class="rounded-lg border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 to-transparent p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black tracking-widest text-emerald-400 uppercase">Jackpot Settled</h2>
                            <p class="mt-1 text-[11px] text-slate-400">
                                Here's how your ticket compared to {{ digest.totals.total_entrants.toLocaleString() }}
                                other entrants.
                            </p>
                        </div>
                        <Link :href="`/contests/${jackpot.uuid}/results`"
                            class="flex-shrink-0 rounded-lg bg-emerald-500 px-4 py-2 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-emerald-400">
                            View Full Results →
                        </Link>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-3 text-center">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Your Score</span>
                            <p class="mt-1 text-2xl font-black text-white">
                                {{ digest.totals.user_score }} <span class="text-sm text-slate-500">/ {{ jackpot.legs.length }}</span>
                            </p>
                        </div>
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-3 text-center">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Crowd Score</span>
                            <p class="mt-1 text-2xl font-black text-sky-400">
                                {{ digest.totals.crowd_score }} <span class="text-sm text-slate-500">/ {{ jackpot.legs.length }}</span>
                            </p>
                        </div>
                        <div class="rounded border border-amber-500/30 bg-amber-500/5 p-3 text-center">
                            <span class="text-[9px] font-black text-amber-400 uppercase tracking-widest">Contrarian Accuracy</span>
                            <p class="mt-1 text-2xl font-black text-amber-400">{{ digest.totals.contrarian_accuracy }}%</p>
                            <p class="text-[9px] text-slate-500 mt-0.5">
                                {{ digest.totals.contrarian_wins }}/{{ digest.totals.contrarian_attempts }} against the crowd
                            </p>
                        </div>
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-3 text-center">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Result</span>
                            <p class="mt-1 text-2xl font-black" :class="digest.totals.user_score > digest.totals.crowd_score ? 'text-emerald-400' : digest.totals.user_score < digest.totals.crowd_score ? 'text-rose-400' : 'text-slate-300'">
                                {{ digest.totals.user_score > digest.totals.crowd_score ? 'Beat' :
                                    digest.totals.user_score < digest.totals.crowd_score ? 'Below' : 'Tied' }}
                            </p>
                            <p class="text-[9px] text-slate-500 mt-0.5">vs. crowd consensus</p>
                        </div>
                    </div>
                </div>

                <!-- Narrative talking points -->
                <div v-if="digest.narrative && digest.narrative.length" class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                    <h3 class="text-[10px] font-black tracking-widest text-sky-400 uppercase mb-3">
                        What happened
                    </h3>
                    <ul class="space-y-3">
                        <li v-for="(point, i) in digest.narrative" :key="i"
                            class="rounded-r-md py-2 pl-3 pr-2 text-[12px] leading-relaxed"
                            :class="narrativeStyles[point.type] || narrativeStyles.neutral">
                            <span class="mr-2 font-black" :class="narrativeIconClass[point.type] || narrativeIconClass.neutral">
                                {{ narrativeIcon[point.type] || '·' }}
                            </span>
                            {{ point.text }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ═══ UNSETTLED CTA (Guest / Non-entrant) ═══ -->
            <div v-else-if="!user" class="mb-6 rounded-lg border border-amber-500/30 bg-amber-500/5 p-5">
                <p class="text-sm text-slate-300">
                    <strong class="text-white">Log in</strong> to enter this jackpot and reveal the crowd's picks.
                </p>
                <Link href="/login" class="mt-3 inline-block rounded-lg bg-amber-500 px-5 py-2.5 text-[10px] font-black tracking-widest text-black uppercase hover:bg-amber-400">
                    Log in
                </Link>
            </div>

            <div v-else-if="!jackpot.is_entered && isOpen" class="mb-6 rounded-lg border border-amber-500/40 bg-gradient-to-r from-amber-500/15 to-transparent p-5">
                <h2 class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Enter the crowd</h2>
                <p class="mt-2 text-sm text-slate-300">
                    Submit your ticket to reveal the crowd's consensus. Once you pick a leg, it's locked — you can't change it after seeing the percentages.
                </p>
                <button type="button" @click="enter"
                    class="mt-4 cursor-pointer rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase hover:bg-amber-400">
                    Enter jackpot →
                </button>
            </div>

            <div v-else-if="jackpot.is_entered && isOpen" class="mb-6 rounded-lg border border-sky-500/30 bg-sky-500/5 p-4 text-[11px] text-slate-300">
                You're in. <Link :href="`/contests/${jackpot.uuid}/picks`" class="font-black text-sky-400 hover:text-sky-300">Make your picks →</Link>
            </div>

            <!-- ═══ LEGS ═══ -->
            <section>
                <h2 class="mb-3 text-[10px] font-black tracking-widest text-slate-500 uppercase">Fixtures</h2>
                <ul class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a] divide-y divide-[#232d42]/40">
                    <li v-for="(leg, i) in jackpot.legs" :key="leg.id" class="px-4 py-4">
                        <!-- Header -->
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="min-w-0">
                                <p class="text-[9px] font-bold tracking-widest text-slate-500 uppercase">
                                    Leg {{ i + 1 }} · {{ leg.market }}
                                </p>

                                <!-- Clickable Link if fixture is finished -->
                                <Link
                                    v-if="leg.fixture.status === 'FT' || leg.fixture.status === 'AET' || leg.fixture.status === 'PEN'"
                                    :href="`/fixtures/${leg.fixture.id}/results`"
                                    class="group mt-0.5 flex items-center gap-1"
                                    :title="`See match results for ${leg.fixture.home_team} vs ${leg.fixture.away_team}`"
                                >
                                    <p class="truncate text-sm font-bold text-white transition-colors group-hover:text-sky-400">
                                        {{ leg.fixture.home_team }} <span class="text-slate-500">vs</span> {{ leg.fixture.away_team }}
                                    </p>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3 w-3 text-slate-600 transition-colors group-hover:text-sky-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                                <p v-else class="mt-0.5 truncate text-sm font-bold text-white">
                                    {{ leg.fixture.home_team }} <span class="text-slate-500">vs</span> {{ leg.fixture.away_team }}
                                </p>
                            </div>

                            <!-- User pick -->
                            <div v-if="digest && digest.legs[i]?.user_pick.selection" class="shrink-0 text-right">
                                <span class="text-[9px] text-slate-500 uppercase tracking-widest">You</span>
                                <div class="mt-0.5 rounded border px-2 py-1 text-[10px] font-black uppercase tracking-wide"
                                    :class="digest.legs[i].user_pick.won ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400' : 'border-rose-500/40 bg-rose-500/10 text-rose-400'">
                                    {{ digest.legs[i].user_pick.selection }} {{ digest.legs[i].user_pick.won ? '✓' : '✕' }}
                                </div>
                            </div>
                            <div v-else-if="picksByLeg[leg.id]" class="shrink-0 rounded border border-sky-500/40 bg-sky-500/10 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-sky-400">
                                {{ picksByLeg[leg.id] }}
                            </div>
                        </div>

                        <!-- Distribution (settled only) -->
                        <div v-if="digest" class="space-y-2 mt-4">
                            <div v-for="dist in digest.legs[i].distribution" :key="dist.selection"
                                class="flex items-center gap-3">
                                <span class="w-16 text-[10px] font-mono text-slate-400">
                                    {{ dist.selection }}
                                    <span v-if="digest.legs[i].winning_selections.includes(dist.selection)"
                                        class="text-emerald-400">✓</span>
                                </span>
                                <div class="flex-1 h-2 rounded bg-[#0f1422] overflow-hidden">
                                    <div class="h-full bg-sky-500/50" :style="{ width: `${dist.percentage}%` }"></div>
                                </div>
                                <span class="w-10 text-right text-[10px] font-mono text-white">{{ dist.percentage }}%</span>
                            </div>

                            <div v-if="digest.legs[i].user_pick.is_contrarian" class="mt-2 text-[10px] font-black text-amber-400">
                                ⚡ Contrarian Pick: You went against the {{ digest.legs[i].crowd_majority.percentage }}% majority.
                            </div>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>