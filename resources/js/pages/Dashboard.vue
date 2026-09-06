<!-- <script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template> -->


<script setup>
import AuthenticatedLayout from "@/layouts/auth/AuthenticatedLayout.vue";
import HeatMap from "@/components/dashboard/HeatMap.vue";
import { Head } from "@inertiajs/vue3";

// lucide.createIcons();

// Generate a full year (365 days) of data
const generateYearlyData = () => {
    const data = [];
    const today = new Date();

    for (let i = 364; i >= 0; i--) {
        const date = new Date();
        date.setDate(today.getDate() - i);

        // Logic: 20% Wins, 10% Losses, 70% Inactive (Typical for a high-quality tipster)
        const rand = Math.random();
        let value = 0;
        let label = "No Activity";

        if (rand > 0.8) {
            value = 1;
            label = "Profit Generated";
        } else if (rand > 0.7) {
            value = -1;
            label = "Net Loss";
        }

        data.push({
            date: date.toISOString().split("T")[0], // YYYY-MM-DD
            value: value,
            label: label,
        });
    }
    return data;
};

const yearlyData = generateYearlyData();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- You're logged in! -->
                    </div>

                    <main class="ml20 p-8 lg:p-12 max-w-7xl mx-auto">
                        <header
                            class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12"
                        >
                            <div>
                                <h1
                                    class="text-2xl text-white font-bold tracking-tight"
                                >
                                    Seller Terminal
                                </h1>
                                <p class="text-zinc-100 text-sm">
                                    Welcome back, Captain. Your EPL markets are
                                    active.
                                </p>
                            </div>
                            <div
                                class="flex items-center gap-3 bg-zinc-900/50 border border-zinc-800 p-2 rounded-xl"
                            >
                                <div class="px-4 py-2 text-right">
                                    <p
                                        class="text-[10px] text-zinc-500 uppercase font-bold tracking-widest"
                                    >
                                        Global Rank
                                    </p>
                                    <p
                                        class="text-sm font-bold text-emerald-400"
                                    >
                                        #14 (Top 1%)
                                    </p>
                                </div>
                                <div class="h-8 w-px bg-zinc-800"></div>
                                <a href="/marketplace">
                                    <button
                                        class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-lg text-sm font-bold transition-all"
                                    >
                                        Create New Slip
                                    </button>
                                </a>
                            </div>
                        </header>

                        <div
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
                        >
                            <div
                                class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl"
                            >
                                <p
                                    class="text-zinc-500 text-xs font-bold uppercase mb-2"
                                >
                                    Realized Earnings
                                </p>
                                <h3 class="text-2xl font-bold text-white">
                                    $4,280.50
                                </h3>
                                <p
                                    class="text-emerald-400 text-[10px] mt-2 font-bold"
                                >
                                    +12% from last GW
                                </p>
                            </div>
                            <div
                                class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl"
                            >
                                <p
                                    class="text-zinc-500 text-xs font-bold uppercase mb-2"
                                >
                                    Locked in Escrow
                                </p>
                                <h3 class="text-2xl font-bold text-blue-400">
                                    $840.00
                                </h3>
                                <p
                                    class="text-zinc-500 text-[10px] mt-2 italic"
                                >
                                    Awaiting match results
                                </p>
                            </div>
                            <div
                                class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl"
                            >
                                <p
                                    class="text-zinc-500 text-xs font-bold uppercase mb-2"
                                >
                                    Avg. Slip ROI
                                </p>
                                <h3 class="text-2xl font-bold text-white">
                                    142%
                                </h3>
                                <p
                                    class="text-zinc-500 text-[10px] mt-2 font-bold uppercase tracking-widest"
                                >
                                    Verified via API
                                </p>
                            </div>
                            <div
                                class="bg-zinc-900 border border-zinc-800 p-6 rounded-2xl"
                            >
                                <p
                                    class="text-zinc-500 text-xs font-bold uppercase mb-2"
                                >
                                    Total Tails
                                </p>
                                <h3 class="text-2xl font-bold text-white">
                                    1,892
                                </h3>
                                <p
                                    class="text-zinc-500 text-[10px] mt-2 font-bold"
                                >
                                    Unique Buyers
                                </p>
                            </div>
                        </div>

                        <div class="py-10">
                            <HeatMap :data="yearlyData" roi="+14.2%" />
                        </div>

                        <div
                            class="bg-zinc-900 border border-zinc-800 p-8 rounded-2xl relative overflow-hidden"
                        >
                            <div class="absolute top-0 right-0 p-4 opacity-10">
                                <i
                                    data-lucide="shield-check"
                                    class="w-16 h-16"
                                ></i>
                            </div>
                            <h3
                                class="font-bold text-lg mb-6 leading-none text-white"
                            >
                                Withdrawal Terminal
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="text-[10px] text-zinc-500 font-bold uppercase block mb-2"
                                        >Preferred Asset</label
                                    >
                                    <select
                                        class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm focus:ring-0 focus:border-blue-500 text-white"
                                    >
                                        <option>Bank</option>
                                        <option>MPESA</option>
                                        <option>Airtel Money</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="text-[10px] text-zinc-500 font-bold uppercase block mb-2"
                                        >Recipient Address</label
                                    >
                                    <input
                                        type="text"
                                        placeholder="0..."
                                        class="w-full bg-zinc-950 border border-zinc-800 rounded-xl px-4 py-3 text-sm font-mono placeholder:text-zinc-700"
                                    />
                                </div>
                                <button
                                    class="w-full bg-white text-black font-bold py-3 rounded-xl text-sm mt-2 hover:bg-zinc-200 transition-colors"
                                >
                                    Settle to Wallet
                                </button>
                            </div>
                        </div>

                        <div
                            class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden mt-10"
                        >
                            <div class="p-8 border-b border-zinc-800">
                                <h3 class="font-bold text-lg text-white">
                                    Active Listings
                                </h3>
                            </div>
                            <table class="w-full text-left text-sm">
                                <thead
                                    class="bg-zinc-950/50 text-zinc-500 uppercase text-[10px] font-bold tracking-widest"
                                >
                                    <tr>
                                        <th class="px-8 py-4">Slip ID</th>
                                        <th class="px-8 py-4">Total Odds</th>
                                        <th class="px-8 py-4">Buyers</th>
                                        <th class="px-8 py-4">Status</th>
                                        <th class="px-8 py-4 text-right">
                                            Potential Payout
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-800">
                                    <tr>
                                        <td
                                            class="px-8 py-5 font-mono text-zinc-400"
                                        >
                                            #82910-EPL
                                        </td>
                                        <td
                                            class="px-8 py-5 font-bold text-blue-400"
                                        >
                                            12.45
                                        </td>
                                        <td class="px-8 py-5 text-white">
                                            412 Users
                                        </td>
                                        <td class="px-8 py-5">
                                            <span
                                                class="px-2 py-1 rounded bg-blue-500/10 text-blue-400 text-[10px] font-bold uppercase"
                                                >Matches Live</span
                                            >
                                        </td>
                                        <td
                                            class="px-8 py-5 text-right font-bold text-white"
                                        >
                                            $824.00
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            class="px-8 py-5 font-mono text-zinc-400"
                                        >
                                            #82885-EPL
                                        </td>
                                        <td
                                            class="px-8 py-5 font-bold text-blue-400"
                                        >
                                            4.20
                                        </td>
                                        <td class="px-8 py-5 text-white">
                                            188 Users
                                        </td>
                                        <td class="px-8 py-5">
                                            <span
                                                class="px-2 py-1 rounded bg-zinc-800 text-zinc-500 text-[10px] font-bold uppercase"
                                                >Pending Start</span
                                            >
                                        </td>
                                        <td
                                            class="px-8 py-5 text-right font-bold text-white"
                                        >
                                            $376.00
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
