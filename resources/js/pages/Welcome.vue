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
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import LogoutButton from '@/components/LogoutButton.vue';

const leaderboardRef = ref(null);

const handleLeaderSearch = async ({ code, local, seller }) => {
    if (local) {
        // Found in the top 10 — nothing else needed, card handles display
        return;
    }

    // Not in the top 10 — fetch from backend
    try {
        const { data } = await axios.get(`/sellers/lookup`, {
            params: { code },
        });

        if (data.success && data.seller) {
            // Push the fetched seller into the card
            leaderboardRef.value?.setSearchedSeller(data.seller);
        } else {
            alert('No seller found with that code.');
        }
    } catch (err) {
        console.error('Seller lookup failed:', err);
        alert('Seller lookup failed. Please try again.');
    }
};

const handleSellerClick = (seller) => {
    router.visit(`/profile/${seller.code}`);
};

const handleViewAllLeaders = () => {
    router.visit('/leaderboard');
};

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

const searchQuery = ref('');

const clearSearch = () => {
    searchQuery.value = '';
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
            class="[#070b14] fixed top-0 right-0 left-0 z-50 mx-auto flex w-full max-w-[1200px] justify-center bg-transparent text-sm not-has-[nav]:hidden md:px-6 lg:px-0"
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
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard()"
                            class="inline-block rounded border border-transparent bg-[#242f48] px-4 py-2 text-xs font-bold tracking-wide text-sky-400 uppercase transition-colors hover:bg-[#2e3c5c]"
                        >
                            Dashboard
                        </Link>

                        <!-- Settings gear icon -->
                        <Link
                            href="/settings"
                            class="flex h-9 w-9 items-center justify-center rounded border border-transparent text-slate-400 transition-colors hover:bg-[#242f48] hover:text-white"
                            title="Settings"
                            aria-label="Settings"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                                class="h-4 w-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                />
                            </svg>
                        </Link>
                        <LogoutButton />    
                    </template>

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
                    <BetslipWrapper />

                    <LeaderboardSummaryCard
                        ref="leaderboardRef"
                        class="mt-4"
                        :leaders="$page.props.leaders || []"
                        @seller-clicked="handleSellerClick"
                        @view-all-clicked="handleViewAllLeaders"
                        @search-submitted="handleLeaderSearch"
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
