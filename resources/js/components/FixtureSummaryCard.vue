<template>
    <div
        class="rounded-xl border border-marketplace-border bg-marketplace-card shadow-lg"
    >
        <!-- HEADER -->
        <div
            class="flex flex-col items-stretch justify-between gap-3 border-b border-marketplace-border/60 bg-marketplace-card/80 p-4 sm:flex-row sm:items-center"
        >
            <div class="flex items-center space-x-2 text-marketplace-gold">
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
                <h3 class="text-xs font-bold tracking-widest text-white uppercase">
                    Create Your Betslip
                </h3>
            </div>

            <!-- SEARCH -->
            <div class="relative w-full select-none sm:max-w-xs">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-marketplace-muted"
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
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z"
                        />
                    </svg>
                </div>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Find a team…"
                    class="w-full rounded-lg border border-marketplace-border bg-marketplace-bg py-2 pr-9 pl-9 text-xs font-semibold tracking-wide text-white placeholder-marketplace-muted transition-colors focus:border-marketplace-gold focus:outline-none"
                />
                <button
                    v-if="search"
                    type="button"
                    @click="search = ''"
                    class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3 text-marketplace-muted transition-colors hover:text-white"
                    aria-label="Clear search"
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
        </div>

        <!-- BODY -->
        <div
            class="bg-marketplace-bg p-2 font-sans text-marketplace-slate select-none md:p-6"
        >
            <div class="mx-auto max-w-[1200px]">
                <FixtureList :leagues="leagues" :search="search" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import FixtureList from './FixtureList.vue';

defineProps({
    leagues: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const search = ref('');
</script>