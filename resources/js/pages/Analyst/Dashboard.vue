<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

import Panel from '@/components/Panel.vue';
import EmptyState from '@/components/EmptyState.vue';
import InfoPopover from '@/components/InfoPopover.vue';

const props = defineProps<{
    picks?: any;
    pagination?: any;
    record?: any;
    published_today?: boolean;
    profile_url?: string | null;
}>();

// ── Defensive defaults ────────────────────────────────────────
const safePicks = computed(() => {
    const p = props.picks;
    if (Array.isArray(p)) return { data: p };
    if (p && Array.isArray(p.data)) return p;
    return { data: [] };
});

const safePagination = computed(() => ({
    current_page: props.pagination?.current_page ?? 1,
    last_page:    props.pagination?.last_page    ?? 1,
    prev_url:     props.pagination?.prev_url     ?? null,
    next_url:     props.pagination?.next_url     ?? null,
}));

const safeRecord = computed(() => props.record ?? {
    won: 0, lost: 0, voided: 0, open: 0, settled_count: 0,
    total_profit_units: 0, roi: null, win_rate: null,
});

const publishedToday = computed(() => !!props.published_today);
const profileUrl = computed(() => props.profile_url ?? null);

// ── Derived display values ───────────────────────────────────
const unitsLabel = computed(() => {
    const u = safeRecord.value.total_profit_units ?? 0;
    if (u === 0) return '0.00';
    return `${u > 0 ? '+' : '−'}${Math.abs(u).toFixed(2)}`;
});

const unitsTone = computed(() => {
    const u = safeRecord.value.total_profit_units ?? 0;
    if (u > 0) return 'text-emerald-300';
    if (u < 0) return 'text-rose-300';
    return 'text-white';
});

const roiLabel = computed(() => {
    const r = safeRecord.value.roi;
    if (r === null || r === undefined) return '—';
    return `${r > 0 ? '+' : ''}${r.toFixed(1)}%`;
});

const roiTone = computed(() => {
    const r = safeRecord.value.roi;
    if (r === null || r === undefined || r === 0) return 'text-slate-300';
    return r > 0 ? 'text-emerald-300' : 'text-rose-300';
});

const recordLine = computed(() => {
    const r = safeRecord.value;
    return `${r.won}–${r.lost}`;
});

const pendingCount = computed(() => {
    return safePicks.value.data.filter((p: any) =>
        p.status === 'pending' || p.status === 'underway'
    ).length;
});

// ── Helpers ──────────────────────────────────────────────────
function formatDate(iso: string | null | undefined): string {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString('en-KE', { day: 'numeric', month: 'short' });
    } catch {
        return '—';
    }
}

function unpublish(pick: any) {
    if (!confirm(`Unpublish "${pick.analyst_title ?? 'this pick'}"? This frees up today's slot.`)) return;
    router.post(`/dashboard/analyst/picks/${pick.id}/unpublish`);
}

function statusBadge(pick: any): { label: string; cls: string } {
    if (pick.outcome === 'won' || (pick.status === 'settled' && pick.is_winner)) {
        return { label: 'Won', cls: 'bg-emerald-400/10 text-emerald-300' };
    }
    if (pick.outcome === 'lost' || (pick.status === 'settled' && !pick.is_winner)) {
        return { label: 'Lost', cls: 'bg-rose-400/10 text-rose-300' };
    }
    if (pick.outcome === 'void' || pick.status === 'voided') {
        return { label: 'Void', cls: 'bg-white/[0.06] text-slate-400' };
    }
    if (pick.status === 'underway') {
        return { label: 'In play', cls: 'bg-amber-400/10 text-amber-300' };
    }
    return { label: 'Pending', cls: 'bg-sky-400/10 text-sky-300' };
}

function legCount(pick: any): number {
    return Array.isArray(pick.legs) ? pick.legs.length : 0;
}

// Shared info for the record card, mirroring PlayDashboard's reputationInfo.
const recordInfo = [
    { lead: 'What it is:', text: 'Your public record. Every settled pick is counted, wins and losses alike — no filtering.' },
    { lead: 'Units:', text: 'Flat 1-unit stakes. A 1.85 win is +0.85u, a loss is −1.00u, a void is 0.00u.' },
    { lead: 'ROI:', text: 'Total units ÷ settled picks. Only appears once 10 picks have settled — before that it would be a rumour, not a record.' },
    { lead: 'Where readers see it:', text: 'On your analyst profile, in the archive, and on the homepage pick cards.' },
];
</script>

