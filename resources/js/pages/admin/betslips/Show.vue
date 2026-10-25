<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    betslip: Object,
    financials: Object,
    legs: Array,
    purchases: Array,
});

const formatKES = (n: number) => 'KES ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 });
</script>

<template>
    <Head :title="`Betslip ${betslip.code} — Admin`" />

    <div class="space-y-8">
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-[#232d42] pb-6">
            <div>
                <Link href="/admin/betslips" class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">← Back to Betslips</Link>
                <h1 class="mt-2 font-mono text-2xl font-black tracking-wider text-white uppercase">{{ betslip.code }}</h1>
                <p class="mt-1 text-xs text-slate-500">
                    Seller: <span class="text-slate-300">{{ betslip.seller.name }}</span> · 
                    Created: {{ new Date(betslip.created_at).toLocaleString() }}
                </p>
            </div>
            <div class="text-right">
                <span v-if="betslip.status === 'settled' && betslip.is_winner"
                    class="rounded bg-emerald-500/20 px-3 py-1 font-mono text-xs font-black text-emerald-400 uppercase tracking-widest border border-emerald-500/30">
                    Won
                </span>
                <span v-else-if="betslip.status === 'settled' && !betslip.is_winner"
                    class="rounded bg-rose-500/20 px-3 py-1 font-mono text-xs font-black text-rose-400 uppercase tracking-widest border border-rose-500/30">
                    Lost
                </span>
                <span v-else 
                    class="rounded bg-[#161c2a] px-3 py-1 font-mono text-xs font-black text-slate-300 uppercase tracking-widest border border-[#232d42]">
                    {{ betslip.status }}
                </span>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Unit Price</span>
                <p class="mt-2 font-mono text-xl font-black text-white">{{ formatKES(betslip.price) }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total Odds</span>
                <p class="mt-2 font-mono text-xl font-black text-amber-400">{{ Number(betslip.total_odds).toFixed(2) }}×</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total Sales Volume</span>
                <p class="mt-2 font-mono text-xl font-black text-white">{{ formatKES(financials.total_sales) }}</p>
            </div>
            
            <!-- Dynamic Fee / Revenue Card -->
            <div class="rounded-lg border p-4"
                :class="{
                    'border-sky-500/30 bg-sky-500/5': financials.fee_color === 'sky',
                    'border-emerald-500/30 bg-emerald-500/5': financials.fee_color === 'emerald',
                    'border-rose-500/30 bg-rose-500/5': financials.fee_color === 'rose',
                    'border-[#232d42] bg-[#161c2a]': financials.fee_color === 'slate',
                }">
                <span class="text-[10px] font-black tracking-widest uppercase"
                    :class="{
                        'text-sky-400': financials.fee_color === 'sky',
                        'text-emerald-400': financials.fee_color === 'emerald',
                        'text-rose-400': financials.fee_color === 'rose',
                        'text-slate-500': financials.fee_color === 'slate',
                    }">
                    {{ financials.fee_label }}
                </span>
                <p class="mt-2 font-mono text-xl font-black"
                    :class="{
                        'text-sky-400': financials.fee_color === 'sky',
                        'text-emerald-400': financials.fee_color === 'emerald',
                        'text-rose-400': financials.fee_color === 'rose',
                        'text-slate-500': financials.fee_color === 'slate',
                    }">
                    {{ formatKES(financials.fee_value) }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Legs -->
            <section>
                <h2 class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase">Fixtures & Markets ({{ legs.length }})</h2>
                <div class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                    <ul class="divide-y divide-[#232d42]/60">
                        <li v-for="leg in legs" :key="leg.id" class="p-4">
                            <p class="text-xs text-slate-400 uppercase tracking-widest">{{ leg.market }}</p>
                            <p class="mt-1 text-sm font-bold text-white">
                                {{ leg.fixture.home }} vs {{ leg.fixture.away }}
                            </p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="rounded bg-[#0f1422] px-2 py-1 text-xs font-mono text-emerald-400">{{ leg.selection }}</span>
                                <span class="text-xs text-amber-400 font-bold">@ {{ Number(leg.odd).toFixed(2) }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Purchases -->
            <section>
                <h2 class="mb-3 text-[10px] font-black tracking-widest text-emerald-400 uppercase">Purchases ({{ purchases.length }})</h2>
                <div class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                    <ul v-if="purchases.length > 0" class="divide-y divide-[#232d42]/60">
                        <li v-for="p in purchases" :key="p.id" class="flex items-center justify-between p-4">
                            <div>
                                <p class="text-sm font-bold text-white">{{ p.buyer.name }}</p>
                                <p class="text-[10px] text-slate-500">{{ new Date(p.purchased_at).toLocaleString() }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-sm font-black text-emerald-400">{{ formatKES(p.price) }}</p>
                                <p class="text-[10px] uppercase tracking-widest text-slate-500">{{ p.status }}</p>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="p-8 text-center text-xs text-slate-500">
                        No purchases yet.
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>