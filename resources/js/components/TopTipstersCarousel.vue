<template>
    <div class="relative w-full">
        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span
                        class="text-[10px] font-black tracking-[0.25em] text-sky-400 uppercase"
                    >
                        Top Tipsters
                    </span>
                    <span
                        class="rounded-full border border-sky-500/30 bg-sky-500/5 px-2 py-0.5 font-mono text-[9px] font-black tracking-[0.2em] text-sky-400 uppercase"
                    >
                        Monthly
                    </span>
                </div>
                <h3
                    class="mt-3 text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl"
                >
                    The ones to follow.
                </h3>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="openSearch"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-[#232d42] text-slate-400 transition-colors hover:border-sky-500/40 hover:text-sky-400"
                    aria-label="Search a tipster"
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
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z"
                        />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="prev"
                    :disabled="atStart"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-[#232d42] text-slate-400 transition-colors hover:border-sky-500/40 hover:text-sky-400 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Previous"
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
                            d="M15.75 19.5 8.25 12l7.5-7.5"
                        />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="next"
                    :disabled="atEnd"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-[#232d42] text-slate-400 transition-colors hover:border-sky-500/40 hover:text-sky-400 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="Next"
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
                            d="m8.25 4.5 7.5 7.5-7.5 7.5"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search overlay -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="searchOpen" class="mb-6">
                <div class="relative">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500"
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
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z"
                            />
                        </svg>
                    </div>
                    <input
                        ref="searchInput"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search a seller by code…"
                        class="w-full rounded-full border border-[#232d42] bg-transparent py-3 pr-4 pl-11 text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-colors focus:border-sky-500/60 focus:outline-none"
                        @keyup.enter="submitSearch"
                    />
                    <button
                        type="button"
                        @click="closeSearch"
                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-500 transition-colors hover:text-slate-200"
                        aria-label="Close search"
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
        </Transition>

        <!-- Carousel track -->
        <div
            ref="track"
            class="carousel-track flex snap-x snap-mandatory gap-6 overflow-x-auto pb-4 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
            @scroll.passive="onScroll"
        >
            <TipsterPodiumCard
                v-for="(seller, index) in leaders"
                :key="seller.id"
                :tipster="seller"
                :rank="index + 1"
                @click="$emit('seller-clicked', seller)"
            />

            <div
                v-if="!leaders || leaders.length === 0"
                class="flex w-full items-center justify-center rounded-2xl border border-dashed border-[#232d42]/60 py-16 text-xs font-black tracking-[0.2em] text-slate-600 uppercase"
            >
                No tipsters on the board yet
            </div>
        </div>

        <!-- Pagination dots -->
        <div v-if="leaders && leaders.length > 0" class="mt-6 flex justify-center gap-1.5">
            <button
                v-for="(_, i) in leaders"
                :key="i"
                type="button"
                @click="scrollToIndex(i)"
                :class="[
                    'h-1.5 rounded-full transition-all',
                    i === activeIndex
                        ? 'w-6 bg-sky-400'
                        : 'w-1.5 bg-[#232d42] hover:bg-sky-500/40',
                ]"
                :aria-label="`Go to tipster ${i + 1}`"
            />
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <button
                type="button"
                @click="$emit('view-all-clicked')"
                class="inline-flex items-center gap-2 text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase transition-colors hover:text-sky-400"
            >
                View all rankings
                <span>→</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue';
import TipsterPodiumCard from './TipsterPodiumCard.vue';

const props = defineProps({
    leaders: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'seller-clicked',
    'view-all-clicked',
    'search-submitted',
]);

const track = ref(null);
const activeIndex = ref(0);
const searchOpen = ref(false);
const searchQuery = ref('');
const searchInput = ref(null);

const atStart = computed(() => activeIndex.value === 0);
const atEnd = computed(() => activeIndex.value >= props.leaders.length - 1);

const openSearch = async () => {
    searchOpen.value = true;
    await nextTick();
    searchInput.value?.focus();
};

const closeSearch = () => {
    searchOpen.value = false;
    searchQuery.value = '';
};

const submitSearch = () => {
    const code = searchQuery.value.trim().toUpperCase();
    if (!code) return;

    const match = props.leaders.find(
        (s) => (s.code ?? '').toUpperCase() === code,
    );

    if (match) {
        const idx = props.leaders.indexOf(match);
        scrollToIndex(idx);
        emit('seller-clicked', match);
        return;
    }

    emit('search-submitted', { code, local: false });
};

const scrollToIndex = (index) => {
    const el = track.value;
    if (!el) return;

    const cards = el.querySelectorAll('[data-tipster-card]');
    const target = cards[index];
    if (!target) return;

    target.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'start',
    });
    activeIndex.value = index;
};

const prev = () => {
    if (activeIndex.value > 0) scrollToIndex(activeIndex.value - 1);
};

const next = () => {
    if (activeIndex.value < props.leaders.length - 1) {
        scrollToIndex(activeIndex.value + 1);
    }
};

const onScroll = () => {
    const el = track.value;
    if (!el) return;

    const cards = el.querySelectorAll('[data-tipster-card]');
    if (!cards.length) return;

    const elLeft = el.getBoundingClientRect().left;
    let best = 0;
    let bestDist = Infinity;

    cards.forEach((card, i) => {
        const dist = Math.abs(card.getBoundingClientRect().left - elLeft);
        if (dist < bestDist) {
            bestDist = dist;
            best = i;
        }
    });

    if (best !== activeIndex.value) activeIndex.value = best;
};
</script>

<style scoped>
.carousel-track {
    scroll-behavior: smooth;
}
</style>