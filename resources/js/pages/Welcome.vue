<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
import { register } from '@/routes';
import HeroSection from '@/components/HeroSection.vue';
import FixtureSummaryCard from '@/components/FixtureSummaryCard.vue';
import BetSlipSummaryCard from '@/components/BetSlipSummaryCard.vue';
import LeaderboardSummaryCard from '@/components/LeaderboardSummaryCard.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import BetslipWrapper from '@/components/BetslipWrapper.vue';
import Footer from '@/components/Footer.vue';

// Initialize selections as a ref with an empty array
const selections = ref([]);

// Helper function to get all selections from localStorage
const getSelectionsFromLocalStorage = () => {
    try {
        const stored_selections = localStorage.getItem('bet_selections');
        if (stored_selections) {
            const parsed = JSON.parse(stored_selections);
            return Array.isArray(parsed) ? parsed : [];
        }
        return [];
    } catch (error) {
        console.error('❌ Error reading selections from localStorage:', error);
        return [];
    }
};

// Update selections from localStorage
const updateSelections = () => {
    selections.value = getSelectionsFromLocalStorage();
};

// Optional: Watch for localStorage changes from other tabs/windows
const handleStorageChange = (event) => {
    if (event.key === 'bet_selections') {
        updateSelections();
    }
};

// Lifecycle hooks
onMounted(() => {
    // Load initial selections
    updateSelections();

    // Listen for custom event from the component that saves selections
    window.addEventListener('betSelectionUpdated', updateSelections);

    // Listen for localStorage changes from other tabs/windows
    window.addEventListener('storage', handleStorageChange);

    // Optional: Poll for changes every few seconds (if you want extra safety)
    // const interval = setInterval(updateSelections, 2000);
    // Store interval ID to clear it later
});

onBeforeUnmount(() => {
    // Clean up event listeners
    window.removeEventListener('betSelectionUpdated', updateSelections);
    window.removeEventListener('storage', handleStorageChange);
    // clearInterval(interval); // If using polling
});

const openBetslip = () => {
    console.log('Dispatching betslipOpened event');
    window.dispatchEvent(
        new CustomEvent('betslipOpened', {
            detail: { timestamp: Date.now() },
        }),
    );
};

// Expose to window for debugging
window.updateSelections = updateSelections;
window.getSelections = getSelectionsFromLocalStorage;

const leaderboardData = ref([
    {
        id: 27,
        name: 'SmartMoney',
        roi: 24.5,
        win_rate: 82,
        avatar: 'https://api.dicebear.com/10.x/lorelei-neutral/svg?seed=Felix',
        streak: 12,
        recent_form: ['L', 'W', 'W', 'W', 'W', 'W', 'W', 'L', 'W'],
        active_tips: 7,
    },
    {
        id: 50,
        name: 'PhoenixPicks',
        roi: 19.6,
        avatar: 'https://api.dicebear.com/10.x/thumbs/svg?seed=Felix',
        win_rate: 74,
        streak: 4,
        recent_form: ['L', 'W', 'L', 'W', 'L', 'W', 'W', 'W', 'W'],
        active_tips: 3,
        badges: [
            {
                name: 'BigMan',
                created_at: '22025-07-09 12:20:20',
                avatar: '',
            },
            {
                name: 'Top 1%',
                created_at: '2000-02-02 02:05:34',
                avatar: '',
            },
        ],
    },
]);

const searchQuery = ref('');

const clearSearch = () => {
    searchQuery.value = '';
};

const handleSellerClick = (seller) => {
    console.log('Redirecting to public profile of:', seller.name);
    // route('sellers.show', seller.id)
};

