<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    transactions: Object,
    types: Array,
    counts: Object,
    filters: Object,
});

const direction = ref(props.filters.direction || '');
const type = ref(props.filters.type || '');
const search = ref(props.filters.search || '');
const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');

const apply = () => {
    router.get('/admin/transactions', {
        direction: direction.value || undefined,
        type: type.value || undefined,
        search: search.value || undefined,
        from: from.value || undefined,
        to: to.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const tabs = [
    { value: '',            label: 'All' },
    { value: 'credits',     label: 'Credits' },
    { value: 'debits',      label: 'Debits' },
    { value: 'adjustments', label: 'Adjustments' },
];

const setDirection = (value: string) => {
    if (direction.value === value) return;
    direction.value = value;
    apply();
};

const exportCsv = () => {
    const params = new URLSearchParams();
    if (direction.value) params.append('direction', direction.value);
    if (type.value) params.append('type', type.value);
    if (search.value) params.append('search', search.value);
    if (from.value) params.append('from', from.value);
    if (to.value) params.append('to', to.value);
    window.location.href = `/admin/transactions/export?${params.toString()}`;
};

const fmt = (n: number) => 'KES ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 2 });
const fmtCompact = (n: number) => {
    const v = Number(n || 0);
    if (Math.abs(v) >= 1_000_000) return 'KES ' + (v / 1_000_000).toFixed(2) + 'M';
    if (Math.abs(v) >= 1_000)     return 'KES ' + (v / 1_000).toFixed(1) + 'K';
    return 'KES ' + v.toFixed(0);
};
</script>

<template>
    <Head title="Transactions — Admin" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-[#232d42] pb-6">
            <div>
                <h1 class="text-2xl font-black tracking-wider text-white uppercase">Transactions</h1>
                <p class="mt-1 text-xs text-slate-500">Full ledger, filterable and exportable</p>
            </div>
            <button @click="exportCsv"
                class="rounded-lg border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase hover:bg-sky-500/20">
                Export CSV
            </button>
        </div>

        <!-- Stat cards -->
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total volume</span>
                <p class="mt-1 text-2xl font-black text-white">{{ fmtCompact(counts.total_volume) }}</p>
                <p class="mt-0.5 text-[10px] text-slate-500">{{ counts.total_count.toLocaleString() }} transactions</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Credits</span>
                <p class="mt-1 text-2xl font-black text-emerald-400">{{ fmtCompact(counts.credit_volume) }}</p>
                <p class="mt-0.5 text-[10px] text-slate-500">{{ counts.credit_count.toLocaleString() }} entries</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Debits</span>
                <p class="mt-1 text-2xl font-black text-rose-400">{{ fmtCompact(counts.debit_volume) }}</p>
                <p class="mt-0.5 text-[10px] text-slate-500">{{ counts.debit_count.toLocaleString() }} entries</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Net flow</span>
                <p class="mt-1 text-2xl font-black"
                    :class="counts.net >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ counts.net >= 0 ? '+' : '−' }}{{ fmtCompact(Math.abs(counts.net)) }}
                </p>
                <p class="mt-0.5 text-[10px] text-slate-500">Credits − Debits</p>
            </div>
        </div>

        <!-- Direction tabs -->
        <div class="mb-5 flex flex-wrap items-center gap-1 border-b border-[#232d42]">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                @click="setDirection(tab.value)"
                :class="[
                    'relative px-4 py-2 text-[11px] font-black tracking-widest uppercase transition-colors',
                    direction === tab.value
                        ? 'text-sky-400'
                        : 'text-slate-500 hover:text-slate-300',
                ]"
            >
                {{ tab.label }}
                <span
                    v-if="direction === tab.value"
                    class="absolute inset-x-0 -bottom-px h-0.5 bg-sky-400"
                ></span>
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-4">
            <select v-model="type" @change="apply"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white focus:outline-none">
                <option value="">All types</option>
                <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
            </select>
            <input v-model="search" @keyup.enter="apply" type="text" placeholder="Search reference, description, user…"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none sm:col-span-2" />
            <div class="flex gap-2">
                <input v-model="from" @change="apply" type="date"
                    class="flex-1 rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white focus:outline-none" />
                <input v-model="to" @change="apply" type="date"
                    class="flex-1 rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white focus:outline-none" />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-[#232d42]">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3 text-right">Balance After</th>
                        <th class="px-4 py-3">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#232d42]/60 bg-[#161c2a]">
                    <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-[#1a2233]">
                        <td class="px-4 py-2.5 text-slate-500 whitespace-nowrap">{{ new Date(tx.created_at).toLocaleString() }}</td>
                        <td class="px-4 py-2.5">
                            <div class="text-slate-300">{{ tx.user?.name }}</div>
                            <div class="font-mono text-[10px] text-slate-500">{{ tx.user?.code }}</div>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="font-mono text-[10px] uppercase text-sky-400">{{ tx.type }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono font-bold"
                            :class="Number(tx.amount) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                            {{ Number(tx.amount) >= 0 ? '+' : '' }}{{ Number(tx.amount).toFixed(2) }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-mono text-slate-400">{{ Number(tx.balance_after).toFixed(2) }}</td>
                        <td class="px-4 py-2.5 font-mono text-[10px] text-slate-500">{{ tx.reference }}</td>
                    </tr>
                    <tr v-if="transactions.data.length === 0">
                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">No transactions match these filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.last_page > 1" class="mt-4 flex items-center justify-between text-xs">
            <span class="text-slate-500">Page {{ transactions.current_page }} of {{ transactions.last_page }}</span>
            <div class="flex gap-2">
                <a v-if="transactions.prev_page_url" :href="transactions.prev_page_url"
                    class="rounded border border-[#232d42] px-3 py-1.5 text-slate-300 hover:text-sky-400">← Prev</a>
                <a v-if="transactions.next_page_url" :href="transactions.next_page_url"
                    class="rounded border border-[#232d42] px-3 py-1.5 text-slate-300 hover:text-sky-400">Next →</a>
            </div>
        </div>
    </div>
</template>