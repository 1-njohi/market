<template>
    <Head title="Purchases — Betslip Pirates" />

    <ListPage
        label="Buyer"
        title="All Purchases"
        subtitle="Every betslip you've bought, from pending through to settlement."
        back-href="/buyer/dashboard#purchases"
        back-label="Buyer Dashboard"
    >
        <template #filters>
            <ListFilters
                v-model="statusFilter"
                label="Status"
                :options="statusOptions"
                @update:model-value="applyFilter"
            />
        </template>

        <div
            class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
        >
            <div
                v-if="purchases.data.length === 0"
                class="p-10 text-center font-mono text-xs tracking-wider text-slate-500"
            >
                No purchases to display.
            </div>
            <BetslipsTable v-else :betslips="purchasesForTable" />
        </div>

        <template #pagination>
            <ListPagination :paginator="purchases" />
        </template>
    </ListPage>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ListPage from '@/components/ListPage.vue';
import ListFilters from '@/components/ListFilters.vue';
import ListPagination from '@/components/ListPagination.vue';
import BetslipsTable from '@/components/BetslipsTable.vue';

const props = defineProps({
    purchases: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const statusOptions = [
    { value: null,       label: 'All' },
    { value: 'active',   label: 'Active' },
    { value: 'settled',  label: 'Settled' },
    { value: 'won',      label: 'Won' },
    { value: 'refunded', label: 'Refunded' },
    { value: 'voided',   label: 'Voided' },
];

const statusFilter = ref(props.filters.status ?? null);

const purchasesForTable = computed(() =>
    (props.purchases.data || []).map((p) => ({
        id: p.id,
        code: p.betslip_code,
        legs: p.legs ?? 0,
        total_odds: p.total_odds,
        price: p.price,
        remaining: 1,
        status: p.status,
        is_winner: p.is_winner,
        purchases: 1,
        is_expiring_soon: false,
        seller_name: p.seller_name,
    })),
);

const applyFilter = (value) => {
    router.get(
        '/buyer/purchases',
        value ? { status: value } : {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(
    () => props.filters.status,
    (v) => {
        statusFilter.value = v ?? null;
    },
);
</script>