<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[#070b14] px-0 py-6 text-[#1b1b18] lg:justify-center lg:px-0 lg:py-8 dark:bg-[#070b14]">
        <div
            class="w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main class="grid w-full grid-cols-1 items-start gap-4 lg:grid-cols-12 lg:px-6">
                <!-- SIDEBAR PANEL (unchanged) -->
                <div
                    class="lg:h- [calc(100vh-4rem)] hidden overflow-y-auto bg-red-800 pr-2 text-white lg:sticky lg:top-6 lg:col-span-3 lg:block [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:rounded [&::-webkit-scrollbar-thumb]:bg-transparent hover:[&::-webkit-scrollbar-thumb]:bg-gray-800/80">
                    {{ initialPaginator }}
                </div>

                <!-- MAIN SLIPS DECK DISPLAY -->
                <div class="/40 overflow-hidden rounded-lg border border-gray-800/40 bg-[#070b14] lg:col-span-9">
                    <!-- HEADER CONTROL CONSOLE -->
                    <div
                        class="block items-center justify-between border-b border-gray-800/60 p-4 md:flex md:bg-[#070b14]">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-bold tracking-widest text-gray-200 uppercase">
                                Featured Betslips
                            </h3>
                            <InfoPopover title="How the marketplace works">
                                <p>
                                    Every slip here is listed by a seller. You
                                    can interact with any of them in two ways.
                                    (You too can create your own betslips and
                                    they will be listed here)
                                </p>
                                <div class="rounded border border-sky-500/20 bg-sky-500/5 px-3 py-2">
                                    <p class="mb-1 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                        Watch — free
                                    </p>
                                    <p class="text-[11px] text-slate-300">
                                        Track the slip without paying. You won't
                                        see the picks until it settles. We'll
                                        notify you of the outcome, and your
                                        personal watch record grows over time.
                                    </p>
                                </div>
                                <div class="rounded border border-amber-500/20 bg-amber-500/5 px-3 py-2">
                                    <p class="mb-1 text-[10px] font-black tracking-widest text-amber-400 uppercase">
                                        Unlock — pay once
                                    </p>
                                    <p class="text-[11px] text-slate-300">
                                        Pay the seller's price to see their
                                        picks immediately, and place a similar
                                        bet with the bookmarker of your choice.
                                        If the bet wins, you keep 100% of your
                                        winnings, and the seller gets their
                                        payout. If the slip loses, you're
                                        refunded the amount you used to unlock
                                        the betslip automatically, and the
                                        seller gets nothing.
                                    </p>
                                </div>
                                <p class="text-slate-400">
                                    Sellers are ranked by win rate and ROI.
                                    Click any seller's name to see their full
                                    record before you commit.
                                </p>
                            </InfoPopover>
                        </div>
                        <div class="relative mt-2 w-full max-w-md select-none md:mt-0">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z" />
                                </svg>
                            </div>
                            <input type="text" v-model="searchQuery" placeholder="Enter Betslip Tracking Code"
                                class="w-full rounded-lg border border-[#232d42] bg-[#111622] py-2 pr-9 pl-10 text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-all duration-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none" />
                            <button v-if="searchQuery" type="button" @click="clearSearch"
                                class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3 text-slate-500 transition-colors hover:text-slate-300">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- BUDGET FILTER CHIPS -->
                    <div class="flex flex-wrap items-center gap-2 border-b border-gray-800/60 bg-[#070b14] px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                                Budget
                            </span>
                            <InfoPopover title="What the budget filter does">
                                <p>
                                    Sellers price their slips at a set amount —
                                    that's what you pay to unlock the picks.
                                    Once you have them, you place the bet
                                    yourself with a bookmaker of your choice.
                                </p>
                                <p>
                                    <span class="font-bold text-slate-200">
                                        Break-even stake
                                    </span>
                                    is the amount you'd need to place at a
                                    bookmaker to earn back exactly what you
                                    paid for the slip — no profit, no loss.
                                    It's shown on every card.
                                </p>
                                <p>
                                    Pick a budget chip and we'll show only
                                    slips whose break-even stake fits within
                                    that amount. So a KES 50 budget shows
                                    slips you can afford to follow.
                                </p>
                                <p>
                                    A slip's break-even depends on its total
                                    odds:
                                </p>
                                <p class="font-mono text-[11px] text-slate-300">
                                    break-even = price ÷ (total odds − 1)
                                </p>
                                <p>
                                    Higher odds mean a lower break-even
                                    stake. A KES 100 slip at 10.00 odds only
                                    needs KES 11 staked. The same KES 100
                                    slip at 2.00 odds needs the full KES 100.
                                </p>
                                <p class="text-slate-400">
                                    No budget filter means every slip shows,
                                    whatever its break-even.
                                </p>
                            </InfoPopover>
                        </div>
                        <button v-for="amount in BUDGET_PRESETS" :key="amount" type="button" @click="setBudget(amount)"
                            class="cursor-pointer rounded-full border px-3 py-1 font-mono text-[11px] font-black transition-colors"
                            :class="activeBudget === amount
                                    ? 'border-amber-500 bg-amber-500/15 text-amber-400'
                                    : 'border-[#232d42] bg-[#111622] text-slate-400 hover:border-sky-500/60 hover:text-sky-300'
                                ">
                            KES {{ amount }}
                        </button>
                        <button v-if="activeBudget !== null" type="button" @click="setBudget(null)"
                            class="cursor-pointer rounded-full border border-[#232d42] bg-[#111622] px-3 py-1 font-mono text-[11px] font-black text-slate-400 transition-colors hover:border-rose-500/60 hover:text-rose-400">
                            Clear
                        </button>
                        <span v-if="activeBudget !== null" class="ml-1 text-[10px] text-slate-500">
                            Slips whose break-even fits your stake.
                        </span>
                    </div>

                    <!-- CARD LOOP GRID CONTAINER -->
                    <div
                        class="grid grid-cols-1 justify-items-center gap-6 px-2 py-6 text-red-600 md:grid-cols-2 md:bg-[#0a1628]">
                        <template v-for="slot in renderedSlots" :key="slot.key">
                            <BetSlipSummaryCard v-if="slot.type === 'betslip'" :slip="slot.data" :isLocked="true"
                                class="w-full" />
                            <div v-else-if="slot.type === 'iframe'"
                                class="[#0a0f1a] aspect-square w-full rounded-lg border border-gray-700/40 p-0">
                                <iframe :src="slot.url" width="100%" frameborder="0" scrolling="no" allowfullscreen
                                    loading="lazy" sandbox="allow-scripts allow-same-origin"
                                    class="aspect-square rounded-md" />
                            </div>
                        </template>

                        <!-- Empty state -->
                        <div v-if="items.length === 0 && !loading"
                            class="col-span-full w-full rounded-lg border border-dashed border-gray-700/60 px-6 py-12 text-center">
                            <p class="text-sm font-black tracking-wide text-slate-300 uppercase">
                                No slips under KES {{ activeBudget }}
                            </p>
                            <p class="mt-2 text-[11px] text-slate-500">
                                Try a larger budget, or clear the filter to see
                                everything.
                            </p>
                            <button type="button" @click="setBudget(null)"
                                class="mt-4 cursor-pointer rounded-lg border border-[#232d42] bg-[#111622] px-5 py-2 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/60 hover:text-sky-300">
                                Clear filter
                            </button>
                        </div>
                    </div>

                    <!-- Infinite scroll sentinel -->
                    <div ref="observerTarget" class="flex justify-center py-6 text-xs text-slate-400">
                        <span v-if="loading">Loading more bets...</span>
                        <span v-else-if="!hasMore && items.length > 0">No more bets to load</span>
                        <span v-else>&nbsp;</span>
                    </div>
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import BetSlipSummaryCard from '@/components/BetSlipSummaryCard.vue';
import InfoPopover from '@/components/InfoPopover.vue';

// ---------- Budget presets ----------
const BUDGET_PRESETS = [5, 10, 20, 50, 100, 200, 500, 1000];

// ---------- 1. Initial data from props ----------
const page = usePage();
const initialPaginator = computed(() => page.props.bet_slips);
const filters = computed(() => page.props.filters ?? { max_break_even: null });

const activeBudget = computed(() => {
    const v = filters.value.max_break_even;
    return v !== null && v !== undefined ? Number(v) : null;
});

// ---------- 2. Reactive state ----------
const items = ref(initialPaginator.value.data);
const nextPageUrl = ref(initialPaginator.value.next_page_url);
const hasMore = ref(!!nextPageUrl.value);
const loading = ref(false);

// Reset state whenever the server payload changes (i.e. when the filter
// changes). Without this the old slips stay visible and new ones append.
watch(
    () => initialPaginator.value,
    (paginator) => {
        items.value = paginator.data;
        nextPageUrl.value = paginator.next_page_url;
        hasMore.value = !!paginator.next_page_url;
        // Reset iframe positions so ads re-scatter across the new list.
        iframePositions.value = generateIframePositions(items.value.length);
    },
);

// ---------- 3. Budget filter ----------
const setBudget = (amount) => {
    // Clicking the active chip clears it — a common toggle convention.
    const next = amount === activeBudget.value ? null : amount;

    router.get(
        '/marketplace',
        next !== null ? { max_break_even: next } : {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['bet_slips', 'filters'],
        },
    );
};

// ---------- 4. Search ----------
const searchQuery = ref('');
const clearSearch = () => {
    searchQuery.value = '';
};

// ---------- 5. iFrame insertion ----------
const IFRAME_URL = 'https://example.com/';
const iframePositions = ref([]);

function generateIframePositions(totalItems) {
    const positions = [];
    let nextPos = -1;
    while (true) {
        const gap = Math.floor(Math.random() * 3) + 3;
        nextPos += gap;
        if (nextPos >= totalItems) break;
        positions.push(nextPos);
    }
    return positions;
}

watch(
    () => items.value.length,
    (newLen, oldLen) => {
        if (newLen > oldLen) {
            iframePositions.value = generateIframePositions(newLen);
        }
    },
    { immediate: true },
);

const renderedSlots = computed(() => {
    const slots = [];
    const positions = iframePositions.value;
    let iframeIndex = 0;

    for (let i = 0; i < items.value.length; i++) {
        slots.push({
            key: `betslip-${items.value[i].id}`,
            type: 'betslip',
            data: items.value[i],
        });

        if (iframeIndex < positions.length && i === positions[iframeIndex]) {
            slots.push({
                key: `iframe-${i}-${iframeIndex}`,
                type: 'iframe',
                url: IFRAME_URL,
            });
            iframeIndex++;
        }
    }
    return slots;
});

// ---------- 6. Infinite scroll ----------
const observerTarget = ref(null);
let observer = null;

async function loadMore() {
    if (loading.value || !hasMore.value) return;
    loading.value = true;

    let url = nextPageUrl.value;
    if (window.location.protocol === 'https:') {
        url = url.replace(/^http:\/\//, 'https://');
    }

    try {
        const response = await axios.get(url, {
            headers: { Accept: 'application/json' },
        });
        let newPaginator = response.data.props?.bet_slips;
        if (!newPaginator) {
            newPaginator = response.data;
        }
        if (!Array.isArray(newPaginator.data)) {
            throw new Error('Invalid paginator data');
        }
        items.value = [...items.value, ...newPaginator.data];
        nextPageUrl.value = newPaginator.next_page_url || null;
        hasMore.value = !!newPaginator.next_page_url;
    } catch (error) {
        console.error('Failed to load more:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (!observerTarget.value) return;
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && !loading.value && hasMore.value) {
                loadMore();
            }
        },
        { rootMargin: '0px 0px 100px 0px' },
    );
    observer.observe(observerTarget.value);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// ---------- 7. Meta (unchanged) ----------
const bet_slips_meta = computed(() => ({
    current_page: initialPaginator.value.current_page,
    total: initialPaginator.value.total,
}));
</script>