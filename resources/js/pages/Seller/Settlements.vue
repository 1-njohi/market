<template>
    <Head title="Settlements — Betslip Pirates" />

    <ListPage
        label="Seller"
        title="All Settlements"
        subtitle="Every settled sale — wins, refunds, and voids — with the payout and fee breakdown."
        back-href="/seller/dashboard#betslips"
        back-label="Seller Dashboard"
    >
        <template #filters>
            <ListFilters
                v-model="outcomeFilter"
                label="Outcome"
                :options="outcomeOptions"
                @update:model-value="applyFilter"
            />
        </template>

        <div
            class="divide-y divide-gray-800/40 overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
        >
            <EmptyState
                v-if="settlements.data.length === 0"
                :title="emptyTitle"
                :body="emptyBody"
                :cta-label="emptyCtaLabel"
                :cta-href="emptyCtaHref"
                accent="emerald"
            />
            <template v-else>
                <SettlementRow
                    v-for="s in settlements.data"
                    :key="s.id"
                    :settlement="s"
                />
            </template>
        </div>

        <template #pagination>
            <ListPagination :paginator="settlements" />
        </template>
    </ListPage>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ListPage from '@/components/ListPage.vue';
import ListFilters from '@/components/ListFilters.vue';
import ListPagination from '@/components/ListPagination.vue';
import SettlementRow from '@/components/SettlementRow.vue';
import EmptyState from '@/components/EmptyState.vue';

const props = defineProps({
    settlements: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const outcomeOptions = [
    { value: null,       label: 'All' },
    { value: 'won',      label: 'Won' },
    { value: 'refunded', label: 'Refunded' },
    { value: 'voided',   label: 'Voided' },
];

const outcomeFilter = ref(props.filters.outcome ?? null);

const filterActive = computed(() => outcomeFilter.value !== null);

const emptyTitle = computed(() =>
    filterActive.value
        ? `No ${outcomeFilter.value} settlements`
        : 'No settlements yet',
);

const emptyBody = computed(() =>
    filterActive.value
        ? 'Try a different outcome filter, or clear it to see everything.'
        : 'When a buyer purchase of one of your betslips reaches settlement, the payout and fee breakdown lands here.',
);

const emptyCtaLabel = computed(() =>
    filterActive.value ? 'Clear filter' : 'Create a betslip',
);

const emptyCtaHref = computed(() =>
    filterActive.value ? '/seller/settlements' : '/fixtures',
);

const applyFilter = (value) => {
    router.get(
        '/seller/settlements',
        value ? { outcome: value } : {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(
    () => props.filters.outcome,
    (v) => {
        outcomeFilter.value = v ?? null;
    },
);
</script>