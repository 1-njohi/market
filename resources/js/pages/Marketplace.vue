<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[#070b14] px-0 py-6 text-[#1b1b18] lg:justify-center lg:px-0 lg:py-8 dark:bg-[#070b14]"
    >
        <div
            class="w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="grid w-full grid-cols-1 items-start gap-4 lg:grid-cols-12 lg:px-6"
            >
                <!-- SIDEBAR PANEL (unchanged) -->
                <div
                    class="lg:h- [calc(100vh-4rem)] hidden overflow-y-auto bg-red-800 pr-2 text-white lg:sticky lg:top-6 lg:col-span-3 lg:block [&::-webkit-scrollbar]:w-1 [&::-webkit-scrollbar-thumb]:rounded [&::-webkit-scrollbar-thumb]:bg-transparent hover:[&::-webkit-scrollbar-thumb]:bg-gray-800/80"
                >
                    <!-- Your Sidebar Components Go Here -->
                    {{ initialPaginator }}
                </div>

                <!-- MAIN SLIPS DECK DISPLAY -->
                <div
                    class="/40 overflow-hidden rounded-lg border border-gray-800/40 bg-[#070b14] lg:col-span-9"
                >
                    <!-- HEADER CONTROL CONSOLE (unchanged) -->
                    <div
                        class="block items-center justify-between border-b border-gray-800/60 p-4 md:flex md:bg-[#070b14]"
                    >
                        <div class="flex items-center gap-2">
                            <h3
                                class="text-xs font-bold tracking-widest text-gray-200 uppercase"
                            >
                                Featured Betslips
                            </h3>
                            <InfoPopover title="How the marketplace works">
                                <p>
                                    Every slip here is listed by a seller. You
                                    can interact with any of them in two ways.
                                </p>
                                <div
                                    class="rounded border border-sky-500/20 bg-sky-500/5 px-3 py-2"
                                >
                                    <p
                                        class="mb-1 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                                    >
                                        Watch — free
                                    </p>
                                    <p class="text-[11px] text-slate-300">
                                        Track the slip without paying. You won't
                                        see the picks until it settles. We'll
                                        notify you of the outcome, and your
                                        personal watch record grows over time.
                                    </p>
                                </div>
                                <div
                                    class="rounded border border-amber-500/20 bg-amber-500/5 px-3 py-2"
                                >
                                    <p
                                        class="mb-1 text-[10px] font-black tracking-widest text-amber-400 uppercase"
                                    >
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
                        <div
                            class="relative mt-2 w-full max-w-md select-none md:mt-0"
                        >
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500"
                            >
                                <!-- <svg ... > -->
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Enter Betslip Tracking Code"
                                class="w-full rounded-lg border border-[#232d42] bg-[#111622] py-2 pr-9 pl-10 text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-all duration-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                            />
                            <button v-if="searchQuery" @click="clearSearch">
                                DSDS
                            </button>
                        </div>
                    </div>

                    <!-- CARD LOOP GRID CONTAINER with mixed slots -->
                    <div
                        class="grid grid-cols-1 justify-items-center gap-6 px-2 py-6 text-red-600 md:grid-cols-2 md:bg-[#0a1628]"
                    >
                        <template v-for="slot in renderedSlots" :key="slot.key">
                            <!-- Betslip card -->
                            <BetSlipSummaryCard
                                v-if="slot.type === 'betslip'"
                                :slip="slot.data"
                                :isLocked="true"
                                class="w-full"
                            />
                            <!-- iFrame ad -->
                            <div
                                v-else-if="slot.type === 'iframe'"
                                class="[#0a0f1a] aspect-square w-full rounded-lg border border-gray-700/40 p-0"
                            >
                                <!-- <AdBanner /> -->
                                <iframe
                                    :src="slot.url"
                                    width="100%"
                                    frameborder="0"
                                    scrolling="no"
                                    allowfullscreen
                                    loading="lazy"
                                    sandbox="allow-scripts allow-same-origin"
                                    class="aspect-square rounded-md"
                                />
                            </div>
                        </template>
                    </div>

                    <!-- Infinite scroll sentinel + loading indicator -->
                    <div
                        ref="observerTarget"
                        class="flex justify-center py-6 text-xs text-slate-400"
                    >
                        <span v-if="loading">Loading more bets...</span>
                        <span v-else-if="!hasMore">No more bets to load</span>
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
import { usePage, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import BetSlipSummaryCard from '@/components/BetSlipSummaryCard.vue';
import AdBanner from '@/components/AdBanner.vue';
import InfoPopover from '@/components/InfoPopover.vue';

// ---------- 1. Initial data from props ----------
const page = usePage();
const initialPaginator = page.props.bet_slips;

// ---------- 2. Reactive state ----------
const items = ref(initialPaginator.data); // all loaded betslips
const nextPageUrl = ref(initialPaginator.next_page_url);
const hasMore = ref(!!nextPageUrl.value);
const loading = ref(false);

// Search (unrelated to infinite scroll)
const searchQuery = ref('');
const clearSearch = () => {
    searchQuery.value = '';
};

// ---------- 3. iFrame insertion logic ----------
const IFRAME_URL = 'https://example.com/'; // 🔁 change to your ad URL

// Generate stable positions (index of betslip after which to insert iframe)
// Positions are generated once and only updated when new items are appended.
const iframePositions = ref([]);

function generateIframePositions(totalItems) {
    const positions = [];
    let nextPos = -1;
    while (true) {
        const gap = Math.floor(Math.random() * 3) + 3; // 3,4,5
        nextPos += gap;
        if (nextPos >= totalItems) break;
        positions.push(nextPos);
    }
    return positions;
}

// When items change, regenerate iframe positions (only if total increased)
watch(
    () => items.value.length,
    (newLen, oldLen) => {
        if (newLen > oldLen) {
            // Generate positions for the new total (this will be stable for the session)
            iframePositions.value = generateIframePositions(newLen);
        }
    },
    { immediate: true }, // generate initially
);

// Compute rendered slots: betslips + iframes inserted at fixed positions
const renderedSlots = computed(() => {
    const slots = [];
    const positions = iframePositions.value;
    let iframeIndex = 0;

    for (let i = 0; i < items.value.length; i++) {
        // Add betslip
        slots.push({
            key: `betslip-${items.value[i].id}`,
            type: 'betslip',
            data: items.value[i],
        });

        // Insert iframe after this betslip if position matches
        if (iframeIndex < positions.length && i === positions[iframeIndex]) {
            slots.push({
                key: `iframe-${i}-${Date.now()}-${iframeIndex}`, // ensure uniqueness
                type: 'iframe',
                url: IFRAME_URL,
            });
            iframeIndex++;
        }
    }
    return slots;
});

// ---------- 4. Infinite scroll: load more ----------
const observerTarget = ref(null);
let observer = null;

async function loadMore() {
    if (loading.value || !hasMore.value) return;
    loading.value = true;

    // Ensure HTTPS if page is served over HTTPS
    let url = nextPageUrl.value;
    if (window.location.protocol === 'https:') {
        url = url.replace(/^http:\/\//, 'https://');
    }

    try {
        const response = await axios.get(url, {
            headers: { Accept: 'application/json' },
        });
        // Try to get the paginator from props
        let newPaginator = response.data.props?.bet_slips;
        if (!newPaginator) {
            // Maybe it's directly the paginator
            newPaginator = response.data;
        }
        // Ensure data is an array
        if (!Array.isArray(newPaginator.data)) {
            throw new Error('Invalid paginator data');
        }
        items.value = [...items.value, ...newPaginator.data];
        nextPageUrl.value = newPaginator.next_page_url || null;
        hasMore.value = !!nextPageUrl.value;
    } catch (error) {
        console.error('Failed to load more:', error);
    } finally {
        loading.value = false;
    }
}

// Setup Intersection Observer
onMounted(() => {
    if (!observerTarget.value) return;
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && !loading.value && hasMore.value) {
                loadMore();
            }
        },
        { rootMargin: '0px 0px 100px 0px' }, // trigger 100px before the sentinel is fully visible
    );
    observer.observe(observerTarget.value);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// ---------- 5. Expose meta for debugging/display (optional) ----------
const bet_slips_meta = computed(() => ({
    current_page: initialPaginator.current_page,
    total: initialPaginator.total,
}));
</script>
