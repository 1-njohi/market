<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    betslip: Object,
    legs: Array,
    purchases: Array,
});
</script>

<template>
    <Head :title="`Betslip ${betslip.code} — Admin`" />

    <div>
        <div class="mb-6 border-b border-[#232d42] pb-6">
            <Link
                href="/admin/betslips"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← All betslips
            </Link>
            <h1
                class="mt-3 font-mono text-2xl font-black tracking-wider text-white uppercase"
            >
                {{ betslip.code }}
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                Seller: {{ betslip.seller?.name }} ({{ betslip.seller?.code }})
                · Status: {{ betslip.status }} ·
                {{ new Date(betslip.created_at).toLocaleString() }}
            </p>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >Odds</span
                >
                <p class="mt-1 text-xl font-black text-amber-400">
                    {{ Number(betslip.total_odds).toFixed(2) }}
                </p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >Price</span
                >
                <p class="mt-1 text-xl font-black text-emerald-400">
                    KES {{ Number(betslip.price).toFixed(2) }}
                </p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >Remaining</span
                >
                <p class="mt-1 text-xl font-black text-white">
                    {{ betslip.remaining }}
                </p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >Winner</span
                >
                <p
                    class="mt-1 text-xl font-black"
                    :class="
                        betslip.is_winner
                            ? 'text-emerald-400'
                            : 'text-slate-500'
                    "
                >
                    {{ betslip.is_winner ? 'YES' : 'NO' }}
                </p>
            </div>
        </div>

        <!-- Legs -->
        <section class="mb-6">
            <h2
                class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Legs
            </h2>
            <div class="overflow-hidden rounded-lg border border-[#232d42]">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Match</th>
                            <th class="px-4 py-3">Market</th>
                            <th class="px-4 py-3">Selection</th>
                            <th class="px-4 py-3 text-right">Odds</th>
                            <th class="px-4 py-3">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#232d42]/60 bg-[#161c2a]">
                        <tr v-for="leg in legs" :key="leg.id">
                            <td class="px-4 py-2.5 text-slate-300">
                                {{ leg.fixture.home }} vs {{ leg.fixture.away }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-400">
                                {{ leg.market }}
                            </td>
                            <td class="px-4 py-2.5 font-bold text-slate-200">
                                {{ leg.selection }}
                            </td>
                            <td
                                class="px-4 py-2.5 text-right font-mono text-amber-400"
                            >
                                {{ leg.odd.toFixed(2) }}
                            </td>
                            <td
                                class="px-4 py-2.5 text-[10px] uppercase"
                                :class="{
                                    'text-amber-400':
                                        leg.pivot_status === 'pending',
                                    'text-emerald-400':
                                        leg.pivot_status === 'won',
                                    'text-rose-400':
                                        leg.pivot_status === 'lost',
                                    'text-slate-500':
                                        leg.pivot_status === 'void',
                                }"
                            >
                                {{ leg.pivot_status }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Purchases -->
        <section>
            <h2
                class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Purchases ({{ purchases.length }})
            </h2>
            <div class="overflow-hidden rounded-lg border border-[#232d42]">
                <table class="w-full text-left text-xs">
                    <thead
                        class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Buyer</th>
                            <th class="px-4 py-3 text-right">Price</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Purchased</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#232d42]/60 bg-[#161c2a]">
                        <tr v-for="p in purchases" :key="p.id">
                            <td class="px-4 py-2.5">
                                <Link
                                    v-if="p.buyer"
                                    :href="`/admin/users/${p.buyer.id}`"
                                    class="text-slate-300 hover:text-sky-400"
                                >
                                    {{ p.buyer.name }} ({{ p.buyer.code }})
                                </Link>
                            </td>
                            <td
                                class="px-4 py-2.5 text-right font-mono text-emerald-400"
                            >
                                {{ p.price.toFixed(2) }}
                            </td>
                            <td
                                class="px-4 py-2.5 text-[10px] text-slate-400 uppercase"
                            >
                                {{ p.status }}
                            </td>
                            <td class="px-4 py-2.5 text-slate-500">
                                {{ new Date(p.purchased_at).toLocaleString() }}
                            </td>
                        </tr>
                        <tr v-if="purchases.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-slate-500"
                            >
                                No purchases yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
