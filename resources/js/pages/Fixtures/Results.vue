<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    fixture: { type: Object, required: true },
    markets: { type: Array, required: true },
    fixture_talking_points: { type: Object, default: null },
});

const formatDate = (iso: string) => {
    return new Date(iso).toLocaleString('en-KE', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

const getTalkingPoint = (market: any) => {
    if (!market.crowd_wisdom?.is_visible) return null;

    const entries = Object.entries(market.crowd_wisdom.all || {}).sort(
        (a: any, b: any) => b[1] - a[1],
    );
    if (entries.length === 0) return null;

    const [crowdPick, crowdPct] = entries[0];
    const winner = market.winning_selections[0];
    const crowdWon = market.winning_selections.includes(crowdPick);

    if (!crowdWon) {
        return `The crowd got this wrong. ${crowdPct}% backed ${crowdPick}, but ${winner} won.`;
    }
    return `The crowd nailed this. ${crowdPct}% backed ${crowdPick} and it won.`;
};

const fmtKES = (n: number) =>
    'KES ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 });
</script>

<template>
    <Head :title="`${fixture.home_team} vs ${fixture.away_team} — Results`" />

    <div class="min-h-screen bg-[#070b14] px-3 py-8 font-sans text-slate-300 md:px-6 lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-3xl space-y-4">
            <!-- Back -->
            <Link href="/" class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">
                ← Back to Fixtures
            </Link>

            <!-- ═══ HERO SCOREBOARD ═══ -->
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-5 text-center">
                <p class="text-[9px] font-black tracking-widest text-slate-500 uppercase">
                    {{ fixture.league }} · {{ formatDate(fixture.date) }}
                </p>

                <div class="mt-3 flex items-center justify-center gap-2 md:gap-4">
                    <div class="flex-1 text-right">
                        <h2 class="text-lg font-black tracking-wider text-white uppercase md:text-xl">
                            {{ fixture.home_team }}
                        </h2>
                    </div>

                    <div class="flex flex-shrink-0 items-center justify-center rounded border border-[#232d42] bg-[#0f1422] px-4 py-2">
                        <span class="font-mono text-2xl font-black text-emerald-400">{{ fixture.score.home }}</span>
                        <span class="mx-2 text-xl text-slate-600">-</span>
                        <span class="font-mono text-2xl font-black text-emerald-400">{{ fixture.score.away }}</span>
                    </div>

                    <div class="flex-1 text-left">
                        <h2 class="text-lg font-black tracking-wider text-white uppercase md:text-xl">
                            {{ fixture.away_team }}
                        </h2>
                    </div>
                </div>

                <div class="mt-3 inline-block rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-0.5 text-[9px] font-black tracking-widest text-rose-400 uppercase">
                    Full Time
                </div>
            </div>

            <!-- ═══ FIXTURE TALKING POINTS ═══ -->
            <section v-if="fixture_talking_points" class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <header class="border-b border-[#232d42] bg-[#111a30] px-4 py-2">
                    <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                        Match Narrative
                    </span>
                </header>

                <div class="space-y-3 p-4">
                    <!-- Headline + tags -->
                    <div>
                        <h3 class="text-base font-black tracking-wide text-white">
                            {{ fixture_talking_points.narrative.headline }}
                        </h3>
                        <p class="mt-0.5 text-[11px] text-slate-400 leading-relaxed">
                            {{ fixture_talking_points.narrative.detail }}
                        </p>
                        <div v-if="fixture_talking_points.narrative.tags.length" class="mt-1.5 flex flex-wrap gap-1">
                            <span
                                v-for="tag in fixture_talking_points.narrative.tags"
                                :key="tag"
                                class="rounded border border-[#232d42] bg-[#0f1422] px-1.5 py-0.5 text-[8px] font-black tracking-widest text-slate-400 uppercase"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>

                    <!-- Crowd summary -->
                    <div v-if="fixture_talking_points.crowd.markets_evaluated > 0" class="rounded border border-[#232d42] bg-[#0f1422] p-3">
                        <div class="flex items-baseline justify-between">
                            <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                Crowd accuracy on this fixture
                            </span>
                            <span class="font-mono text-sm font-black text-white">
                                {{ fixture_talking_points.crowd.crowd_correct }} /
                                {{ fixture_talking_points.crowd.markets_evaluated }}
                                <span class="text-[9px] text-slate-500">markets correct</span>
                            </span>
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
                            <div v-if="fixture_talking_points.crowd.biggest_hit" class="rounded border border-emerald-500/30 bg-emerald-500/5 px-2 py-1.5">
                                <p class="text-[8px] font-black tracking-widest text-emerald-400 uppercase">
                                    Crowd's strongest call
                                </p>
                                <p class="mt-0.5 text-[10px] text-slate-300">
                                    <span class="font-black text-white">
                                        {{ fixture_talking_points.crowd.biggest_hit.pct }}%
                                    </span>
                                    backed
                                    <span class="font-mono text-emerald-400">
                                        {{ fixture_talking_points.crowd.biggest_hit.selection }}
                                    </span>
                                    on {{ fixture_talking_points.crowd.biggest_hit.market }}.
                                </p>
                            </div>

                            <div v-if="fixture_talking_points.crowd.biggest_miss" class="rounded border border-rose-500/30 bg-rose-500/5 px-2 py-1.5">
                                <p class="text-[8px] font-black tracking-widest text-rose-400 uppercase">
                                    Crowd's biggest miss
                                </p>
                                <p class="mt-0.5 text-[10px] text-slate-300">
                                    <span class="font-black text-white">
                                        {{ fixture_talking_points.crowd.biggest_miss.pct }}%
                                    </span>
                                    backed
                                    <span class="font-mono text-rose-400">
                                        {{ fixture_talking_points.crowd.biggest_miss.selection }}
                                    </span>
                                    on {{ fixture_talking_points.crowd.biggest_miss.market }}
                                    — but
                                    <span class="font-mono text-emerald-400">
                                        {{ fixture_talking_points.crowd.biggest_miss.winner }}
                                    </span>
                                    won.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Activity strip -->
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-2 text-center">
                            <span class="block text-[8px] font-black tracking-widest text-slate-500 uppercase">
                                Betslips
                            </span>
                            <p class="mt-0.5 font-mono text-base font-black text-white">
                                {{ fixture_talking_points.betslips.count }}
                            </p>
                        </div>
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-2 text-center">
                            <span class="block text-[8px] font-black tracking-widest text-slate-500 uppercase">
                                Sales volume
                            </span>
                            <p class="mt-0.5 font-mono text-base font-black text-emerald-400">
                                {{ fmtKES(fixture_talking_points.betslips.sales_volume) }}
                            </p>
                            <p class="mt-0.5 text-[8px] text-slate-500">
                                {{ fixture_talking_points.betslips.winning_slips }} winning slips
                            </p>
                        </div>
                        <div class="rounded border border-emerald-500/30 bg-emerald-500/5 p-2 text-center">
                            <span class="block text-[8px] font-black tracking-widest text-emerald-400 uppercase">
                                Seller earnings
                            </span>
                            <p class="mt-0.5 font-mono text-base font-black text-emerald-400">
                                {{ fmtKES(fixture_talking_points.betslips.seller_earnings) }}
                            </p>
                        </div>
                        <div class="rounded border border-[#232d42] bg-[#0f1422] p-2 text-center">
                            <span class="block text-[8px] font-black tracking-widest text-slate-500 uppercase">
                                Crowd voters
                            </span>
                            <p class="mt-0.5 font-mono text-base font-black text-sky-400">
                                {{ fixture_talking_points.crowd_voters }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ MARKET INTELLIGENCE ═══ -->
            <div class="space-y-3">
                <h3 class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    Market Intelligence
                </h3>

                <div v-for="market in markets" :key="market.id" class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                    <!-- Market Header -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#232d42] bg-[#111a30] px-3 py-2">
                        <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                            {{ market.name }}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-[8px] font-bold text-slate-500 uppercase">Winner:</span>
                            <span class="rounded bg-emerald-500/20 px-1.5 py-0.5 font-mono text-[9px] font-black text-emerald-400">
                                {{ market.winning_selections.join(', ') || 'Void' }}
                            </span>
                            <span v-if="market.winning_odds" class="font-mono text-[9px] text-slate-400">
                                @{{ market.winning_odds.toFixed(2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Crowd Wisdom & Distribution -->
                    <div class="p-3">
                        <div v-if="market.crowd_wisdom?.is_visible" class="space-y-2">
                            <!-- Distribution bars -->
                            <div class="space-y-1">
                                <div v-for="(pct, selection) in market.crowd_wisdom.all" :key="selection" class="flex items-center gap-2">
                                    <span class="w-16 shrink-0 truncate font-mono text-[9px] font-bold" :class="market.winning_selections.includes(selection) ? 'text-emerald-400' : 'text-slate-400'">
                                        {{ selection }}
                                    </span>
                                    <div class="h-1 flex-1 overflow-hidden rounded bg-[#0f1422]">
                                        <div class="h-full" :class="market.winning_selections.includes(selection) ? 'bg-emerald-500' : 'bg-sky-500/40'" :style="{ width: `${pct}%` }"></div>
                                    </div>
                                    <span class="w-6 shrink-0 text-right font-mono text-[9px] text-white">{{ pct }}%</span>
                                </div>
                            </div>

                            <!-- Talking point -->
                            <div class="rounded border border-[#232d42] bg-[#0f1422] px-2 py-1.5 text-[9px] italic text-slate-300">
                                {{ getTalkingPoint(market) }}
                            </div>
                        </div>

                        <div v-else class="rounded border border-[#232d42] bg-[#0f1422] px-2 py-1.5 text-[9px] text-slate-500">
                            Crowd data not available yet ({{ market.crowd_wisdom?.raw_count || 0 }} / {{ market.crowd_wisdom?.threshold || 50 }} predictions).
                        </div>

                        <!-- Stats strip -->
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-[#232d42] pt-2">
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3 w-3 text-slate-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                <span class="font-mono text-[9px] text-slate-400">
                                    {{ market.crowd_wisdom?.raw_count || 0 }}
                                    <span class="text-slate-600 uppercase tracking-wider">predictions</span>
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3 w-3 text-slate-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18" />
                                </svg>
                                <span class="font-mono text-[9px] text-slate-400">
                                    {{ market.market_popularity }}
                                    <span class="text-slate-600 uppercase tracking-wider">betslips</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>