<template>
    <Head title="Fixtures — Betslip Pirates" />

    <div class="bg-[#070b14] font-sans text-slate-300">
        <div class="mx-auto w-full max-w-[1400px] px-2 py-12 md:px-6 lg:px-8">
            <!-- ═══ HEADER ═══ -->
            <div class="mb-8 border-b border-[#232d42] pb-6">
                <div class="flex items-center gap-2">
                    <p
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Fixtures
                    </p>
                    <InfoPopover title="How picking works">
                        <p>
                            Tap any odd to add it to your betslip. Tap again
                            to remove it.
                        </p>
                        <p>
                            Nothing is committed until you build and buy the
                            slip — your selections are stored locally on this
                            device, not sent to the server.
                        </p>
                        <p class="text-slate-400">
                            The betslip is shared across all betting pages
                            (Fixtures, Welcome, Match view), so you can jump
                            around without losing your picks.
                        </p>
                    </InfoPopover>
                </div>

                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    {{ headlineCount }} Fixtures Across
                    {{ leagueCount }}
                    {{ leagueCount === 1 ? 'League' : 'Leagues' }}
                </h1>

                <p
                    class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400"
                >
                    Build your slip by tapping odds that catch your eye.
                    Multi-leg selections across different leagues are
                    supported.
                </p>

                <!-- SEARCH -->
                <div class="mt-6 max-w-md select-none">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-marketplace-muted"
                    ></div>
                    <div class="relative">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500"
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
                            v-model="search"
                            type="text"
                            placeholder="Search for a team…"
                            class="w-full rounded-lg border border-[#232d42] bg-[#111622] py-2.5 pr-9 pl-10 text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-colors focus:border-sky-500 focus:outline-none"
                        />
                        <button
                            v-if="search"
                            type="button"
                            @click="search = ''"
                            class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3 text-slate-500 transition-colors hover:text-white"
                            aria-label="Clear search"
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
            </div>

            <!-- ═══ CONTENT ═══ -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Fixtures list -->
                <div class="lg:col-span-8">
                    <div
                        v-if="fixtures.length === 0"
                        class="rounded-lg border border-[#232d42] bg-[#111622] p-12 text-center"
                    >
                        <p
                            class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            No fixtures available
                        </p>
                        <p class="mt-3 text-sm text-slate-400">
                            Check back later — new fixtures are added as they
                            become available.
                        </p>
                        <Link
                            href="/marketplace"
                            class="mt-6 inline-block rounded-lg bg-sky-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-sky-400"
                        >
                            Browse marketplace
                        </Link>
                    </div>

                    <FixtureList
                        v-else
                        :leagues="fixtures"
                        :search="search"
                    />
                </div>

                <!-- Betslip sidebar (desktop) -->
                <div class="hidden lg:col-span-4 lg:block">
                    <BetslipWrapper variant="sticky-sidebar" />
                </div>
            </div>
        </div>

        <!-- Mobile: floating button + bottom-sheet betslip -->
        <div v-if="selections.length > 0">
            <button
                type="button"
                @click="openBetslip"
                class="fixed right-4 bottom-6 z-50 flex cursor-pointer items-center space-x-2 rounded-full bg-[#ff8c00] px-5 py-3 text-xs font-black tracking-wider text-black uppercase shadow-2xl transition-transform hover:scale-105 active:scale-95 lg:hidden"
            >
                <span>⚡ Betslip</span>
                <span
                    class="flex h-5 w-5 items-center justify-center rounded-full bg-black text-[10px] font-black text-[#ff8c00]"
                >
                    {{ selections.length }}
                </span>
            </button>
        </div>

        <!-- Mobile: bottom sheet betslip (only renders when open) -->
        <div class="lg:hidden">
            <BetslipWrapper />
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import FixtureList from '@/components/FixtureList.vue';
import BetslipWrapper from '@/components/BetslipWrapper.vue';
import InfoPopover from '@/components/InfoPopover.vue';

const page = usePage();

const fixtures = computed(() => {
    const raw = page.props.fixtures ?? [];
    return Array.isArray(raw) ? raw : [];
});

const search = ref('');

const headlineCount = computed(() =>
    fixtures.value.reduce((sum, l) => sum + (l.fixtures?.length ?? 0), 0),
);

const leagueCount = computed(
    () =>
        fixtures.value.filter((l) => (l.fixtures?.length ?? 0) > 0).length,
);

// Mobile floating-button state — mirrors Fixture.vue / Welcome.vue
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

const openBetslip = () => {
    window.dispatchEvent(
        new CustomEvent('betslipOpened', {
            detail: { timestamp: Date.now() },
        }),
    );
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
</script>