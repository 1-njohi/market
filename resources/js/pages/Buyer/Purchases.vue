<template>
    <Head title="Purchases — Fainali" />

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
            <EmptyState
                v-if="purchases.data.length === 0"
                :title="emptyTitle"
                :body="emptyBody"
                :cta-label="emptyCtaLabel"
                :cta-href="emptyCtaHref"
                accent="emerald"
            />
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
import EmptyState from '@/components/EmptyState.vue';

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

const filterActive = computed(() => statusFilter.value !== null);

const emptyTitle = computed(() =>
    filterActive.value
        ? `No ${statusFilter.value} purchases`
        : 'No purchases yet',
);

const emptyBody = computed(() =>
    filterActive.value
        ? 'Try a different filter, or clear it to see all your purchases.'
        : 'Buy your first betslip and it will appear here — tracked through to settlement.',
);

const emptyCtaLabel = computed(() =>
    filterActive.value ? 'Clear filter' : 'Browse marketplace',
);

const emptyCtaHref = computed(() =>
    filterActive.value ? '/buyer/purchases' : '/marketplace',
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