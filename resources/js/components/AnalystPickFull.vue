<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AnalystPickCard from '@/components/AnalystPickCard.vue';

const props = defineProps<{
    pick: any;
    related: any[];
    record: any;
}>();

// ─────────────────────────────────────────────────────────────
// Defensive accessors.
// props.pick can be undefined momentarily (parent reactivity,
// route transitions, dev HMR). Every read goes through these
// computeds so nothing in the template can throw on undefined.
// ─────────────────────────────────────────────────────────────
const safePick = computed(() => props.pick ?? {
    id: '',
    code: '',
    url: '',
    analyst_title: 'Analyst pick',
    reasoning: '',
    post_settlement_note: null,
    total_odds: 0,
    status: 'pending',
    is_winner: false,
    profit_units: null,
    analyst_published_at: null,
    settled_at: null,
    is_edited: false,
    revision_count: 0,
    analyst: { name: 'Fainali Analyst', slug: null, avatar_url: null },
    legs: [],
});

const safeAnalyst = computed(() => safePick.value.analyst ?? {
    name: 'Fainali Analyst',
    slug: null,
    avatar_url: null,
});

const safeLegs = computed(() => Array.isArray(safePick.value.legs) ? safePick.value.legs : []);

const safeRelated = computed(() => Array.isArray(props.related) ? props.related : []);

// ─────────────────────────────────────────────────────────────
// Derived state — all read from safePick, never props.pick
// ─────────────────────────────────────────────────────────────
const isSettled = computed(() => ['settled', 'voided'].includes(safePick.value.status));
const isVoided = computed(() => safePick.value.status === 'voided');
const isWinner = computed(() => safePick.value.status === 'settled' && safePick.value.is_winner);

const whatsappUrl = computed(() => {
    const url = typeof window !== 'undefined' ? window.location.href : '';
    const title = safePick.value.analyst_title ?? 'Analyst pick';
    const odds = Number(safePick.value.total_odds ?? 0).toFixed(2);
    const text = `${title} @ ${odds}x — ${safeAnalyst.value.name} on Fainali\n${url}`;
    return `https://wa.me/?text=${encodeURIComponent(text)}`;
});

function formatProfit(p: number | null | undefined): string {
    if (p === null || p === undefined) return '—';
    return `${p >= 0 ? '+' : '−'}${Math.abs(p).toFixed(2)}u`;
}

function formatDate(iso: string | null | undefined): string {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleDateString('en-KE', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return '';
    }
}
</script>

