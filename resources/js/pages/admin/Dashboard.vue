<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});

const formatKES = (n: number) =>
    'KES ' +
    Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 });
</script>

<template>

    <Head title="Admin Dashboard" />

    <div class="space-y-8">
        <!-- Header -->
        <div class="border-b border-[#232d42] pb-6">
            <h1 class="text-2xl font-black tracking-wider text-white uppercase">Operations Overview</h1>
            <p class="mt-2 text-xs text-slate-500">Financial and system snapshot for Fainali.</p>
        </div>

        <!-- Requires Attention -->
        <section v-if="stats.queues.pending_withdrawals_count > 0 || stats.queues.open_reports_count > 0">
            <h2 class="mb-4 text-[10px] font-black tracking-widest text-rose-400 uppercase">Requires Attention</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div v-if="stats.queues.pending_withdrawals_count > 0"
                    class="rounded-lg border border-amber-500/30 bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Pending
                        Withdrawals</span>
                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-black text-white">{{ stats.queues.pending_withdrawals_count }}</p>
                        <span class="text-sm font-bold text-amber-400">{{
                            formatKES(stats.queues.pending_withdrawals_value) }}</span>
                    </div>
                    <p class="mt-1 text-[10px] text-slate-500">Awaiting approval</p>
                </div>
                <div v-if="stats.queues.open_reports_count > 0"
                    class="rounded-lg border border-amber-500/30 bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Open Disputes</span>
                    <p class="mt-2 text-3xl font-black text-white">{{ stats.queues.open_reports_count }}</p>
                    <p class="mt-1 text-[10px] text-slate-500">Needs review</p>
                </div>
            </div>
        </section>

        <!-- System Treasury (Lifetime) -->
        <section>
            <h2 class="mb-4 text-[10px] font-black tracking-widest text-emerald-400 uppercase">System Treasury</h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total Deposits
                        In</span>
                    <p class="mt-2 text-xl font-black text-white">{{ formatKES(stats.treasury.deposits_lifetime) }}</p>
                </div>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total Withdrawn
                        Out</span>
                    <p class="mt-2 text-xl font-black text-rose-400">{{ formatKES(stats.treasury.withdrawals_lifetime)
                        }}</p>
                </div>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Platform Revenue
                        (Fees)</span>
                    <p class="mt-2 text-xl font-black text-amber-400">{{ formatKES(stats.treasury.fees_lifetime) }}</p>
                </div>
            </div>
        </section>

        <!-- User Wallets (Current) -->
        <section>
            <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">User Funds (Live Balances)
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Available
                        (Spendable)</span>
                    <p class="mt-2 text-xl font-black text-white">{{ formatKES(stats.wallets.available) }}</p>
                </div>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">In Escrow
                        (Locked)</span>
                    <p class="mt-2 text-xl font-black text-purple-400">{{ formatKES(stats.wallets.escrow) }}</p>
                </div>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total User
                        Balance</span>
                    <p class="mt-2 text-xl font-black text-emerald-400">{{ formatKES(stats.wallets.total) }}</p>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Today's Activity -->
            <section>
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-white uppercase">Today's Activity</h2>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-[#232d42]/60">
                            <tr>
                                <td class="px-4 py-3 text-slate-400">Deposits</td>
                                <td class="px-4 py-3 text-right font-black text-white">{{
                                    formatKES(stats.today.deposits_amount) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[10px] text-slate-500">{{
                                    stats.today.deposits_count }} tx</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-slate-400">Withdrawals</td>
                                <td class="px-4 py-3 text-right font-black text-white">{{
                                    formatKES(stats.today.withdrawals_amount) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[10px] text-slate-500">{{
                                    stats.today.withdrawals_count }} req</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-slate-400">Fees Collected</td>
                                <td class="px-4 py-3 text-right font-black text-amber-400">{{
                                    formatKES(stats.today.fees_amount) }}</td>
                                <td class="px-4 py-3"></td>
                            </tr>
                            <tr class="bg-[#0f1422]">
                                <td class="px-4 py-3 font-bold text-slate-300">Marketplace Volume</td>
                                <td class="px-4 py-3 text-right font-black text-emerald-400">{{
                                    formatKES(stats.today.volume_amount) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-[10px] text-slate-500">{{
                                    stats.today.betslips_sold }} sold</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Marketplace Snapshot -->
            <section>
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-white uppercase">Marketplace Snapshot</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Betslips Created
                            Today</span>
                        <p class="mt-2 text-2xl font-black text-white">{{ stats.today.betslips_created }}</p>
                    </div>
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total Lifetime
                            Sales</span>
                        <p class="mt-2 text-2xl font-black text-white">{{ formatKES(stats.betslips.total_sales_value) }}
                        </p>
                    </div>
                    <div class="col-span-2 rounded-lg border border-rose-500/20 bg-rose-500/5 p-4">
                        <span class="text-[10px] font-black tracking-widest text-rose-400 uppercase">Max Payout
                            Liability (Open Slips)</span>
                        <p class="mt-2 text-2xl font-black text-rose-400">{{
                            formatKES(stats.betslips.potential_liability) }}</p>
                        <p class="mt-1 text-[10px] text-slate-500">Total the platform would pay if every active slip
                            won.</p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Users -->
        <section>
            <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">Users</h2>
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total</span>
                    <p class="mt-2 text-xl font-black text-white">{{ stats.users.total.toLocaleString() }}</p>
                </div>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">New Today</span>
                    <p class="mt-2 text-xl font-black text-sky-400">{{ stats.users.new_today }}</p>
                </div>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Active 24h</span>
                    <p class="mt-2 text-xl font-black text-emerald-400">{{ stats.users.active_24h }}</p>
                </div>
            </div>
        </section>
    </div>
</template>