<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    betslips: Object,
    summary: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const sellerId = ref(props.filters.seller_id || '');
const status = ref(props.filters.status || '');
const result = ref(props.filters.result || '');
const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');
const minOdds = ref(props.filters.min_odds || '');
const maxOdds = ref(props.filters.max_odds || '');
const minPurchases = ref(props.filters.min_purchases || '');
const maxPurchases = ref(props.filters.max_purchases || '');
const flagged = ref(props.filters.flagged === '1' || props.filters.flagged === true);
const sort = ref(props.filters.sort || 'newest');

const apply = () => {
    router.get('/admin/betslips', {
        search: search.value || undefined,
        seller_id: sellerId.value || undefined,
        status: status.value || undefined,
        result: result.value || undefined,
        from: from.value || undefined,
        to: to.value || undefined,
        min_odds: minOdds.value || undefined,
        max_odds: maxOdds.value || undefined,
        min_purchases: minPurchases.value || undefined,
        max_purchases: maxPurchases.value || undefined,
        flagged: flagged.value ? 1 : undefined,
        sort: sort.value !== 'newest' ? sort.value : undefined,
    }, { preserveState: true, replace: true });
};

const formatKES = (n: number) => 'KES ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 });
</script>

<template>
    <Head title="Betslips — Admin" />

    <div>
        <div class="mb-6 border-b border-[#232d42] pb-6">
            <h1 class="text-2xl font-black tracking-wider text-white uppercase">Betslips</h1>
            <p class="mt-1 text-xs text-slate-500">Marketplace oversight and financial tracking</p>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-4 space-y-4">
            <!-- Row 1: Search & Primary -->
            <div class="flex flex-wrap gap-3 items-center">
                <input v-model="search" @keyup.enter="apply" type="text" placeholder="Search code or seller…"
                    class="flex-1 min-w-[240px] rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2 text-sm text-white placeholder-slate-600 focus:outline-none" />
                
                <input v-model="sellerId" @keyup.enter="apply" type="number" placeholder="Seller ID"
                    class="w-32 rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2 text-sm text-white placeholder-slate-600 focus:outline-none" />

                <select v-model="status" @change="apply"
                    class="rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2 text-sm text-white focus:outline-none">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="underway">Underway</option>
                    <option value="settled">Settled</option>
                    <option value="voided">Voided</option>
                </select>

                <select v-model="result" @change="apply"
                    class="rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2 text-sm text-white focus:outline-none">
                    <option value="">All Results</option>
                    <option value="won">Won</option>
                    <option value="lost">Lost</option>
                    <option value="voided">Voided</option>
                </select>

                <select v-model="sort" @change="apply"
                    class="rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2 text-sm text-white focus:outline-none">
                    <option value="newest">Newest First</option>
                    <option value="odds_desc">Highest Odds</option>
                    <option value="price_desc">Highest Price</option>
                    <option value="sales_desc">Most Sold</option>
                    <option value="volume_desc">Highest Volume</option>
                    <option value="earned_desc">Most Earned</option>
                </select>
            </div>

            <!-- Row 2: Numbers & Dates -->
            <div class="flex flex-wrap gap-3 items-center">
                <div class="flex items-center gap-2 rounded-lg border border-[#232d42] bg-[#0f1422] px-3 py-1.5">
                    <span class="text-[10px] uppercase tracking-widest text-slate-500">Odds</span>
                    <input v-model="minOdds" @keyup.enter="apply" type="number" step="0.1" placeholder="Min" class="w-16 bg-transparent text-sm text-white placeholder-slate-600 focus:outline-none" />
                    <span class="text-slate-500">-</span>
                    <input v-model="maxOdds" @keyup.enter="apply" type="number" step="0.1" placeholder="Max" class="w-16 bg-transparent text-sm text-white placeholder-slate-600 focus:outline-none" />
                </div>

                <div class="flex items-center gap-2 rounded-lg border border-[#232d42] bg-[#0f1422] px-3 py-1.5">
                    <span class="text-[10px] uppercase tracking-widest text-slate-500">Purchases</span>
                    <input v-model="minPurchases" @keyup.enter="apply" type="number" placeholder="Min" class="w-16 bg-transparent text-sm text-white placeholder-slate-600 focus:outline-none" />
                    <span class="text-slate-500">-</span>
                    <input v-model="maxPurchases" @keyup.enter="apply" type="number" placeholder="Max" class="w-16 bg-transparent text-sm text-white placeholder-slate-600 focus:outline-none" />
                </div>

                <div class="flex items-center gap-2 rounded-lg border border-[#232d42] bg-[#0f1422] px-3 py-1.5">
                    <span class="text-[10px] uppercase tracking-widest text-slate-500">Date</span>
                    <input v-model="from" @change="apply" type="date" class="bg-transparent text-sm text-white focus:outline-none [color-scheme:dark]" />
                    <span class="text-slate-500">-</span>
                    <input v-model="to" @change="apply" type="date" class="bg-transparent text-sm text-white focus:outline-none [color-scheme:dark]" />
                </div>

                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2 hover:bg-[#1a2233]">
                    <input type="checkbox" v-model="flagged" @change="apply" class="accent-rose-500" />
                    <span class="text-xs font-bold text-rose-400 uppercase tracking-widest">Flagged</span>
                </label>

                <button @click="apply" class="ml-auto rounded-lg bg-sky-500 px-6 py-2 text-xs font-black tracking-widest text-black uppercase hover:bg-sky-400">
                    Apply
                </button>
            </div>
        </div>

        <!-- Summary Strip -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-3">
                <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">Matches</span>
                <p class="mt-1 text-lg font-black text-white">{{ summary.total_count }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-3">
                <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">Total Volume</span>
                <p class="mt-1 text-lg font-black text-emerald-400">{{ formatKES(summary.total_volume) }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-3">
                <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">Est. Earned</span>
                <p class="mt-1 text-lg font-black text-sky-400">{{ formatKES(summary.total_earned) }}</p>
            </div>
            <div class="rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-3">
                <span class="text-[9px] font-black tracking-widest text-emerald-500 uppercase">Won</span>
                <p class="mt-1 text-lg font-black text-emerald-400">{{ summary.won_count }}</p>
            </div>
            <div class="rounded-lg border border-rose-500/20 bg-rose-500/5 p-3">
                <span class="text-[9px] font-black tracking-widest text-rose-500 uppercase">Lost</span>
                <p class="mt-1 text-lg font-black text-rose-400">{{ summary.lost_count }}</p>
            </div>
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
                        <th class="px-4 py-3 text-right">Sales</th>
                        <th class="px-4 py-3 text-right">Volume</th>
                        <th class="px-4 py-3 text-right">Earned</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#232d42]/60 bg-[#161c2a]">
                    <tr v-for="b in betslips.data" :key="b.id" class="hover:bg-[#1a2233]">
                        <td class="px-4 py-2.5 font-mono text-[10px] text-sky-400">{{ b.code }}</td>
                        <td class="px-4 py-2.5 text-slate-300">{{ b.seller?.name || '(deleted)' }}</td>
                        <td class="px-4 py-2.5 text-slate-400">{{ b.legs_count }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-amber-400">{{ Number(b.total_odds).toFixed(2) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-white">{{ Number(b.price).toFixed(2) }}</td>
                        <td class="px-4 py-2.5 text-right text-slate-400">{{ b.purchases_count }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-emerald-400 font-bold">{{ formatKES(b.sales_value) }}</td>
                        <td class="px-4 py-2.5 text-right font-mono font-bold"
                            :class="b.status === 'settled' && !b.is_winner ? 'text-slate-600' : 'text-emerald-400'">
                            {{ b.status === 'settled' && !b.is_winner ? '—' : formatKES(b.platform_earned) }}
                        </td>
                        <td class="px-4 py-2.5 text-[10px] uppercase font-bold">
                            <span v-if="b.status === 'settled' && b.is_winner" class="text-emerald-400">Won</span>
                            <span v-else-if="b.status === 'settled' && !b.is_winner" class="text-rose-400">Lost</span>
                            <span v-else class="text-slate-400">{{ b.status }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <Link :href="`/admin/betslips/${b.id}`"
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">View →</Link>
                        </td>
                    </tr>
                    <tr v-if="betslips.data.length === 0">
                        <td colspan="10" class="px-4 py-12 text-center text-slate-500">No betslips match these filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex items-center justify-between" v-if="betslips.links.length > 3">
            <span class="text-xs text-slate-500">Showing {{ betslips.from }} - {{ betslips.to }} of {{ betslips.total }}</span>
            <div class="flex gap-1">
                <template v-for="link in betslips.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" v-html="link.label"
                        :class="['rounded px-3 py-1 text-xs font-black uppercase tracking-wider', link.active ? 'bg-sky-500 text-black' : 'bg-[#161c2a] text-slate-400 hover:bg-[#1a2233]']" />
                    <span v-else v-html="link.label" class="rounded bg-[#0f1422] px-3 py-1 text-xs text-slate-600" />
                </template>
            </div>
        </div>
    </div>
</template>