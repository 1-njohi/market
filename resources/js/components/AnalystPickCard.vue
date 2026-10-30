<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

interface Leg {
    odd_id: number;
    selection: string;
    market: string;
    odds: number;
    comment: string | null;
    confidence: number | null;
    status: string;
    fixture: {
        id: number | null;
        home_team: string;
        away_team: string;
        league: string | null;
        kickoff: string | null;
    };
}

interface Pick {
    id: string;
    code: string;
    url: string;
    analyst_title: string | null;
    reasoning: string | null;
    post_settlement_note: string | null;
    total_odds: number;
    status: 'pending' | 'underway' | 'settled' | 'voided';
    is_winner: boolean;
    profit_units: number | null;
    analyst_published_at: string | null;
    settled_at: string | null;
    is_edited: boolean;
    revision_count: number;
    analyst: { name: string; slug: string | null; avatar_url: string | null };
    legs: Leg[];
}

const props = defineProps<{
    pick: Pick;
    variant?: 'default' | 'compact';
}>();

const analyst = computed(() => props.pick?.analyst ?? {
    name: 'Fainali Analyst',
    slug: null,
    avatar_url: null,
});
const isSettled = computed(() => ['settled', 'voided'].includes(props.pick.status));
const isVoided = computed(() => props.pick.status === 'voided');
const isPending = computed(() => ['pending', 'underway'].includes(props.pick.status));

const outcomeBadge = computed(() => {
    if (isVoided.value) {
        return { label: 'Void', cls: 'bg-slate-500/10 text-slate-400 border-slate-500/30' };
    }
    if (props.pick.status === 'settled' && props.pick.is_winner) {
        return {
            label: `Won · ${formatProfit(props.pick.profit_units)}`,
            cls: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
        };
    }
    if (props.pick.status === 'settled' && !props.pick.is_winner) {
        return { label: `Lost · −1.00u`, cls: 'bg-red-500/10 text-red-400 border-red-500/30' };
    }
    return { label: 'Open', cls: 'bg-sky-500/10 text-sky-400 border-sky-500/30' };
});

function formatProfit(p: number | null): string {
    if (p === null) return '';
    return `${p >= 0 ? '+' : '−'}${Math.abs(p).toFixed(2)}u`;
}

const fixtureLine = computed(() => {
    if (props.pick.legs.length === 0) return '—';
    if (props.pick.legs.length === 1) {
        const l = props.pick.legs[0];
        return `${l.fixture.home_team} vs ${l.fixture.away_team}`;
    }
    return `${props.pick.legs.length} legs · ${props.pick.legs[0].fixture.home_team} vs ${props.pick.legs[0].fixture.away_team} + ${props.pick.legs.length - 1} more`;
});

const visibleLegs = computed(() =>
    props.variant === 'compact' ? props.pick.legs.slice(0, 2) : props.pick.legs,
);

const extraLegCount = computed(() => Math.max(0, props.pick.legs.length - visibleLegs.value.length));

const publishedLabel = computed(() => {
    if (!props.pick.analyst_published_at) return '';
    const d = new Date(props.pick.analyst_published_at);
    return d.toLocaleDateString('en-KE', { day: 'numeric', month: 'short' });
});
</script>

<template>
    <Link :href="pick.url"
        class="group flex h-full flex-col rounded-xl border border-[#232d42] bg-[#111622] p-4 transition-colors hover:border-amber-500/50">
        <!-- byline + date -->
        <div class="flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2">
                <img v-if="pick.analyst.avatar_url" :src="pick.analyst.avatar_url" :alt="pick.analyst.name"
                    class="h-6 w-6 shrink-0 rounded-full border border-[#232d42] object-cover" />
                <div v-else
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-[#232d42] bg-[#0a1628] text-[10px] font-black text-amber-400">
                    {{ pick.analyst.name.charAt(0) }}
                </div>
                <span class="truncate text-xs font-bold text-slate-300">{{ pick.analyst.name }}</span>
            </div>
            <span class="shrink-0 text-[10px] font-bold uppercase tracking-widest text-slate-500">
                {{ publishedLabel }}
            </span>
        </div>

        <!-- headline -->
        <h3 class="mt-3 line-clamp-2 text-base font-bold leading-snug text-white group-hover:text-amber-200">
            {{ pick.analyst_title ?? 'Untitled pick' }}
        </h3>

        <!-- fixture line -->
        <p class="mt-1 truncate text-[11px] font-medium text-slate-500">
            {{ fixtureLine }}
        </p>

        <!-- reasoning -->
        <p v-if="pick.reasoning" class="mt-2 line-clamp-3 text-sm leading-relaxed text-slate-400">
            {{ pick.reasoning }}
        </p>

        <!-- legs -->
        <ul class="mt-3 space-y-1">
            <li v-for="leg in visibleLegs" :key="leg.odd_id" class="flex items-center justify-between gap-2 text-xs">
                <span class="truncate text-slate-300">
                    <span class="font-bold">{{ leg.selection }}</span>
                    <span class="text-slate-600"> · {{ leg.market }}</span>
                </span>
                <span class="shrink-0 font-mono font-black text-emerald-400">
                    {{ Number(leg.odds).toFixed(2) }}
                </span>
            </li>
        </ul>
        <p v-if="extraLegCount > 0" class="mt-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">
            + {{ extraLegCount }} more leg{{ extraLegCount === 1 ? '' : 's' }}
        </p>

        <!-- footer -->
        <div class="mt-auto flex items-end justify-between gap-2 pt-4">
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Total odds</p>
                <p class="font-mono text-lg font-black text-amber-400">
                    {{ Number(pick.total_odds).toFixed(2) }}×
                </p>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span class="rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-widest"
                    :class="outcomeBadge.cls">
                    {{ outcomeBadge.label }}
                </span>
                <span v-if="pick.is_edited" class="text-[9px] font-bold uppercase tracking-widest text-slate-500">
                    {{ isSettled ? 'Edited before settlement' : 'Edited' }}
                </span>
                <span v-if="pick.analyst.is_self && pick.status === 'pending'"
                    class="text-[9px] font-bold uppercase tracking-widest text-slate-500">
                    <Link :href="`/dashboard/analyst/picks/${pick.id}/edit`">Edit</Link>
                </span>
            </div>

        </div>
    </Link>
</template>