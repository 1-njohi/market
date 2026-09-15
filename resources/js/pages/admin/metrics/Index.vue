<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    metrics: { type: Object, required: true },
    windows: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const setWindow = (days: number) => {
    if (days === props.filters.days) return;
    router.get('/admin/metrics', { days }, { preserveState: true, replace: true });
};

const refresh = () => {
    router.post('/admin/metrics/refresh', {}, { preserveScroll: true });
};

/**
 * Display config per metric. `higherIsBetter` flips the color logic —
 * for median time to first sale, a *decrease* is good.
 */
const cards = computed(() => {
    const m = props.metrics;

    return [
        {
            key: 'unlock_rate',
            label: 'Slip Unlock Rate',
            value: m.unlock_rate.value,
            suffix: '%',
            delta: m.unlock_rate.delta,
            higherIsBetter: true,
            empty: m.unlock_rate.listed === 0,
            context: `${m.unlock_rate.unlocked} of ${m.unlock_rate.listed} slips listed got a buyer`,
        },
        {
            key: 'buyer_repeat_rate',
            label: 'Buyer Repeat Rate',
            value: m.buyer_repeat_rate.value,
            suffix: '%',
            delta: m.buyer_repeat_rate.delta,
            higherIsBetter: true,
            empty: m.buyer_repeat_rate.total_buyers === 0,
            context: `${m.buyer_repeat_rate.repeat_buyers} of ${m.buyer_repeat_rate.total_buyers} buyers came back`,
        },
        {
            key: 'seller_activation',
            label: 'Seller Activation',
            value: m.seller_activation.value,
            suffix: '%',
            delta: m.seller_activation.delta,
            higherIsBetter: true,
            empty: m.seller_activation.signups === 0,
            context: `${m.seller_activation.activated} of ${m.seller_activation.signups} signups listed within ${m.seller_activation.window_hours}h`,
        },
        {
            key: 'median_time_to_first_sale',
            label: 'Median Time to First Sale',
            value: m.median_time_to_first_sale.value,
            suffix: 'h',
            delta: m.median_time_to_first_sale.delta,
            higherIsBetter: false,
            empty: m.median_time_to_first_sale.sample_size === 0,
            context: m.median_time_to_first_sale.sample_size > 0
                ? `across ${m.median_time_to_first_sale.sample_size} slips sold`
                : 'no slips sold in this window',
        },
    ];
});

const formatValue = (card: any) => {
    if (card.empty || card.value === null) return '—';
    return `${card.value}${card.suffix}`;
};

const formatDelta = (card: any) => {
    if (card.delta === null) return '—';
    if (card.delta === 0) return '±0';
    const sign = card.delta > 0 ? '+' : '−';
    return `${sign}${Math.abs(card.delta).toFixed(1)}${card.suffix}`;
};

const deltaClass = (card: any) => {
    if (card.delta === null || card.delta === 0) {
        return 'border-slate-500/20 bg-slate-500/10 text-slate-400';
    }
    const isPositive = card.delta > 0;
    const isGood = card.higherIsBetter ? isPositive : !isPositive;
    return isGood
        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
        : 'border-rose-500/20 bg-rose-500/10 text-rose-400';
};

const windowLabel = (days: number) => `Last ${days} days`;
</script>

<template>
    <Head title="Business Metrics — Admin" />

    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-[#232d42] pb-6">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <Link
                        href="/admin"
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase hover:text-slate-300"
                    >
                        ← Operations
                    </Link>
                </div>
                <h1 class="text-2xl font-black tracking-wider text-white uppercase">
                    Business Metrics
                </h1>
                <p class="mt-2 text-xs text-slate-500">
                    Leading indicators · {{ windowLabel(filters.days) }}
                    <span class="text-slate-600"> · </span>
                    updated {{ new Date(metrics.generated_at).toLocaleTimeString() }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Window selector -->
                <div class="flex items-center gap-1 rounded-lg border border-[#232d42] bg-[#0f1422] p-1">
                    <button
                        v-for="w in windows"
                        :key="w"
                        type="button"
                        @click="setWindow(w)"
                        :class="[
                            'rounded px-3 py-1.5 text-[10px] font-black tracking-widest uppercase transition-colors',
                            filters.days === w
                                ? 'bg-sky-500/10 text-sky-400'
                                : 'text-slate-500 hover:text-slate-300',
                        ]"
                    >
                        {{ w }}d
                    </button>
                </div>

                <!-- Refresh -->
                <button
                    type="button"
                    @click="refresh"
                    class="rounded-lg border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/40 hover:text-sky-400"
                >
                    Refresh now
                </button>
            </div>
        </div>

        <!-- Metric cards -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div
                v-for="card in cards"
                :key="card.key"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
            >
                <div class="flex items-start justify-between gap-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        {{ card.label }}
                    </span>
                    <span
                        :class="[
                            'rounded border px-2 py-0.5 text-[10px] font-black tracking-wider uppercase',
                            deltaClass(card),
                        ]"
                    >
                        {{ formatDelta(card) }}
                    </span>
                </div>

                <p class="mt-3 text-3xl font-black text-white">
                    {{ formatValue(card) }}
                </p>

                <p class="mt-2 text-[11px] text-slate-500">
                    {{ card.context }}
                </p>

                <p
                    v-if="!card.empty && card.delta !== null"
                    class="mt-1 text-[10px] text-slate-600"
                >
                    vs previous {{ filters.days }} days
                </p>
            </div>
        </div>

        <!-- Footer note -->
        <div class="mt-8 rounded-lg border border-[#232d42]/60 bg-[#0f1422] p-4">
            <p class="text-[11px] leading-relaxed text-slate-500">
                Metrics are recomputed hourly and cached for one hour.
                <span class="font-black tracking-widest text-slate-400 uppercase">Delta</span>
                compares the current window against the immediately-preceding window
                of the same length — green means movement in the desired direction,
                red means the opposite. Click
                <span class="font-black tracking-widest text-sky-400 uppercase">Refresh now</span>
                to recompute immediately.
            </p>
        </div>
    </div>
</template>