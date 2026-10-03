<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import HeroSection from '@/components/HeroSection.vue';
import FixtureSummaryCard from '@/components/FixtureSummaryCard.vue';
import BetSlipSummaryCard from '@/components/BetSlipSummaryCard.vue';
import LeaderboardSummaryCard from '@/components/LeaderboardSummaryCard.vue';
import BetslipWrapper from '@/components/BetslipWrapper.vue';
import Footer from '@/components/Footer.vue';
import NavMenu from '@/components/NavMenu.vue';
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();

const leaderboardRef = ref(null);

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const visibleSuccess = ref<string | null>(null);
const visibleError = ref<string | null>(null);

let successTimer: ReturnType<typeof setTimeout> | null = null;
let errorTimer: ReturnType<typeof setTimeout> | null = null;

const showSuccess = (msg: string) => {
    if (successTimer) {
        clearTimeout(successTimer);
        successTimer = null;
    }
    visibleSuccess.value = msg;
    successTimer = setTimeout(() => {
        visibleSuccess.value = null;
        successTimer = null;
    }, 4000);
};

const showError = (msg: string) => {
    if (errorTimer) {
        clearTimeout(errorTimer);
        errorTimer = null;
    }
    visibleError.value = msg;
    errorTimer = setTimeout(() => {
        visibleError.value = null;
        errorTimer = null;
    }, 5000);
};

const dismissSuccess = () => {
    if (successTimer) {
        clearTimeout(successTimer);
        successTimer = null;
    }
    visibleSuccess.value = null;
};

const dismissError = () => {
    if (errorTimer) {
        clearTimeout(errorTimer);
        errorTimer = null;
    }
    visibleError.value = null;
};

watch(
    flashSuccess,
    (msg) => {
        if (msg) showSuccess(msg);
    },
    { immediate: true },
);

watch(
    flashError,
    (msg) => {
        if (msg) showError(msg);
    },
    { immediate: true },
);

