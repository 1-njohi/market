<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    matchday: Object,
    top_entries: Array,
    legs: Array,
});

const settle = () => {
    if (!confirm('Are you sure you want to force settle this matchday? Ensure all legs have a result before doing this.')) return;
    router.post(`/admin/matchdays/${props.matchday.id}/settle`, {}, { preserveScroll: true });
};

const formatKES = (n: number) => 'KES ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 2 });
</script>

<template>
    <Head :title="`${matchday.name} — Admin`" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 border-b border-[#232d42] pb-6">
            <div>
                <Link href="/admin/matchdays" class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">← Back to Matchdays</Link>
                <h1 class="mt-2 text-2xl font-black tracking-wider text-white uppercase">{{ matchday.name }}</h1>
                <p class="mt-1 text-xs text-slate-500 font-mono">{{ matchday.uuid }}</p>
                <div class="mt-3 flex items-center gap-4 text-xs">
                    <span class="text-slate-400">Status: <strong class="uppercase" :class="matchday.status === 'settled' ? 'text-emerald-400' : 'text-amber-400'">{{ matchday.status }}</strong></span>
                    <span class="text-slate-400">Season: {{ matchday.season }}</span>
                    <span class="text-slate-400">Host: {{ matchday.host?.name }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <button v-if="matchday.status !== 'settled'" @click="settle" class="rounded border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-emerald-400 uppercase hover:bg-emerald-500/20">
                    Force Settle
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left: Fixtures & Distribution -->
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-[10px] font-black tracking-widest text-sky-400 uppercase">Fixtures & Crowd Picks</h2>
                <div class="space-y-3">
                    <div v-for="(leg, i) in legs" :key="leg.id" class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Leg {{ i + 1 }} · {{ leg.market }}</p>
                                <p class="mt-1 text-sm font-bold text-white">{{ leg.fixture.home }} vs {{ leg.fixture.away }}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 rounded text-[10px] uppercase font-black"
                                    :class="leg.status === 'pending' ? 'bg-slate-500/20 text-slate-400' : 'bg-emerald-500/20 text-emerald-400'">
                                    {{ leg.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Distribution Bars -->
                        <div class="mt-4 space-y-2">
                            <div v-for="dist in leg.distribution" :key="dist.selection" class="flex items-center gap-3">
                                <span class="w-16 text-[10px] font-mono text-slate-400">{{ dist.selection }}</span>
                                <div class="flex-1 h-2 rounded bg-[#0f1422] overflow-hidden">
                                    <!-- Rough visual percentage, assuming total entries -->
                                    <div class="h-full bg-sky-500/50" :style="{ width: `${(dist.count / (top_entries.length || 1)) * 100}%` }"></div>
                                </div>
                                <span class="w-8 text-right text-[10px] font-mono text-white">{{ dist.count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Leaderboard -->
            <div>
                <h2 class="text-[10px] font-black tracking-widest text-sky-400 uppercase mb-3">Top Players</h2>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">Rank</th>
                                <th class="px-4 py-3">Player</th>
                                <th class="px-4 py-3 text-right">Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#232d42]/60">
                            <tr v-for="entry in top_entries" :key="entry.id" class="hover:bg-[#1a2233]">
                                <td class="px-4 py-3 font-mono font-bold text-slate-400">#{{ entry.rank || '—' }}</td>
                                <td class="px-4 py-3 font-bold text-white">{{ entry.user?.name }}</td>
                                <td class="px-4 py-3 text-right font-mono font-black text-emerald-400">{{ entry.score_correct }}</td>
                            </tr>
                            <tr v-if="top_entries.length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-slate-500">No entries yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>