<template>
    <Head title="Transactions — Betslip Pirates" />

    <ListPage
        label="Wallet"
        title="All Transactions"
        subtitle="Every deposit, purchase, refund, payout, and fee on your account. Newest first."
        back-href="/dashboard"
        back-label="Dashboard"
    >
        <template #filters>
            <ListFilters
                v-model="typeFilter"
                label="Type"
                :options="typeOptions"
                @update:model-value="applyFilter"
            />
        </template>

        <div
            class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
        >
            <div
                v-if="transactions.data.length === 0"
                class="p-10 text-center font-mono text-xs tracking-wider text-slate-500"
            >
                No transactions to display.
            </div>
            <div v-else>
                <TransactionsTable :transactions="transactions.data" />
            </div>
        </div>

        <template #pagination>
            <ListPagination :paginator="transactions" />
        </template>
    </ListPage>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ListPage from '@/components/ListPage.vue';
import ListFilters from '@/components/ListFilters.vue';
import ListPagination from '@/components/ListPagination.vue';
import TransactionsTable from '@/components/TransactionsTable.vue';

const props = defineProps({
    transactions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const typeOptions = [
    { value: null,           label: 'All' },
    { value: 'deposit',      label: 'Deposits' },
    { value: 'withdrawal',   label: 'Withdrawals' },
    { value: 'purchase',     label: 'Purchases' },
    { value: 'refund',       label: 'Refunds' },
    { value: 'payout',       label: 'Payouts' },
    { value: 'fee',          label: 'Fees' },
];

const typeFilter = ref(props.filters.type ?? null);

const applyFilter = (value) => {
    router.get(
        '/wallet/transactions',
        value ? { type: value } : {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// Keep the ref in sync when the server echoes back a filter (e.g. on
// a fresh page load from a bookmarked URL).
watch(
    () => props.filters.type,
    (v) => {
        typeFilter.value = v ?? null;
    },
);
</script>