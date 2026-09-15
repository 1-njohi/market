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

    <div>
        <!-- Header -->
        <div class="mb-8 border-b border-[#232d42] pb-6">
            <h1 class="text-2xl font-black tracking-wider text-white uppercase">
                Operations Overview
            </h1>
            <p class="mt-2 text-xs text-slate-500">
                Live snapshot of everything moving through Betslip Pirates right
                now.
            </p>
        </div>

        <!-- Queues that need attention -->
        <section class="mb-8">
            <h2
                class="mb-4 text-[10px] font-black tracking-widest text-rose-400 uppercase"
            >
                Requires Attention
            </h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    :class="
                        stats.pending_withdrawals > 0
                            ? 'border-amber-500/30'
                            : ''
                    "
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Pending Withdrawals
                    </span>
                    <p class="mt-2 text-3xl font-black text-white">
                        {{ stats.pending_withdrawals }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">
                        M-Pesa payouts awaiting processing
                    </p>
                </div>

                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    :class="stats.open_reports > 0 ? 'border-amber-500/30' : ''"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Open Reports
                    </span>
                    <p class="mt-2 text-3xl font-black text-white">
                        {{ stats.open_reports }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">
                        User-submitted reports awaiting review
                    </p>
                </div>
            </div>
        </section>

        <!-- Today's numbers -->
        <section class="mb-8">
            <h2
                class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Today
            </h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Deposits
                    </span>
                    <p class="mt-2 text-xl font-black text-emerald-400">
                        {{ formatKES(stats.deposits_today_amount) }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">
                        {{ stats.deposits_today_count }} transactions
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Withdrawals
                    </span>
                    <p class="mt-2 text-xl font-black text-rose-400">
                        {{ formatKES(stats.withdrawals_today_amount) }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">
                        {{ stats.withdrawals_today_count }} requests
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Betslips Created
                    </span>
                    <p class="mt-2 text-xl font-black text-white">
                        {{ stats.betslips_today }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">
                        {{ stats.betslips_sold_today }} sold
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Platform Fees
                    </span>
                    <p class="mt-2 text-xl font-black text-amber-400">
                        {{ formatKES(stats.fees_today_amount) }}
                    </p>
                    <p class="mt-1 text-[10px] text-slate-500">Earned today</p>
                </div>
            </div>
        </section>

        <!-- Users -->
        <section>
            <h2
                class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Users
            </h2>
            <div class="grid grid-cols-3 gap-3">
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Total
                    </span>
                    <p class="mt-2 text-xl font-black text-white">
                        {{ stats.users_total.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        New Today
                    </span>
                    <p class="mt-2 text-xl font-black text-sky-400">
                        {{ stats.users_new_today }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Active 24h
                    </span>
                    <p class="mt-2 text-xl font-black text-emerald-400">
                        {{ stats.active_last_24h }}
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
