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
            <EmptyState
                v-if="transactions.data.length === 0"
                :title="emptyTitle"
                :body="emptyBody"
                :cta-label="emptyCtaLabel"
                :cta-href="emptyCtaHref"
                accent="emerald"
            />
            <TransactionsTable v-else :transactions="transactions.data" />
        </div>

        <template #pagination>
            <ListPagination :paginator="transactions" />
        </template>
    </ListPage>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ListPage from '@/components/ListPage.vue';
import ListFilters from '@/components/ListFilters.vue';
import ListPagination from '@/components/ListPagination.vue';
import TransactionsTable from '@/components/TransactionsTable.vue';
import EmptyState from '@/components/EmptyState.vue';

const props = defineProps({
    transactions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const typeOptions = [
    { value: null,         label: 'All' },
    { value: 'deposit',    label: 'Deposits' },
    { value: 'withdrawal', label: 'Withdrawals' },
    { value: 'purchase',   label: 'Purchases' },
    { value: 'refund',     label: 'Refunds' },
    { value: 'payout',     label: 'Payouts' },
    { value: 'fee',        label: 'Fees' },
];

const typeFilter = ref(props.filters.type ?? null);

const filterActive = computed(() => typeFilter.value !== null);

const emptyTitle = computed(() =>
    filterActive.value
        ? `No ${typeFilter.value} transactions`
        : 'No transactions yet',
);

const emptyBody = computed(() =>
    filterActive.value
        ? 'Try a different filter, or clear it to see everything.'
        : 'Fund your wallet to see deposits, purchases, refunds, and payouts appear here.',
);

const emptyCtaLabel = computed(() =>
    filterActive.value ? 'Clear filter' : 'Fund your wallet',
);

const emptyCtaHref = computed(() =>
    filterActive.value ? '/wallet/transactions' : '/dashboard#wallet',
);

const applyFilter = (value) => {
    router.get(
        '/wallet/transactions',
        value ? { type: value } : {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(
    () => props.filters.type,
    (v) => {
        typeFilter.value = v ?? null;
    },
);
</script>