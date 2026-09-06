<template>
    <div
        class="w-full max-w-xl overflow-hidden rounded-xl border border-gray-800 bg-[#0d1527] font-sans shadow-2xl"
    >
        <!-- HEADER -->
        <div
            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
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
                    Top Captains
                </h3>
            </div>
            <span
                class="text-[10px] font-bold tracking-wider text-gray-500 uppercase"
            >
                Monthly Standings
            </span>
        </div>

        <!-- LEADERBOARD ROWS -->
        <div
            class="scrollbar-thin overflow-y-auto scrollbar-color-sky-500_slate-200 max-h-150 scrollbar-thin divide-y divide-gray-800/40 overflow-x-hidden overflow-y-auto p-1 md:max-h-full"
        >
            <div
                v-for="(seller, index) in leaders"
                :key="seller.id"
                class="group flex cursor-pointer items-center justify-between p-1 transition-colors hover:bg-[#121b32]/30"
                @click="$emit('seller-clicked', seller)"
            >
            <LeaderCard :leader="seller" :key="index"    /> 
            </div>
        </div>

        <!-- CARD FOOTER -->
        <div class="border-t border-gray-800/50 bg-[#0a101f] p-3 text-center">
            <button
                @click="$emit('view-all-clicked')"
                class="text-[10px] font-bold tracking-widest text-blue-500 uppercase transition-colors hover:text-blue-400"
            >
                View Full Standings Registry →
            </button>
        </div>
    </div>
</template>

<script setup>
import LeaderCard from './LeaderCard.vue';

defineProps({
    leaders: {
        type: Array,
        required: true,
        default: () => [],
    },
});

defineEmits(['seller-clicked', 'view-all-clicked']);
</script>
