<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    betslips: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const flagged = ref(props.filters.flagged === '1' || props.filters.flagged === true);

const apply = () => {
    router.get('/admin/betslips', {
        search: search.value || undefined,
        status: status.value || undefined,
        flagged: flagged.value ? 1 : undefined,
    }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Betslips — Admin" />

    <div>
        <div class="mb-6 border-b border-[#232d42] pb-6">
            <h1 class="text-2xl font-black tracking-wider text-white uppercase">Betslips</h1>
            <p class="mt-1 text-xs text-slate-500">Marketplace-wide betslip oversight</p>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-3">
            <input v-model="search" @keyup.enter="apply" type="text" placeholder="Search code or seller…"
                class="flex-1 min-w-[240px] rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none" />
            <select v-model="status" @change="apply"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white focus:outline-none">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="underway">Underway</option>
                <option value="settled">Settled</option>
                <option value="voided">Voided</option>
                <option value="sold_out">Sold out</option>
            </select>
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5">
                <input type="checkbox" v-model="flagged" @change="apply" class="accent-rose-500" />
                <span class="text-xs font-bold text-rose-400 uppercase tracking-widest">Flagged only</span>
            </label>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-[#232d42]">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Seller</th>
                        <th class="px-4 py-3">Legs</th>
                        <th class="px-4 py-3 text-right">Odds</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Sales</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#232d42]/60 bg-[#161c2a]">
                    <tr v-for="b in betslips.data" :key="b.id" class="hover:bg-[#1a2233]">
                        <td class="px-4 py-2.5 font-mono text-[10px] text-sky-400">{{ b.code }}</td>
                        <td class="px-4 py-2.5 text-slate-300">{{ b.seller?.name }}</td>
                        <td class="px-4 py-2.5 text-slate-400">{{ b.legs_count }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-amber-400">{{ Number(b.total_odds).toFixed(2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-emerald-400">{{ Number(b.price).toFixed(2) }}</td>
                        <td class="px-4 py-2.5 text-slate-400 uppercase text-[10px]">{{ b.status }}</td>
                        <td class="px-4 py-2.5 text-right text-slate-400">{{ b.purchases_count }}</td>
                        <td class="px-4 py-2.5 text-right">
                            <Link :href="`/admin/betslips/${b.id}`"
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">View →</Link>
                        </td>
                    </tr>
                    <tr v-if="betslips.data.length === 0">
                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">No betslips match these filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>