<template>
    <article>
        <!-- byline -->
        <div class="flex items-center gap-3">
            <img
                v-if="safeAnalyst.avatar_url"
                :src="safeAnalyst.avatar_url"
                :alt="safeAnalyst.name"
                class="h-12 w-12 rounded-full border border-[#232d42] object-cover"
            />
            <div
                v-else
                class="flex h-12 w-12 items-center justify-center rounded-full border border-[#232d42] bg-[#0a1628] text-lg font-black text-amber-400"
            >
                {{ safeAnalyst.name.charAt(0) }}
            </div>
            <div class="min-w-0">
                <component
                    :is="safeAnalyst.slug ? Link : 'span'"
                    :href="safeAnalyst.slug ? `/analysts/${safeAnalyst.slug}` : undefined"
                    class="text-sm font-bold text-white hover:text-amber-300"
                >
                    {{ safeAnalyst.name }}
                </component>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">
                    Analyst · published {{ formatDate(safePick.analyst_published_at) }}
                    <span v-if="safePick.is_edited"> · edited</span>
                </p>
            </div>
        </div>

        <!-- headline -->
        <h1 class="mt-6 text-2xl font-black leading-tight text-white md:text-3xl">
            {{ safePick.analyst_title }}
        </h1>

        <!-- odds + outcome -->
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <div class="rounded-lg border border-[#232d42] bg-[#111622] px-3 py-2">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Total odds</p>
                <p class="font-mono text-xl font-black text-amber-400">
                    {{ Number(safePick.total_odds ?? 0).toFixed(2) }}×
                </p>
            </div>
            <div
                v-if="isSettled"
                class="rounded-lg border px-3 py-2"
                :class="isVoided
                    ? 'border-slate-500/30 bg-slate-500/10'
                    : (isWinner
                        ? 'border-emerald-500/30 bg-emerald-500/10'
                        : 'border-red-500/30 bg-red-500/10')"
            >
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Outcome</p>
                <p
                    class="font-mono text-xl font-black"
                    :class="isVoided ? 'text-slate-400' : (isWinner ? 'text-emerald-400' : 'text-red-400')"
                >
                    {{ isVoided ? 'Void' : (isWinner ? `Won ${formatProfit(safePick.profit_units)}` : 'Lost −1.00u') }}
                </p>
            </div>
            <div v-else class="rounded-lg border border-sky-500/30 bg-sky-500/10 px-3 py-2">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Status</p>
                <p class="font-mono text-xl font-black text-sky-400">
                    {{ safePick.status === 'underway' ? 'In play' : 'Pending' }}
                </p>
            </div>
        </div>

        <!-- reasoning -->
        <div v-if="safePick.reasoning" class="mt-8">
            <h2 class="text-[10px] font-black uppercase tracking-widest text-amber-400">Reasoning</h2>
            <div class="mt-3 whitespace-pre-wrap text-base leading-relaxed text-slate-300">
                {{ safePick.reasoning }}
            </div>
        </div>

        <!-- legs -->
        <div v-if="safeLegs.length > 0" class="mt-8">
            <h2 class="text-[10px] font-black uppercase tracking-widest text-amber-400">The picks</h2>
            <ul class="mt-3 space-y-3">
                <li
                    v-for="leg in safeLegs"
                    :key="leg.odd_id"
                    class="rounded-lg border border-[#232d42] bg-[#111622] p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-white">
                                {{ leg.fixture?.home_team ?? 'Home' }} vs {{ leg.fixture?.away_team ?? 'Away' }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                {{ leg.fixture?.league }}
                                <span v-if="leg.fixture?.kickoff" class="text-slate-600"> · </span>
                                <span v-if="leg.fixture?.kickoff">{{ formatDate(leg.fixture.kickoff) }}</span>
                            </p>
                            <p class="mt-1.5 text-sm font-bold text-sky-400">
                                {{ leg.market }} · {{ leg.selection }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-mono text-base font-black text-emerald-400">
                                {{ Number(leg.odds ?? 0).toFixed(2) }}
                            </p>
                            <p v-if="leg.confidence" class="mt-1 text-[10px] font-bold uppercase tracking-widest text-amber-400">
                                Conf {{ leg.confidence }}/5
                            </p>
                        </div>
                    </div>
                    <p v-if="leg.comment" class="mt-3 border-t border-[#232d42] pt-3 text-sm italic leading-relaxed text-slate-400">
                        "{{ leg.comment }}"
                    </p>
                </li>
            </ul>
        </div>

        <!-- post-settlement note -->
        <div v-if="safePick.post_settlement_note" class="mt-8 rounded-lg border border-[#232d42] bg-[#0f1422] p-5">
            <h2 class="text-[10px] font-black uppercase tracking-widest text-slate-500">What happened</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-300">
                {{ safePick.post_settlement_note }}
            </p>
        </div>

        <!-- share -->
        <div class="mt-8 flex flex-wrap items-center gap-3">
            <a
                :href="whatsappUrl"
                target="_blank"
                rel="noopener"
                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-xs font-black uppercase tracking-widest text-emerald-400 hover:bg-emerald-500/20"
            >
                Share on WhatsApp
            </a>
        </div>

        <!-- disclaimer -->
        <p class="mt-8 border-t border-[#232d42] pt-6 text-[11px] leading-relaxed text-slate-500">
            Published as free editorial analysis. 1-unit flat stake shown for record-keeping.
            Odds captured at publish time. Past performance does not guarantee future results.
            18+. If gambling stops being fun, stop.
        </p>

        <!-- related -->
        <div v-if="safeRelated.length > 0" class="mt-10">
            <h2 class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                More from {{ safeAnalyst.name }}
            </h2>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                <AnalystPickCard v-for="r in safeRelated" :key="r.id" :pick="r" variant="compact" />
            </div>
        </div>
    </article>
</template>