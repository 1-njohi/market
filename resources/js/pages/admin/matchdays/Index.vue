<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    matchdays: Object,
    stats: Object,
    filters: Object,
});

const formatKES = (n: number) => 'KES ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 });

const setStatus = (status: string) => {
    router.get('/admin/matchdays', { ...props.filters, status }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Matchdays — Admin" />

    <div>
        <!-- Header -->
        <div class="mb-6 border-b border-[#232d42] pb-6 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-black tracking-wider text-white uppercase">Matchdays & Contests</h1>
                <p class="mt-1 text-xs text-slate-500">Global overview of all matchday events</p>
            </div>
            <div class="flex gap-2">
                <button @click="setStatus('')" :class="!filters.status ? 'text-sky-400 border-sky-400' : 'text-slate-500 border-transparent'" class="px-3 py-1 text-xs font-bold uppercase tracking-wider border-b-2">All</button>
                <button @click="setStatus('open')" :class="filters.status === 'open' ? 'text-sky-400 border-sky-400' : 'text-slate-500 border-transparent'" class="px-3 py-1 text-xs font-bold uppercase tracking-wider border-b-2">Open</button>
                <button @click="setStatus('settled')" :class="filters.status === 'settled' ? 'text-sky-400 border-sky-400' : 'text-slate-500 border-transparent'" class="px-3 py-1 text-xs font-bold uppercase tracking-wider border-b-2">Settled</button>
            </div>
        </div>

        <!-- Global Stats -->
        <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total Matchdays</span>
                <p class="mt-2 text-2xl font-black text-white">{{ stats.total_matchdays }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total Entries</span>
                <p class="mt-2 text-2xl font-black text-sky-400">{{ stats.total_entries.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total Picks</span>
                <p class="mt-2 text-2xl font-black text-emerald-400">{{ stats.total_picks.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-amber-500/30 bg-amber-500/5 p-4">
                <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Total Prize Pool</span>
                <p class="mt-2 text-2xl font-black text-amber-400">{{ formatKES(stats.total_prize_pool) }}</p>
                <p class="mt-1 text-[10px] text-slate-500">If applicable</p>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Matchday</th>
                        <th class="px-4 py-3">Season / League</th>
                        <th class="px-4 py-3">Locks At</th>
                        <th class="px-4 py-3 text-right">Entrants</th>
                        <th class="px-4 py-3 text-right">Legs</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#232d42]/60">
                    <tr v-for="md in matchdays.data" :key="md.id" class="hover:bg-[#1a2233]">
                        <td class="px-4 py-3">
                            <p class="font-bold text-white">{{ md.name }}</p>
                            <p class="text-[10px] text-slate-500 font-mono mt-0.5">{{ md.uuid }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-400">
                            {{ md.season }} · {{ md.league_code }}
                        </td>
                        <td class="px-4 py-3 text-slate-400">
                            {{ md.locks_at ? new Date(md.locks_at).toLocaleString() : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-sky-400 font-bold">
                            {{ md.entries_count }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-slate-400">
                            {{ md.legs_count }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="uppercase text-[10px] font-bold"
                                :class="{
                                    'text-emerald-400': md.status === 'settled',
                                    'text-amber-400': md.status === 'open' || md.status === 'underway',
                                    'text-rose-400': md.status === 'cancelled',
                                    'text-slate-400': md.status === 'locked'
                                }">
                                {{ md.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/admin/matchdays/${md.id}`" class="text-[10px] font-black text-sky-400 uppercase hover:underline">Manage →</Link>
                        </td>
                    </tr>
                    <tr v-if="matchdays.data.length === 0">
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500">No matchdays found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex items-center justify-between" v-if="matchdays.links.length > 3">
            <span class="text-xs text-slate-500">Showing {{ matchdays.from }} - {{ matchdays.to }} of {{ matchdays.total }}</span>
            <div class="flex gap-1">
                <template v-for="link in matchdays.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" v-html="link.label"
                        :class="['rounded px-3 py-1 text-xs font-black uppercase tracking-wider', link.active ? 'bg-sky-500 text-black' : 'bg-[#161c2a] text-slate-400 hover:bg-[#1a2233]']" />
                    <span v-else v-html="link.label" class="rounded bg-[#0f1422] px-3 py-1 text-xs text-slate-600" />
                </template>
            </div>
        </div>
    </div>
</template>