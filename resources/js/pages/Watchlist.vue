<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import BetSlipSummaryCard from '@/components/BetSlipSummaryCard.vue';
import Footer from '@/components/Footer.vue';
import { computed } from 'vue';
import InfoPopover from '@/components/InfoPopover.vue';

const page = usePage();
const betSlips = computed(() => page.props.bet_slips?.data ?? []);
</script>

<template>
    <Head title="Watchlist — Betslip Pirates" />

    <div class="min-h-screen bg-[#0000] font-sans text-slate-300">
        <div class="mx-auto w-full max-w-[1200px] px-4 py-12 md:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 border-b border-[#232d42] pb-6">
                <div class="flex items-center gap-2">
                    <p
                        class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                    >
                        Watchlist
                    </p>
                    <InfoPopover title="How watching works">
                        <p>
                            Watching a betslip is like paper-trading. You don't
                            pay, and you don't see the seller's picks yet.
                        </p>
                        <p>
                            When the slip settles, we'll send you a notification
                            with the outcome. If you want, open the slip to see
                            the picks that made it win — or lose.
                        </p>
                        <p class="text-slate-400">
                            Over time your watchlist becomes a personal track
                            record: a running count of how many slips you
                            watched and how many settled in your favor. Use it
                            to decide which sellers are worth buying from.
                        </p>
                        <p
                            class="rounded border border-sky-500/20 bg-sky-500/5 px-3 py-2 text-[11px] text-sky-300"
                        >
                            You can watch up to 20 active slips at a time.
                        </p>
                    </InfoPopover>
                </div>
                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    Watching {{ betSlips.length }}
                    {{ betSlips.length === 1 ? 'betslip' : 'betslips' }}
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400"
                >
                    These slips are on your radar. You'll be notified when each
                    one settles — no purchase required.
                </p>
            </div>

            <!-- Empty state -->
            <div
                v-if="betSlips.length === 0"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] p-12 text-center"
            >
                <p
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                >
                    No active watches
                </p>
                <p class="mt-3 text-sm text-slate-400">
                    Browse the marketplace and tap Watch on any betslip to keep
                    tabs on it without buying.
                </p>
                <Link
                    href="/marketplace"
                    class="mt-6 inline-block rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
                >
                    Browse marketplace
                </Link>
            </div>

            <!-- List -->
            <div
                v-else
                class="grid grid-cols-1 justify-items-center gap-6 md:grid-cols-2"
            >
                <BetSlipSummaryCard
                    v-for="slip in betSlips"
                    :key="slip.id"
                    :slip="slip"
                    :isLocked="true"
                    class="w-full"
                />
            </div>
        </div>

        <Footer />
    </div>
</template>
