<template>
    <Head title="Betslips — Betslip Pirates" />

    <ListPage
        label="Seller"
        title="All Betslips"
        subtitle="Every betslip you've created — active, and settled."
        back-href="/seller/dashboard#betslips"
        back-label="Seller Dashboard"
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
                v-if="betslips.data.length === 0"
                :title="emptyTitle"
                :body="emptyBody"
                :cta-label="emptyCtaLabel"
                :cta-href="emptyCtaHref"
                accent="sky"
            />
            <BetslipsTable v-else :betslips="betslipsForTable" />
        </div>

        <template #pagination>
            <ListPagination :paginator="betslips" />
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
    betslips: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const statusOptions = [
    { value: null,       label: 'All' },
    { value: 'active',   label: 'Active' },
    { value: 'settled',  label: 'Settled' },
];

const statusFilter = ref(props.filters.status ?? null);

const betslipsForTable = computed(() =>
    (props.betslips.data || []).map((b) => ({
        id: b.id,
        code: b.code,
        legs: b.legs ?? 0,
        total_odds: b.total_odds,
        price: b.price,
        remaining: b.remaining,
        status: b.status,
        is_winner: b.is_winner,
        purchases: b.purchases ?? 0,
        is_expiring_soon: b.is_expiring_soon ?? false,
        watch_count: b.watch_count ?? 0,
    })),
);

const filterActive = computed(() => statusFilter.value !== null);

const emptyTitle = computed(() =>
    filterActive.value
        ? `No ${statusFilter.value.replace('_', ' ')} betslips`
        : 'No betslips yet',
);

const emptyBody = computed(() =>
    filterActive.value
        ? 'Try a different filter, or clear it to see all your betslips.'
        : 'Create your first betslip to start selling picks to followers.',
);

const emptyCtaLabel = computed(() =>
    filterActive.value ? 'Clear filter' : 'Create a betslip',
);

const emptyCtaHref = computed(() =>
    filterActive.value ? '/seller/betslips' : '/fixtures',
);

const applyFilter = (value) => {
    router.get(
        '/seller/betslips',
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