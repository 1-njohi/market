<template>
    <div class="w-full font-sans">
        <!-- ═══════ SECTION HEADER ═══════ -->
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
                    Top Tipsters
                </h3>
                <span
                    class="ml-2 rounded border border-sky-500/30 bg-sky-500/5 px-1.5 py-0.5 font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase"
                >
                    Monthly
                </span>
            </div>

            <div class="flex w-full items-center gap-3 md:w-auto">
                <span
                    class="hidden text-[10px] font-bold tracking-wider text-slate-500 uppercase md:inline"
                >
                    {{ leaders.length }} Ranked
                </span>

                <div class="relative w-full select-none md:w-72">
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
                        @keyup.enter="submitSearch"
                        placeholder="Search seller by code"
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
                class="mb-3 overflow-hidden rounded-lg border border-marketplace-gold/30 bg-marketplace-gold/5"
            >
                <div
                    class="flex items-center justify-between border-b border-marketplace-gold/20 px-4 py-2"
                >
                    <span
                        class="text-[10px] font-bold tracking-wider text-marketplace-gold uppercase"
                    >
                        Search Result
                    </span>
                    <button
                        @click="clearSearch"
                        class="cursor-pointer text-[10px] font-black tracking-wider text-slate-400 uppercase hover:text-white"
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
            class="rounded-lg border border-[#232d42] bg-[#161c2a] p-8 text-center font-mono text-xs tracking-wider text-slate-500 uppercase"
        >
            No tipsters on the board yet.
        </div>

        <!-- ═══════ FOOTER ═══════ -->
        <div
            class="mt-4 w-full rounded-lg border border-[#232d42] bg-[#111622]/40 p-4 text-center"
        >
            <button
                @click="$emit('view-all-clicked')"
                class="inline-block cursor-pointer text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
            >
                View All Rankings →
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