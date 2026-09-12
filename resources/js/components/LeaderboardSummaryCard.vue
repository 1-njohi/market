<template>
    <div class="w-full font-sans">
        <!-- ═══════ SECTION HEADER (outside the cards) ═══════ -->
        <div
            class="block items-center justify-between border-b border-marketplace-border/60 p-4 md:flex md:rounded-t-xl md:bg-marketplace-card/40"
        >
            <div class="flex items-center space-x-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-marketplace-gold/30 bg-marketplace-gold/10"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-4 w-4 text-marketplace-gold"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-6.75c-.621 0-1.125.504-1.125 1.125v3.375m9 0h-9M9 3.75h6M9 3.75a3 3 0 0 0-3 3v2.25c0 .621.503 1.125 1.125 1.125h6.75A1.125 1.125 0 0 0 15 9.375V6.75a3 3 0 0 0-3-3Z"
                        />
                    </svg>
                </div>
                <div>
                    <h3
                        class="text-xs font-bold tracking-widest text-white uppercase"
                    >
                        Top Captains
                    </h3>
                    <div
                        class="mt-0.5 text-[10px] font-semibold tracking-wide text-marketplace-muted uppercase"
                    >
                        Monthly Standings
                    </div>
                </div>
            </div>

            <span
                class="mt-2 block text-[10px] font-bold tracking-wider text-marketplace-muted uppercase md:mt-0 md:block"
            >
                {{ leaders.length }} Ranked
            </span>
        </div>

        <!-- ═══════ SEARCH BAR (outside the cards) ═══════ -->
        <div
            class="border-b border-[#232d42] mb-6 pb-6 marketplace-border/40 bg-marketplace-card/30 p-3"
        >
            <div class="relative w-full select-none">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-marketplace-muted"
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
                    @keyup.enter="submitSearch"
                    placeholder="Search seller by code"
                                class="w-full rounded-lg border border-[#232d42] bg-[#111622] py-2 pr-9 pl-10 text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-all duration-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                />

                <button
                    v-if="searchQuery"
                    type="button"
                    @click="clearSearch"
                    class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3 text-marketplace-muted transition-colors hover:text-white"
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

        <!-- ═══════ SEARCHED SELLER ═══════ -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-96"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 max-h-96"
            leave-to-class="opacity-0 max-h-0"
        >
            <div
                v-if="searchedSeller"
                class="overflow-hidden border-b border-marketplace-gold/30 bg-marketplace-gold/5"
            >
                <div
                    class="flex items-center justify-between border-b border-marketplace-gold/20 px-4 py-2"
                >
                    <span
                        class="text-[10px] font-bold tracking-wider text-marketplace-gold uppercase"
                    >
                        ◆ Search Result
                    </span>
                    <button
                        @click="clearSearch"
                        class="cursor-pointer text-[10px] font-black tracking-wider text-marketplace-muted uppercase hover:text-white"
                    >
                        Dismiss
                    </button>
                </div>
                <div class="p-3">
                    <LeaderCard
                        :leader="searchedSeller"
                        :rank="searchedSeller.rank ?? null"
                        :highlighted="true"
                        @click="$emit('seller-clicked', searchedSeller)"
                    />
                </div>
            </div>
        </Transition>

        <!-- ═══════ LEADERS STACK ═══════ -->
        <div v-if="leaders && leaders.length > 0" class="space-y-2">
            <LeaderCard
                v-for="(seller, index) in leaders"
                :key="seller.id"
                :leader="seller"
                :rank="index + 1"
                @click="$emit('seller-clicked', seller)"
            />
        </div>

        <!-- ═══════ EMPTY STATE ═══════ -->
        <div
            v-else-if="!searchedSeller"
            class="p-8 text-center font-mono text-xs tracking-wider text-marketplace-muted uppercase"
        >
            No captains on the board yet.
        </div>

        <!-- ═══════ FOOTER — matches "View More Betslips" ═══════ -->
        <div
            class="w-full rounded-b-xl border-t border-marketplace-border/50 bg-marketplace-card/40 p-4 text-center"
        >
            <button
                @click="$emit('view-all-clicked')"
                class="inline-block cursor-pointer text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
            >
                View Full Standings Registry →
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import LeaderCard from './LeaderCard.vue';

const props = defineProps({
    leaders: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const emit = defineEmits([
    'seller-clicked',
    'view-all-clicked',
    'search-submitted',
]);

const searchQuery = ref('');
const searchedSeller = ref(null);

const submitSearch = () => {
    const code = searchQuery.value.trim().toUpperCase();
    if (!code) {
        searchedSeller.value = null;
        return;
    }

    const fromLeaderboard = props.leaders.find(
        (s) => (s.code ?? '').toUpperCase() === code,
    );

    if (fromLeaderboard) {
        const rank = props.leaders.indexOf(fromLeaderboard) + 1;
        searchedSeller.value = { ...fromLeaderboard, rank };
        emit('search-submitted', {
            code,
            local: true,
            seller: searchedSeller.value,
        });
        return;
    }

    searchedSeller.value = null;
    emit('search-submitted', { code, local: false });
};

const clearSearch = () => {
    searchQuery.value = '';
    searchedSeller.value = null;
};

const setSearchedSeller = (seller) => {
    searchedSeller.value = seller;
};

defineExpose({ setSearchedSeller });
</script>