const handleViewAllLeaders = () => {
    console.log('Opening full leaderboard page');
};
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="py- flex min-h-screen flex-col items-center bg-[#0a1628] px-0 text-[#1b1b18] lg:justify-center lg:px-0 lg:py-8 dark:bg-[#0a1628]"
    >
        <header
            class="fixed top-0 right-0 left-0 z-50 mx-auto flex w-full max-w-[1200px] justify-center bg-[#070b14] text-sm not-has-[nav]:hidden md:px-6 lg:px-0"
        >
            <nav
                class="flex w-[95%] items-center justify-between rounded-lg border border-[#232d42] bg-[#161c2a] p-4 shadow-lg md:w-[75rem]"
            >
                <!-- BRAND / LOGO ACCENT -->
                <div class="flex items-center space-x-2 text-sky-400">
                    <img
                        src="../../img/logo.png"
                        alt="Betslip Pirates Logo"
                        class="pointer-events-none h-10 w-auto object-contain select-none"
                    />

                    <span
                        class="text-s hidden font-black tracking-widest text-slate-200 uppercase md:block"
                    >
                        BETSLIP PIRATES
                    </span>
                </div>

                <!-- NAVIGATION LINKS & ACTIONS -->
                <div class="flex items-center gap-3">
                    <!-- Authenticated State -->
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-block rounded border border-transparent bg-[#242f48] px-4 py-2 text-xs font-bold tracking-wide text-sky-400 uppercase transition-colors hover:bg-[#2e3c5c]"
                    >
                        Dashboard
                    </Link>

                    <!-- Guest State -->
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-block rounded px-4 py-2 text-xs font-bold tracking-wide text-slate-400 uppercase transition-colors hover:text-slate-200"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-block rounded border border-transparent bg-[#242f48] px-4 py-2 text-xs font-bold tracking-wide text-slate-200 uppercase transition-colors hover:bg-[#2e3c5c]"
                        >
                            Register
                        </Link>
                    </template>

                    <!-- FLOATING MOBILE BETSLIP TRIGGER -->
                    <!-- Styled to match the premium "⚡ BOOSTED ODDS" badge theme -->
                    <div v-if="selections && selections.length > 0">
                        <button
                            type="button"
                            @click="openBetslip"
                            class="fixed right-4 bottom-6 z-50 flex cursor-pointer items-center space-x-2 rounded-full bg-[#ff8c00] px-5 py-3 text-xs font-black tracking-wider text-black uppercase shadow-2xl transition-transform hover:scale-105 active:scale-95 md:hidden"
                        >
                            <span>⚡ Betslip</span>
                            <span
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-black text-[10px] font-black text-[#ff8c00]"
                            >
                                {{ selections.length }}
                            </span>
                        </button>
                    </div>
                </div>
            </nav>
        </header>
        <div
            class="primary red-900 w-full items-center justify-center bg-[#0a1628] pt-20 opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <HeroSection v-if="!$page.props.auth.user" />
            <main
                class="grid w-full grid-cols-1 gap-4 overflow-hidden rounded-lg px-1 lg:grid-cols-12 lg:p-6"
            >
                <div class="lg:col-span-3">
                    <!-- <BetslipCard /> -->
                    <BetslipWrapper />
                    <LeaderboardSummaryCard
                        :leaders="leaderboardData"
                        @seller-clicked="handleSellerClick"
                        @view-all-clicked="handleViewAllLeaders"
                    />
                </div>

                <div class="lg:col-span-9">
                    <!-- HEADER -->
                    <div
                        class="block items-center justify-between border-b border-gray-800/60 p-4 md:flex md:bg-[#111a30]"
                    >
                        <div class="flex items-center space-x-2 text-blue-400">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-4 w-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-6.75c-.621 0-1.125.504-1.125 1.125v3.375m9 0h-9M9 3.75h6M9 3.75a3 3 0 0 0-3 3v2.25c0 .621.503 1.125 1.125 1.125h6.75A1.125 1.125 0 0 0 15 9.375V6.75a3 3 0 0 0-3-3ZM9 3.75h6m-6 0c0-.621.503-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125M18.02 7.2h-1.5m1.5 0c.394 0 .736.228.892.56L19.8 11.53c.092.191.139.399.139.61v.61c0 .746-.604 1.35-1.35 1.35h-1.432a1.432 1.432 0 0 1-1.35-.97l-.63-1.89A1.432 1.432 0 0 0 13.816 10.5H11.5m-5.32 0h-1.5m1.5 0a1.125 1.125 0 0 1-.892-.44L3.92 7.76a1.125 1.125 0 0 1 .892-.56h1.432c.394 0 .736.228.892.56l1.36 2.74c.092.191.139.399.139.61v.61c0 .746-.604 1.35-1.35 1.35H5.85"
                                />
                            </svg>
                            <h3
                                class="text-xs font-bold tracking-widest text-gray-200 uppercase"
                            >
                                Featured Betslips
                            </h3>
                        </div>

                        <div
                            class="relative mt-4 w-full max-w-md select-none md:mt-0"
                        >
                            <!-- SEARCH ICON -->
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-4 w-4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z"
                                    />
                                </svg>
                            </div>

                            <!-- MAIN INPUT FIELD -->
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Enter Betslip Tracking Code"
                                class="w-full rounded-lg border border-[#232d42] bg-[#111622] py-2 pr-9 pl-10 text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-all duration-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                            />

                            <!-- DYNAMIC CLEAR BUTTON -->
                            <button
                                v-if="searchQuery"
                                type="button"
                                @click="clearSearch"
                                class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3 text-slate-500 transition-colors hover:text-slate-300"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-4 w-4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18 18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div
                        class="mb-6 grid grid-cols-1 justify-items-center gap-6 rounded-xl pt-6 md:grid-cols-2 md:bg-[#111622]"
                    >
                        <!-- Card Loop -->
                        <BetSlipSummaryCard
                            :slip="sample_slip"
                            :isLocked="true"
                            v-for="(sample_slip, i) in $page.props.bet_slips"
                            :key="i"
                            class="w-full"
                        />

                        <!-- FULL WIDTH BLOCK SEPARATOR -->
                        <div
                            class="w-full rounded-b-xl border-t border-gray-800/50 bg-[#0a101f] p-4 text-center md:col-span-2"
                        >
                            <Link
                                href="/marketplace"
                                class="inline-block cursor-pointer text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
                            >
                                View More Betslips →
                            </Link>
                        </div>
                    </div>

                    <FixtureSummaryCard :leagues="$page.props.fixtures" />
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>

        <Footer />
    </div>
</template>
