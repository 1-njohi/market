<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, required: true },
    logs: { type: Object, required: true },
});

const formatDate = (iso: string) => {
    return new Date(iso).toLocaleString('en-KE', {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

const pointsColor = computed(() => {
    if (props.stats.current_points > 0) return 'text-emerald-400';
    if (props.stats.current_points < 0) return 'text-rose-400';
    return 'text-white';
});
</script>

<template>

    <Head title="Prediction Portfolio" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-4xl space-y-6">

            <!-- Header -->
            <div class="border-b border-[#232d42] pb-6">
                <h1 class="text-2xl font-black tracking-wider text-white uppercase">Prediction Portfolio</h1>
                <p class="mt-1 text-xs text-slate-500">Your reputation and prediction history.</p>
            </div>

            <!-- Stats Strip -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Main Balance -->
                <div
                    class="rounded-lg border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#0f1422] p-6 md:col-span-2 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Current Reputation
                            Score</p>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="font-mono text-4xl font-black" :class="pointsColor">
                                {{ stats.current_points > 0 ? '+' : '' }}{{ stats.current_points }}
                            </span>
                            <span class="text-xs font-bold text-slate-500 uppercase">pts</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black tracking-widest text-sky-400 uppercase">Global Rank</p>
                        <p class="mt-2 font-mono text-2xl font-black text-white">
                            <template v-if="stats.global_rank">#{{ stats.global_rank }}</template>
                            <template v-else>—</template>
                        </p>
                        <p class="text-[10px] text-slate-500">of {{ stats.total_ranked }} ranked</p>
                    </div>
                </div>

                <!-- Accuracy -->
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-6">
                    <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Lifetime Accuracy</p>
                    <p class="mt-2 font-mono text-4xl font-black text-white">
                        <template v-if="stats.accuracy !== null">{{ stats.accuracy }}%</template>
                        <template v-else>—</template>
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">Based on settled picks only</p>
                </div>
            </div>

            <!-- Ledger -->
            <section class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <div class="border-b border-[#232d42] bg-[#0f1422] px-5 py-3 flex justify-between items-center">
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Point Ledger</span>
                    <span class="text-[10px] font-bold text-slate-600">Showing last 20</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            <tr>
                                <th class="px-5 py-3">Date</th>
                                <th class="px-5 py-3">Event</th>
                                <th class="px-5 py-3 text-right">Points</th>
                                <th class="px-5 py-3 text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#232d42]/60">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-[#1a2233]">
                                <td class="px-5 py-3 whitespace-nowrap text-slate-500 font-mono text-[10px]">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="font-bold text-slate-300">{{ log.description }}</span>
                                    <div class="text-[10px] text-slate-600 uppercase tracking-widest mt-0.5">{{ log.type
                                        }}</div>
                                </td>
                                <td class="px-5 py-3 text-right font-mono font-black"
                                    :class="log.amount > 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ log.amount > 0 ? '+' : '' }}{{ log.amount }}
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-slate-400">
                                    {{ log.balance_after }}
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="4" class="px-5 py-12 text-center text-slate-500">
                                    No prediction history yet. Make a pick to get started.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="border-t border-[#232d42] bg-[#0f1422] px-5 py-3 flex items-center justify-between"
                    v-if="logs.last_page > 1">
                    <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                        Page {{ logs.current_page }} of {{ logs.last_page }}
                    </span>
                    <div class="flex gap-2">
                        <Link v-if="logs.prev_page_url" :href="logs.prev_page_url"
                            class="text-sky-400 hover:text-sky-300 font-bold uppercase tracking-widest text-[10px]">←
                            Prev</Link>
                        <Link v-if="logs.next_page_url" :href="logs.next_page_url"
                            class="text-sky-400 hover:text-sky-300 font-bold uppercase tracking-widest text-[10px]">Next
                            →</Link>
                    </div>
                </div>
            </section>

        </div>
    </div>
</template>