<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import HeroSection from '@/components/HeroSection.vue';
import SingleFixtureCard from '@/components/SingleFixtureCard.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import BetslipWrapper from '@/components/BetslipWrapper.vue';

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

const goHome = () => {
    /// route to '/'
    router.visit("/");
}

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
        id: 1,
        name: 'WazoTank',
        roi: 18.4,
        win_rate: 74,
        streak: 6,
        active_tips: 3,
    },
    {
        id: 2,
        name: 'AlphaPicks',
        roi: 22.1,
        win_rate: 78,
        streak: 9,
        active_tips: 5,
    },
    {
        id: 3,
        name: 'NairobiShark',
        roi: 15.6,
        win_rate: 69,
        streak: -2,
        active_tips: 2,
    },
    {
        id: 4,
        name: 'Zeus_Bets',
        roi: 19.8,
        win_rate: 72,
        streak: 4,
        active_tips: 4,
    },
    {
        id: 5,
        name: 'ValueHunter',
        roi: 25.4,
        win_rate: 81,
        streak: 11,
        active_tips: 6,
    },
    {
        id: 6,
        name: 'Striker99',
        roi: 12.3,
        win_rate: 65,
        streak: 1,
        active_tips: 1,
    },
    {
        id: 7,
        name: 'OddsWhisperer',
        roi: 17.1,
        win_rate: 70,
        streak: 3,
        active_tips: 3,
    },
    {
        id: 8,
        name: 'MatrixBets',
        roi: 20.5,
        win_rate: 75,
        streak: 5,
        active_tips: 4,
    },
    {
        id: 9,
        name: 'GoalRush_KE',
        roi: 14.2,
        win_rate: 68,
        streak: -1,
        active_tips: 0,
    },
    {
        id: 10,
        name: 'PredictorPro',
        roi: 21.9,
        win_rate: 77,
        streak: 8,
        active_tips: 7,
    },
    {
        id: 11,
        name: 'SlipMaster',
        roi: 16.8,
        win_rate: 71,
        streak: 2,
        active_tips: 3,
    },
    {
        id: 12,
        name: 'SureShots',
        roi: 24.0,
        win_rate: 80,
        streak: 7,
        active_tips: 5,
    },
    {
        id: 13,
        name: 'TacticalTipster',
        roi: 11.5,
        win_rate: 63,
        streak: -3,
        active_tips: 1,
    },
    {
        id: 14,
        name: 'BookieBasher',
        roi: 18.9,
        win_rate: 73,
        streak: 4,
        active_tips: 2,
    },
    {
        id: 15,
        name: 'CornerKing',
        roi: 13.7,
        win_rate: 66,
        streak: 1,
        active_tips: 4,
    },
    {
        id: 16,
        name: 'ApexPredator',
        roi: 23.6,
        win_rate: 79,
        streak: 10,
        active_tips: 6,
    },
    {
        id: 17,
        name: 'CleanSheet',
        roi: 15.1,
        win_rate: 67,
        streak: 3,
        active_tips: 2,
    },
    {
        id: 18,
        name: 'Wazza_Bets',
        roi: 19.2,
        win_rate: 74,
        streak: 5,
        active_tips: 3,
    },
    {
        id: 19,
        name: 'Over25Guru',
        roi: 17.8,
        win_rate: 71,
        streak: -1,
        active_tips: 5,
    },
    {
        id: 20,
        name: 'BettingBaron',
        roi: 21.1,
        win_rate: 76,
        streak: 6,
        active_tips: 4,
    },
    {
        id: 21,
        name: 'DrawSpecialist',
        roi: 26.3,
        win_rate: 58,
        streak: 2,
        active_tips: 2,
    },
    {
        id: 22,
        name: 'Kip_Picks',
        roi: 14.7,
        win_rate: 69,
        streak: 1,
        active_tips: 3,
    },
    {
        id: 23,
        name: 'LaserFocus',
        roi: 18.2,
        win_rate: 73,
        streak: 4,
        active_tips: 1,
    },
    {
        id: 24,
        name: 'TheOracle',
        roi: 22.8,
        win_rate: 78,
        streak: 8,
        active_tips: 5,
    },
    {
        id: 25,
        name: 'MegaWin_26',
        roi: 16.4,
        win_rate: 70,
        streak: -2,
        active_tips: 0,
    },
    {
        id: 26,
        name: 'ProfitPulse',
        roi: 15.9,
        win_rate: 68,
        streak: 3,
        active_tips: 3,
    },
    {
        id: 27,
        name: 'SmartMoney',
        roi: 24.5,
        win_rate: 82,
        streak: 12,
        active_tips: 7,
    },
    {
        id: 28,
        name: 'VanguardBets',
        roi: 13.1,
        win_rate: 64,
        streak: 1,
        active_tips: 2,
    },
    {
        id: 29,
        name: 'ChampsLeague',
        roi: 19.5,
        win_rate: 74,
        streak: 5,
        active_tips: 4,
    },
    {
        id: 30,
        name: 'StatMaster',
        roi: 17.4,
        win_rate: 71,
        streak: 2,
        active_tips: 3,
    },
    {
        id: 31,
        name: 'BettingBot',
        roi: 12.8,
        win_rate: 63,
        streak: -1,
        active_tips: 1,
    },
    {
        id: 32,
        name: 'GigaWin',
        roi: 20.1,
        win_rate: 75,
        streak: 4,
        active_tips: 5,
    },
    {
        id: 33,
        name: 'SafaricomSlayer',
        roi: 18.6,
        win_rate: 72,
        streak: 3,
        active_tips: 2,
    },
    {
        id: 34,
        name: 'AccaGeneral',
        roi: 27.2,
        win_rate: 61,
        streak: 6,
        active_tips: 8,
    },
    {
        id: 35,
        name: 'FinalWhistle',
        roi: 14.9,
        win_rate: 67,
        streak: 1,
        active_tips: 2,
    },
    {
        id: 36,
        name: 'IceColdBets',
        roi: 16.3,
        win_rate: 70,
        streak: -2,
        active_tips: 4,
    },
    {
        id: 37,
        name: 'BoomBet',
        roi: 21.5,
        win_rate: 76,
        streak: 5,
        active_tips: 3,
    },
    {
        id: 38,
        name: 'DerbyDay',
        roi: 13.4,
        win_rate: 65,
        streak: 2,
        active_tips: 1,
    },
    {
        id: 39,
        name: 'Maestro_Tips',
        roi: 19.0,
        win_rate: 73,
        streak: 4,
        active_tips: 3,
    },
    {
        id: 40,
        name: 'TitanPicks',
        roi: 23.1,
        win_rate: 79,
        streak: 7,
        active_tips: 6,
    },
    {
        id: 41,
        name: 'SharpShooter',
        roi: 15.4,
        win_rate: 68,
        streak: 1,
        active_tips: 2,
    },
    {
        id: 42,
        name: 'LuckyStreak',
        roi: 25.0,
        win_rate: 83,
        streak: 10,
        active_tips: 5,
    },
    {
        id: 43,
        name: 'UnderdogForce',
        roi: 28.7,
        win_rate: 54,
        streak: -3,
        active_tips: 2,
    },
    {
        id: 44,
        name: 'FlawlessSlips',
        roi: 17.7,
        win_rate: 71,
        streak: 3,
        active_tips: 4,
    },
    {
        id: 45,
        name: 'ZenithBets',
        roi: 18.3,
        win_rate: 72,
        streak: 4,
        active_tips: 3,
    },
    {
        id: 46,
        name: 'GoldenBoot',
        roi: 14.5,
        win_rate: 66,
        streak: 1,
        active_tips: 1,
    },
    {
        id: 47,
        name: 'CalculatedRisk',
        roi: 20.8,
        win_rate: 75,
        streak: 5,
        active_tips: 5,
    },
    {
        id: 48,
        name: 'MidasTouch',
        roi: 22.4,
        win_rate: 77,
        streak: 6,
        active_tips: 4,
    },
    {
        id: 49,
        name: 'HighRoller_KE',
        roi: 16.1,
        win_rate: 69,
        streak: -1,
        active_tips: 2,
    },
    {
        id: 50,
        name: 'PhoenixPicks',
        roi: 19.6,
        win_rate: 74,
        streak: 4,
        active_tips: 3,
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
    <!-- <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head> -->
    <div
        class="flex min-h-screen flex-col items-center bg-[#070b14] px-0 py-6 text-[#1b1b18] lg:justify-center lg:px-0 lg:py-8 dark:bg-[#070b14]"
    >
        <header
            class="mx-auto mb-4 w-full max-w-[1200px] px-2 text-sm not-has-[nav]:hidden md:px-6 lg:px-0"
        >
            <nav
                class="flex items-center justify-between rounded-lg border border-[#232d42] bg-[#161c2a] p-4 shadow-lg"
            >
                <!-- BRAND / LOGO ACCENT -->
                <div class="flex items-center space-x-2 text-sky-400 cursor-pointer" @click="goHome">
                    <img
                        src="../../img/logo.png"
                        alt="Betslip Pirates Logo"
                        class="pointer-events-none h-10 w-auto object-contain select-none"
                    />

                    <span
                        class="text-s md:block hidden font-black tracking-widest text-slate-200 uppercase"
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
            class="w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <HeroSection v-if="!$page.props.auth.user" />
            <main
                class="grid w-full grid-cols-1 gap-4 overflow-hidden rounded-lg p-6 lg:grid-cols-12 lg:p-6"
            >
                <div class="lg:col-span-3">
                    <!-- <BetslipCard /> -->
                    <BetslipWrapper />
                </div>

                <div class="lg:col-span-9">
                    <SingleFixtureCard />
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