<template>
    <Head title="Analyst Dashboard" />

    <div class="flex min-h-screen flex-col bg-[#070b14] px-4 pt-6 pb-28 font-sans text-slate-200 selection:bg-amber-500/30 selection:text-white lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-7xl space-y-6">

            <!-- ═══════════════ HEADER ═══════════════ -->
            <header class="flex w-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3.5">
                    <div class="relative shrink-0">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-500/15 text-xl font-black text-amber-300 ring-2 ring-amber-400/30 md:h-14 md:w-14 md:text-2xl">
                            A
                        </div>
                        <span class="absolute -right-0.5 -bottom-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-[#070b14] bg-amber-400 text-[10px] font-black text-[#070b14]" title="Analyst">★</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-amber-400">Analyst</p>
                        <h1 class="truncate text-xl font-black tracking-tight text-white md:text-2xl">
                            Dashboard
                        </h1>
                        <p class="truncate text-xs text-slate-500">
                            Your picks · your record · your audience
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <Link
                        v-if="profileUrl"
                        :href="profileUrl"
                        class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-[#232d42] bg-[#0b1220] px-3 text-sm font-bold text-sky-300 transition-colors hover:border-sky-400/40 hover:bg-sky-400/10"
                    >
                        <span class="hidden sm:inline">Public profile</span>
                        <span class="sm:hidden">Profile</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </header>

            <!-- ═══════════════ RECORD HERO ═══════════════ -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Reputation card: units, ROI, W-L -->
                <section class="relative overflow-hidden rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6">
                    <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-amber-400/15 blur-3xl" aria-hidden="true"></div>

                    <div class="relative flex items-center gap-2">
                        <p class="text-sm text-slate-400">Your record</p>
                        <InfoPopover title="Your analyst record" label="What is this?">
                            <p v-for="(row, i) in recordInfo" :key="i">
                                <span class="font-bold text-slate-100">{{ row.lead }}</span>
                                {{ row.text }}
                            </p>
                        </InfoPopover>
                    </div>

                    <p class="relative mt-1 flex items-baseline gap-2">
                        <span class="text-5xl font-black tracking-tight tabular-nums" :class="unitsTone">
                            {{ unitsLabel }}
                        </span>
                        <span class="text-base font-bold text-slate-500">u</span>
                    </p>

                    <div class="relative mt-5 grid grid-cols-2 gap-2">
                        <div class="rounded-2xl bg-black/25 p-3">
                            <p class="text-xs text-slate-500">Record</p>
                            <p class="mt-0.5 text-xl font-black text-white tabular-nums">{{ recordLine }}</p>
                        </div>
                        <div class="rounded-2xl bg-black/25 p-3">
                            <p class="text-xs text-slate-500">ROI</p>
                            <p class="mt-0.5 text-xl font-black tabular-nums" :class="roiTone">{{ roiLabel }}</p>
                        </div>
                    </div>

                    <p v-if="safeRecord.roi === null" class="relative mt-3 text-[11px] text-slate-500">
                        ROI unlocks at 10 settled picks.
                    </p>
                </section>

                <!-- Metric tiles -->
                <section class="grid grid-cols-2 gap-3 lg:col-span-2">
                    <div class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5">
                        <p class="text-sm text-slate-400">Open picks</p>
                        <p class="mt-1 text-4xl font-black tabular-nums" :class="pendingCount > 0 ? 'text-sky-300' : 'text-white'">
                            {{ safeRecord.open ?? 0 }}
                        </p>
                        <p class="mt-2 text-xs font-semibold text-slate-500">
                            Live in the archive
                        </p>
                    </div>
                    <div class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5">
                        <p class="text-sm text-slate-400">Settled picks</p>
                        <p class="mt-1 text-4xl font-black text-white tabular-nums">
                            {{ safeRecord.settled_count ?? 0 }}
                        </p>
                        <p class="mt-2 text-xs font-semibold text-slate-500">
                            Every result counts
                        </p>
                    </div>
                    <div class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5">
                        <p class="text-sm text-slate-400">Win rate</p>
                        <p class="mt-1 text-4xl font-black tabular-nums"
                            :class="safeRecord.win_rate !== null && safeRecord.win_rate !== undefined ? 'text-amber-300' : 'text-slate-500'">
                            <template v-if="safeRecord.win_rate !== null && safeRecord.win_rate !== undefined">
                                {{ safeRecord.win_rate.toFixed(1) }}%
                            </template>
                            <template v-else>—</template>
                        </p>
                        <p class="mt-2 text-xs font-semibold text-slate-500">
                            Wins ÷ settled
                        </p>
                    </div>
                    <div class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5">
                        <p class="text-sm text-slate-400">Total picks</p>
                        <p class="mt-1 text-4xl font-black text-white tabular-nums">
                            {{ safePicks.data.length }}
                        </p>
                        <p class="mt-2 text-xs font-semibold text-slate-500">
                            All-time published
                        </p>
                    </div>
                </section>
            </div>

            <!-- ═══════════════ PUBLISH STATE ═══════════════ -->
            <section
                v-if="publishedToday"
                class="relative overflow-hidden rounded-3xl border border-amber-400/25 bg-gradient-to-br from-amber-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6"
            >
                <div class="pointer-events-none absolute -top-16 -left-12 h-44 w-44 rounded-full bg-amber-400/15 blur-3xl" aria-hidden="true"></div>
                <div class="relative flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-amber-300">Today's pick is live</p>
                        <p class="mt-1 text-lg font-bold text-white">You've published for today.</p>
                        <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-400">
                            The next slot resets at midnight. You can still edit or unpublish below —
                            unpublishing frees today's slot if you want to try a different angle.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-xl bg-amber-400/10 px-3 py-2 text-xs font-black uppercase tracking-widest text-amber-300 ring-1 ring-amber-400/30">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                        Published
                    </span>
                </div>
            </section>

            <section
                v-else
                class="relative overflow-hidden rounded-3xl border border-sky-400/25 bg-gradient-to-br from-sky-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6"
            >
                <div class="pointer-events-none absolute -top-16 -left-12 h-44 w-44 rounded-full bg-sky-400/15 blur-3xl" aria-hidden="true"></div>
                <div class="relative flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-sky-300">Ready to publish</p>
                        <p class="mt-1 text-lg font-bold text-white">You haven't published today.</p>
                        <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-400">
                            Build a betslip as usual, then toggle "Publish as analyst pick" on the
                            confirm screen. One set per analyst per day.
                        </p>
                    </div>
                    <Link
                        href="/fixtures"
                        class="inline-flex min-h-12 shrink-0 items-center gap-2 rounded-xl bg-sky-400 px-6 text-sm font-black text-[#070b14] transition-all duration-200 hover:bg-sky-300 focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:outline-none active:scale-[0.98]"
                    >
                        Curate today's picks
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </section>

            <!-- ═══════════════ PICKS PANEL ═══════════════ -->
            <Panel :title="`Your Picks [${safePicks.data.length}]`" accent="sky">
                <template #actions>
                    <span class="text-[11px] font-bold uppercase tracking-widest text-slate-500">
                        {{ pendingCount }} pending · {{ safeRecord.settled_count ?? 0 }} settled
                    </span>
                </template>

                <EmptyState
                    v-if="safePicks.data.length === 0"
                    title="No picks yet"
                    body="Your analyst picks will appear here the first time you publish one — pending first, then settled as each fixture finishes."
                    cta-label="Curate today's picks"
                    cta-href="/fixtures"
                    accent="sky"
                />

                <div v-else class="divide-y divide-white/[0.06]">
                    <div
                        v-for="pick in safePicks.data"
                        :key="pick.id"
                        class="flex flex-col gap-3 p-4 transition-colors hover:bg-white/[0.02] sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-widest"
                                    :class="statusBadge(pick).cls"
                                >
                                    {{ statusBadge(pick).label }}
                                </span>
                                <span class="text-[11px] font-bold uppercase tracking-widest text-slate-500">
                                    {{ formatDate(pick.analyst_published_at) }}
                                </span>
                            </div>
                            <p class="mt-1.5 truncate text-sm font-bold text-slate-100">
                                {{ pick.analyst_title ?? 'Untitled pick' }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ legCount(pick) }} leg{{ legCount(pick) === 1 ? '' : 's' }}
                                · {{ Number(pick.total_odds ?? 0).toFixed(2) }}×
                                <span
                                    v-if="pick.profit_units !== null && pick.profit_units !== undefined"
                                    :class="pick.profit_units >= 0 ? 'text-emerald-300' : 'text-rose-300'"
                                    class="font-bold"
                                >
                                    · {{ pick.profit_units >= 0 ? '+' : '−' }}{{ Math.abs(pick.profit_units).toFixed(2) }}u
                                </span>
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <template v-if="pick.status === 'pending' || pick.status === 'underway'">
                                <Link
                                    :href="`/dashboard/analyst/picks/${pick.id}/edit`"
                                    class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-bold text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200"
                                >
                                    Edit
                                </Link>
                                <button
                                    type="button"
                                    @click="unpublish(pick)"
                                    class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-bold text-rose-300 transition-colors hover:bg-rose-400/10 hover:text-rose-200"
                                >
                                    Unpublish
                                </button>
                            </template>
                            <Link
                                v-else
                                :href="pick.url"
                                class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-bold text-slate-300 transition-colors hover:bg-white/[0.05] hover:text-white"
                            >
                                View →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <nav
                    v-if="safePagination.last_page > 1"
                    class="flex items-center justify-between gap-3 border-t border-white/[0.06] p-3"
                >
                    <Link
                        v-if="safePagination.prev_url"
                        :href="safePagination.prev_url"
                        preserve-scroll
                        class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-bold text-slate-400 transition-colors hover:bg-white/[0.05] hover:text-slate-200"
                    >
                        ← Prev
                    </Link>
                    <span v-else></span>
                    <span class="text-[11px] font-bold uppercase tracking-widest text-slate-500">
                        Page {{ safePagination.current_page }} of {{ safePagination.last_page }}
                    </span>
                    <Link
                        v-if="safePagination.next_url"
                        :href="safePagination.next_url"
                        preserve-scroll
                        class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-bold text-slate-400 transition-colors hover:bg-white/[0.05] hover:text-slate-200"
                    >
                        Next →
                    </Link>
                    <span v-else></span>
                </nav>
            </Panel>

        </div>
    </div>
</template>