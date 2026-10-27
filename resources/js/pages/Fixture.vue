<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import SingleFixtureCard from '@/components/SingleFixtureCard.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import BetslipWrapper from '@/components/BetslipWrapper.vue';

const selections = ref([]);
const betslipOpen = ref(false);

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

const updateSelections = () => {
    selections.value = getSelectionsFromLocalStorage();
};

const handleStorageChange = (event) => {
    if (event.key === 'bet_selections') {
        updateSelections();
    }
};

const markBetslipOpen = () => {
    betslipOpen.value = true;
};

const markBetslipClosed = () => {
    betslipOpen.value = false;
};

const goHome = () => {
    router.visit('/');
};

onMounted(() => {
    updateSelections();

    window.addEventListener('betSelectionUpdated', updateSelections);
    window.addEventListener('storage', handleStorageChange);
    window.addEventListener('betslipOpened', markBetslipOpen);
    window.addEventListener('betslipClosed', markBetslipClosed);
});

onBeforeUnmount(() => {
    window.removeEventListener('betSelectionUpdated', updateSelections);
    window.removeEventListener('storage', handleStorageChange);
    window.removeEventListener('betslipOpened', markBetslipOpen);
    window.removeEventListener('betslipClosed', markBetslipClosed);
});

const openBetslip = () => {
    window.dispatchEvent(
        new CustomEvent('betslipOpened', {
            detail: { timestamp: Date.now() },
        }),
    );
};

const searchQuery = ref('');

const clearSearch = () => {
    searchQuery.value = '';
};

const handleSellerClick = (seller) => {
    console.log('Redirecting to public profile of:', seller.name);
};

const handleViewAllLeaders = () => {
    console.log('Opening full leaderboard page');
};
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[#070b14] px-0 py-6 text-[#1b1b18] lg:justify-center lg:px-0 lg:py-8 dark:bg-[#070b14]"
    >
        <div
            class="w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main class="grid w-full grid-cols-1 gap-4 overflow-hidden rounded-lg lg:grid-cols-12 lg:p-6">
                <div class="lg:col-span-3">
                    <BetslipWrapper />
                </div>

                <div class="lg:col-span-9">
                    <SingleFixtureCard />
                </div>

                <div v-if="selections.length > 0 && !betslipOpen">
                    <button
                        type="button"
                        @click="openBetslip"
                        class="group fixed right-4 bottom-6 z-50 flex min-h-14 cursor-pointer items-center justify-between gap-3 rounded-2xl bg-amber-400 pr-3 pl-5 text-[#070b14] shadow-[0_16px_40px_-12px_rgba(251,191,36,0.8)] transition-all duration-200 hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-100 focus-visible:ring-offset-2 focus-visible:ring-offset-[#070b14] focus-visible:outline-none active:scale-[0.98]"
                        :aria-label="`Open betslip, ${selections.length} ${selections.length === 1 ? 'pick' : 'picks'}`"
                    >
                        <span class="flex items-center gap-2.5 text-base font-black">
                            View betslip
                        </span>
                        <span class="flex items-center gap-2">
                            <span
                                class="flex h-8 min-w-8 items-center justify-center rounded-full bg-[#070b14] px-2 text-sm font-black text-amber-300 tabular-nums"
                            >
                                {{ selections.length }}
                            </span>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="3"
                                stroke="currentColor"
                                class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                                />
                            </svg>
                        </span>
                    </button>
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>