const handleLeaderSearch = async ({ code, local, seller }) => {
    if (local) return;

    try {
        const { data } = await axios.get(`/sellers/lookup`, {
            params: { code },
        });

        if (data.success && data.seller) {
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

const selections = ref([]);

const getSelectionsFromLocalStorage = () => {
    try {
        const stored = localStorage.getItem('bet_selections');
        if (stored) {
            const parsed = JSON.parse(stored);
            return Array.isArray(parsed) ? parsed : [];
        }
        return [];
    } catch {
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

onMounted(() => {
    updateSelections();
    window.addEventListener('betSelectionUpdated', updateSelections);
    window.addEventListener('storage', handleStorageChange);
});

onBeforeUnmount(() => {
    window.removeEventListener('betSelectionUpdated', updateSelections);
    window.removeEventListener('storage', handleStorageChange);
});

const openBetslip = () => {
    window.dispatchEvent(
        new CustomEvent('betslipOpened', {
            detail: { timestamp: Date.now() },
        }),
    );
};

window.updateSelections = updateSelections;
window.getSelections = getSelectionsFromLocalStorage;

const searchQuery = ref('');

const clearSearch = () => {
    searchQuery.value = '';
};

/**
 * Look up a betslip by tracking code and navigate to its public view.
 * Codes are stored uppercased server-side, so we normalize before the
 * request. The lookup endpoint returns a relative URL on success.
 */
const submitBetslipSearch = async () => {
    const code = searchQuery.value.trim().toUpperCase();
    if (!code) return;

    try {
        const { data } = await axios.get('/betslips/lookup', {
            params: { code },
        });

        if (data.success && data.url) {
            router.visit(data.url);
            return;
        }

        alert(data.message ?? 'No betslip found with that code.');
    } catch (err) {
        const msg =
            err.response?.data?.message ??
            'Lookup failed. Please try again.';
        alert(msg);
    }
};
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-[#0a1628] font-sans text-slate-300">
        <!-- ═══════════════ HEADER ═══════════════ -->
        <header
            class="[#070b14] transparent fixed top-0 right-0 left-0 z-50 mx-auto flex w-full max-w-[1200px] justify-center bg-[#] text-sm not-has-[nav]:hidden md:px-6 lg:px-0"
        >
            <nav
                class="flex w-[95%] items-center justify-between rounded-lg border border-[#232d42] bg-[#161c2a] p-4 shadow-lg md:w-[75rem]"
            >
                <div class="flex items-center space-x-2 text-sky-400">
                    <img
                        src="../../img/logo.png"
                        alt="Betslip Pirates Logo"
                        class="pointer-events-none h-10 w-auto object-contain select-none"
                    />
                    <span
                        class="hidden text-sm font-black tracking-widest text-slate-200 uppercase md:block"
                    >
                        BETSLIP PIRATES
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard()"
                            class="flex items-center gap-1.5 rounded border border-[#232d42] bg-[#111622] px-3 py-2 transition-colors hover:border-sky-500/40"
                            title="Available balance"
                        >
                            <span
                                class="hidden text-[9px] font-black tracking-widest text-slate-500 uppercase sm:inline"
                            >
                                Balance
                            </span>
                            <span
                                class="font-mono text-xs font-black text-emerald-400"
                            >
                                KES
                                {{
                                    Number(
                                        $page.props.auth.balance || 0,
                                    ).toLocaleString(undefined, {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0,
                                    })
                                }}
                            </span>
                        </Link>

                        <NavMenu />
                    </template>

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

        <!-- ═══════════════ HERO (guests only) ═══════════════ -->
        <div class="pt-24">
            <HeroSection v-if="!$page.props.auth.user" />
        </div>

        <!-- ═══════════════ MAIN COLUMN ═══════════════ -->
        <main
            :class="[
                'mx-auto w-full max-w-[1200px] space-y-8 px-2 md:px-6 lg:px-8',
                $page.props.auth.user ? 'pt-' : 'pt-8',
            ]"
        >
            <!-- ─── Featured Betslips ─── -->
            <section>
                <div
                    class="mb-4 flex flex-col items-start justify-between gap-3 border-b border-[#232d42] pb-4 md:flex-row md:items-center"
                >
                    <div class="flex items-center gap-2 text-sky-400">
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
                                d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-6.75c-.621 0-1.125.504-1.125 1.125v3.375m9 0h-9M9 3.75h6M9 3.75a3 3 0 0 0-3 3v2.25c0 .621.503 1.125 1.125 1.125h6.75A1.125 1.125 0 0 0 15 9.375V6.75a3 3 0 0 0-3-3ZM9 3.75h6m-6 0c0-.621.503-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125"
                            />
                        </svg>
                        <h3
                            class="text-xs font-black tracking-widest text-white uppercase"
                        >
                            Featured Betslips
                        </h3>
                    </div>

                    <div
                        class="relative w-full max-w-md select-none md:w-80"
                    >
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

                        <input
                            type="text"
                            v-model="searchQuery"
                            @keyup.enter="submitBetslipSearch"
                            placeholder="Enter Betslip Tracking Code"
                            class="w-full rounded-lg border border-[#232d42] bg-[#111622] py-2 pr-9 pl-10 text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-all duration-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                        />

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
                    class="grid grid-cols-1 justify-items-center gap-6 md:grid-cols-2"
                >
                    <BetSlipSummaryCard
                        v-for="(sample_slip, i) in $page.props.bet_slips"
                        :key="i"
                        :slip="sample_slip"
                        :isLocked="true"
                        class="w-full"
                    />

                    <!-- Full width separator -->
                    <div
                        class="w-full rounded-lg border border-[#232d42] bg-[#111622]/40 p-4 text-center md:col-span-2"
                    >
                        <Link
                            href="/marketplace"
                            class="inline-block cursor-pointer text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
                        >
                            View More Betslips →
                        </Link>
                    </div>
                </div>
            </section>

            <!-- ─── Top Tipsters ─── -->
            <section>
                <LeaderboardSummaryCard
                    ref="leaderboardRef"
                    :leaders="$page.props.leaders || []"
                    @seller-clicked="handleSellerClick"
                    @view-all-clicked="handleViewAllLeaders"
                    @search-submitted="handleLeaderSearch"
                />
            </section>

            <!-- ─── Create Your Betslip ─── -->
            <section>
                <FixtureSummaryCard :leagues="$page.props.fixtures" />
            </section>
        </main>

        <div class="h-16"></div>

        <Footer />

        <!-- ─── Toast container (unchanged) ─── -->
        <Teleport to="body">
            <div
                class="pointer-events-none fixed inset-x-0 bottom-0 z-[9999] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6"
            >
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="translate-y-8 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-8 opacity-0"
                >
                    <div
                        v-if="visibleSuccess"
                        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-emerald-200 bg-white p-4 shadow-lg"
                    >
                        <div
                            class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="3"
                                stroke="currentColor"
                                class="h-3 w-3 text-emerald-600"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m4.5 12.75 6 6 9-13.5"
                                />
                            </svg>
                        </div>

                        <p class="flex-1 text-sm font-medium text-slate-800">
                            {{ visibleSuccess }}
                        </p>

                        <button
                            type="button"
                            @click="dismissSuccess"
                            class="-mt-1 -mr-1 flex h-6 w-6 flex-shrink-0 cursor-pointer items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Dismiss"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-3.5 w-3.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </Transition>

                <Transition
                    enter-active-class="transition-all duration-500 ease-out"
                    enter-from-class="translate-y-8 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-8 opacity-0"
                >
                    <div
                        v-if="visibleError"
                        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-rose-200 bg-white p-4 shadow-lg"
                    >
                        <div
                            class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-rose-100"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="3"
                                stroke="currentColor"
                                class="h-3 w-3 text-rose-600"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                        </div>

                        <p class="flex-1 text-sm font-medium text-slate-800">
                            {{ visibleError }}
                        </p>

                        <button
                            type="button"
                            @click="dismissError"
                            class="-mt-1 -mr-1 flex h-6 w-6 flex-shrink-0 cursor-pointer items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Dismiss"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-3.5 w-3.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </Transition>
            </div>
        </Teleport>

        <!-- Betslip overlay (unchanged) -->
        <BetslipWrapper />
    </div>
</template>