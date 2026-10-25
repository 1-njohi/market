<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    transactions: Object,
    summary: Object,
    types: Array,
    filters: Object,
});

const direction = ref(props.filters.direction || '');
const type = ref(props.filters.type || '');
const search = ref(props.filters.search || '');
const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');

const apply = () => {
    router.get(
        '/admin/transactions',
        {
            direction: direction.value || undefined,
            type: type.value || undefined,
            search: search.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const tabs = [
    { value: '',            label: 'All Activity' },
    { value: 'deposits',    label: 'Deposits',     color: 'emerald' },
    { value: 'withdrawals', label: 'Withdrawals',  color: 'rose' },
    { value: 'trading',     label: 'Trading',      color: 'sky' },
    { value: 'fees',        label: 'Platform Fees',color: 'amber' },
    { value: 'seller_fees', label: 'Seller Fees',  color: 'slate' },
    { value: 'admin',       label: 'Admin',        color: 'purple' },
];

const setDirection = (value: string) => {
    if (direction.value === value) return;
    direction.value = value;
    apply();
};

const clearFilters = () => {
    search.value = '';
    type.value = '';
    from.value = '';
    to.value = '';
    direction.value = '';
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

const fmt = (n: number) =>
    'KES ' +
    Number(n || 0).toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const fmtSigned = (n: number) =>
    (Number(n) >= 0 ? '+' : '−') +
    'KES ' +
    Number(Math.abs(n)).toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const groupedByDate = computed(() => {
    const groups: Record<string, any[]> = {};
    for (const tx of props.transactions.data) {
        const key = tx.created_date;
        if (!groups[key]) groups[key] = [];
        groups[key].push(tx);
    }
    return groups;
});

const dateLabel = (dateStr: string) => {
    const d = new Date(dateStr);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const txDate = new Date(dateStr);
    txDate.setHours(0, 0, 0, 0);

    if (txDate.getTime() === today.getTime()) return 'Today';
    if (txDate.getTime() === yesterday.getTime()) return 'Yesterday';

    return d.toLocaleDateString('en-KE', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const typeBadge = (type: string) => {
    const map: Record<string, string> = {
        deposit: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400',
        withdrawal: 'border-rose-500/20 bg-rose-500/10 text-rose-400',
        betslip_purchase: 'border-sky-500/20 bg-sky-500/10 text-sky-400',
        betslip_sale: 'border-sky-500/20 bg-sky-500/10 text-sky-400',
        purchase: 'border-sky-500/20 bg-sky-500/10 text-sky-400',
        sale: 'border-sky-500/20 bg-sky-500/10 text-sky-400',
        refund: 'border-amber-500/20 bg-amber-500/10 text-amber-400',
        payout: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400',
        fee: 'border-amber-500/20 bg-amber-500/10 text-amber-400',
        platform_fee: 'border-amber-500/20 bg-amber-500/10 text-amber-400',
        admin_credit: 'border-purple-500/20 bg-purple-500/10 text-purple-400',
        admin_debit: 'border-purple-500/20 bg-purple-500/10 text-purple-400',
    };
    return map[type] ?? 'border-slate-500/20 bg-slate-500/10 text-slate-400';
};
</script>

<template>
    <Head title="Transactions — Admin" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-[#232d42] pb-6">
            <div>
                <h1 class="text-2xl font-black tracking-wider text-white uppercase">Transactions</h1>
                <p class="mt-1 text-xs text-slate-500">Complete money trail across the platform</p>
            </div>
            <button @click="exportCsv"
                class="rounded-lg border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase hover:bg-sky-500/20">
                Export CSV
            </button>
        </div>

        <!-- Summary: exact numbers, no abbreviations -->
        <div class="mb-6 grid grid-cols-1 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Money In</span>
                <p class="mt-2 font-mono text-xl font-black text-emerald-400">
                    {{ fmt(summary.total_credit) }}
                </p>
                <p class="mt-1 text-[10px] text-slate-500">
                    {{ summary.total_count.toLocaleString() }} transactions
                </p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Money Out</span>
                <p class="mt-2 font-mono text-xl font-black text-rose-400">
                    {{ fmt(summary.total_debit) }}
                </p>
                <p class="mt-1 text-[10px] text-slate-500">Withdrawals, purchases, refunds</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Net Flow</span>
                <p class="mt-2 font-mono text-xl font-black"
                    :class="summary.net_flow >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ fmtSigned(summary.net_flow) }}
                </p>
                <p class="mt-1 text-[10px] text-slate-500">In minus Out</p>
            </div>
            <div class="rounded-lg border border-amber-500/30 bg-amber-500/5 p-4">
                <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Platform Revenue</span>
                <p class="mt-2 font-mono text-xl font-black text-amber-400">
                    {{ fmt(summary.groups.fees.credit) }}
                </p>
                <p class="mt-1 text-[10px] text-slate-500">
                    {{ summary.groups.fees.count.toLocaleString() }} fee entries
                </p>
            </div>
        </div>

        <!-- Group breakdown strip (excluding seller_fees to save space) -->
        <div class="mb-6 grid grid-cols-2 gap-2 md:grid-cols-5">
            <div v-for="(group, key) in summary.groups" :key="key" v-show="key !== 'seller_fees'"
                class="rounded border border-[#232d42] bg-[#0f1422] p-3">
                <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">{{ group.label }}</span>
                <p class="mt-1 font-mono text-sm font-black text-white">
                    {{ fmt(group.net) }} 
                </p>
                <p class="mt-0.5 text-[9px] text-slate-500">{{ group.count }} entries</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-5 flex flex-wrap items-center gap-1 border-b border-[#232d42]">
            <button v-for="tab in tabs" :key="tab.value" type="button" @click="setDirection(tab.value)"
                :class="[
                    'relative px-4 py-2 text-[11px] font-black tracking-widest uppercase transition-colors',
                    direction === tab.value
                        ? 'text-sky-400'
                        : 'text-slate-500 hover:text-slate-300',
                ]">
                {{ tab.label }}
                <span v-if="direction === tab.value"
                    class="absolute inset-x-0 -bottom-px h-0.5 bg-sky-400"></span>
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-2">
            <select v-model="type" @change="apply"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-3 py-2 text-xs text-white focus:outline-none">
                <option value="">All types</option>
                <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
            </select>
            <input v-model="search" @keyup.enter="apply" type="text"
                placeholder="Search reference, description, user…"
                class="flex-1 min-w-[240px] rounded-lg border border-[#232d42] bg-[#161c2a] px-3 py-2 text-xs text-white placeholder-slate-600 focus:outline-none" />
            <input v-model="from" @change="apply" type="date"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-3 py-2 text-xs text-white focus:outline-none [color-scheme:dark]" />
            <input v-model="to" @change="apply" type="date"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-3 py-2 text-xs text-white focus:outline-none [color-scheme:dark]" />
            <button @click="clearFilters"
                class="rounded-lg border border-[#232d42] px-3 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase hover:text-slate-200">
                Reset
            </button>
        </div>

        <!-- Ledger grouped by day -->
        <div class="space-y-4">
            <div v-for="(rows, date) in groupedByDate" :key="date"
                class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <div class="flex items-center justify-between border-b border-[#232d42] bg-[#0f1422] px-4 py-2">
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        {{ dateLabel(date) }}
                    </span>
                    <span class="text-[10px] text-slate-500">
                        {{ rows.length }} transaction{{ rows.length === 1 ? '' : 's' }}
                    </span>
                </div>

                <ul class="divide-y divide-[#232d42]/60">
                    <li v-for="tx in rows" :key="tx.id"
                        class="grid grid-cols-1 gap-3 px-4 py-3 md:grid-cols-[auto_1fr_auto_auto] md:items-center">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-[11px] text-slate-500">{{ tx.created_time }}</span>
                            <span class="rounded border px-1.5 py-0.5 text-[9px] font-black tracking-widest uppercase"
                                :class="typeBadge(tx.type)">
                                {{ tx.type_label }}
                            </span>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-200">
                                {{ tx.user?.name || '(system)' }}
                            </p>
                            <p class="truncate text-[10px] text-slate-500">
                                {{ tx.description || tx.reference || '—' }}
                            </p>
                        </div>

                        <div class="text-right text-[10px] font-mono text-slate-500">
                            <p>{{ fmt(tx.balance_before) }}</p>
                            <p class="text-slate-600">↓</p>
                            <p class="text-slate-400">{{ fmt(tx.balance_after) }}</p>
                        </div>

                        <div class="text-right md:min-w-[140px]">
                            <p class="font-mono text-sm font-black"
                                :class="tx.amount >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                {{ fmtSigned(tx.amount) }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>

            <div v-if="transactions.data.length === 0"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-6 py-12 text-center">
                <p class="text-xs text-slate-500">No transactions match these filters.</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.last_page > 1" class="mt-6 flex items-center justify-between text-xs">
            <span class="text-slate-500">
                Page {{ transactions.current_page }} of {{ transactions.last_page }} ·
                {{ transactions.total.toLocaleString() }} total
            </span>
            <div class="flex gap-2">
                <a v-if="transactions.prev_page_url" :href="transactions.prev_page_url"
                    class="rounded border border-[#232d42] px-3 py-1.5 text-slate-300 hover:text-sky-400">
                    ← Prev
                </a>
                <a v-if="transactions.next_page_url" :href="transactions.next_page_url"
                    class="rounded border border-[#232d42] px-3 py-1.5 text-slate-300 hover:text-sky-400">
                    Next →
                </a>
            </div>
        </div>
    </div>
</template>