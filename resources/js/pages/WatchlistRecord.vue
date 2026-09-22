<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Footer from '@/components/Footer.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import { computed } from 'vue';

const page = usePage();
const record = computed(() => page.props.record);

const winRate = computed(() => {
    const decided = record.value.won_count + record.value.lost_count;
    if (decided === 0) return null;
    return Math.round((record.value.won_count / decided) * 100);
});

const unitsLabel = computed(() => {
    const u = record.value.units;
    if (u > 0) return `+${u.toFixed(2)}u`;
    if (u < 0) return `${u.toFixed(2)}u`;
    return `0.00u`;
});

const unitsColor = computed(() => {
    if (record.value.units > 0) return 'text-emerald-400';
    if (record.value.units < 0) return 'text-rose-400';
    return 'text-slate-400';
});
</script>

<template>
    <Head title="Watch Record — Betslip Pirates" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-[1200px] px-4 py-12 md:px-6 lg:px-8">
            <Link
                href="/watchlist"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to watchlist
            </Link>

            <!-- Header -->
            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <div class="flex items-center gap-2">
                    <p
                        class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                    >
                        Watch Record
                    </p>
                    <InfoPopover title="How the record works">
                        <p>
                            Every slip you watch is logged here once it
                            settles. Each one counts as a flat 1 unit stake
                            at the slip's listed total odds.
                        </p>
                        <div
                            class="rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-[11px]"
                        >
                            <p class="text-emerald-400">
                                Win: + (odds − 1) units
                            </p>
                            <p class="text-rose-400">Loss: − 1 unit</p>
                            <p class="text-slate-400">Void: 0 units</p>
                        </div>
                        <p class="text-slate-400">
                            This is a hypothetical P/L — you never staked
                            real money on watched slips. Use it to see which
                            sellers you'd have made money following.
                        </p>
                    </InfoPopover>
                </div>
                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    Your Watch Record
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400">
                    A paper-trade history of every slip you've watched and
                    how it settled. No money changed hands — this is your
                    personal read on which sellers deliver.
                </p>
            </div>

            <!-- Empty state -->
            <div
                v-if="record.settled_count === 0"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] p-12 text-center"
            >
                <p
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                >
                    No settled watches yet
                </p>
                <p class="mt-3 text-sm text-slate-400">
                    Watch a few betslips and come back once they resolve.
                    Your record starts building from the very first settled
                    slip.
                </p>
                <Link
                    href="/marketplace"
                    class="mt-6 inline-block rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
                >
                    Browse marketplace
                </Link>
            </div>

            <template v-else>
                <!-- Aggregate stat row -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div
                        class="rounded border border-[#232d42] bg-[#111622] p-4"
                    >
                        <span
                            class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Paper P/L
                        </span>
                        <p
                            class="mt-2 font-mono text-2xl font-black"
                            :class="unitsColor"
                        >
                            {{ unitsLabel }}
                        </p>
                        <p class="mt-1 text-[10px] text-slate-500">
                            flat 1u per slip
                        </p>
                    </div>

                    <div
                        class="rounded border border-[#232d42] bg-[#111622] p-4"
                    >
                        <span
                            class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Win Rate
                        </span>
                        <p
                            class="mt-2 font-mono text-2xl font-black text-white"
                        >
                            {{
                                record.has_enough_data
                                    ? winRate + '%'
                                    : '—'
                            }}
                        </p>
                        <p class="mt-1 text-[10px] text-slate-500">
                            {{
                                record.has_enough_data
                                    ? `${record.won_count} won / ${record.won_count + record.lost_count} decided`
                                    : 'not enough data yet'
                            }}
                        </p>
                    </div>

                    <div
                        class="rounded border border-[#232d42] bg-[#111622] p-4"
                    >
                        <span
                            class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Watched
                        </span>
                        <p
                            class="mt-2 font-mono text-2xl font-black text-white"
                        >
                            {{ record.settled_count }}
                        </p>
                        <p class="mt-1 text-[10px] text-slate-500">
                            settled slips
                        </p>
                    </div>

                    <div
                        class="rounded border border-[#232d42] bg-[#111622] p-4"
                    >
                        <span
                            class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Breakdown
                        </span>
                        <p class="mt-2 font-mono text-sm font-black">
                            <span class="text-emerald-400"
                                >{{ record.won_count }}W</span
                            >
                            <span class="mx-1 text-slate-600">·</span>
                            <span class="text-rose-400"
                                >{{ record.lost_count }}L</span
                            >
                            <span class="mx-1 text-slate-600">·</span>
                            <span class="text-slate-400"
                                >{{ record.voided_count }}V</span
                            >
                        </p>
                        <p class="mt-1 text-[10px] text-slate-500">
                            wins · losses · voids
                        </p>
                    </div>
                </div>

                <!-- Insufficient data notice -->
                <div
                    v-if="!record.has_enough_data"
                    class="mt-6 rounded border border-amber-500/20 bg-amber-500/5 p-4"
                >
                    <p
                        class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                    >
                        Not enough data yet
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Watch at least 3 slips that settle before drawing
                        conclusions. The record is here, but it's early.
                    </p>
                </div>

                <!-- Per-seller breakdown -->
                <section class="mt-10">
                    <div
                        class="mb-4 flex items-center justify-between border-b border-[#232d42] pb-3"
                    >
                        <h2
                            class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            By Seller
                        </h2>
                        <span
                            class="font-mono text-[10px] text-slate-500 uppercase"
                        >
                            {{ record.sellers.length }} seller(s)
                        </span>
                    </div>

                    <div
                        v-if="record.sellers.length === 0"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-8 text-center"
                    >
                        <p class="text-xs text-slate-500">
                            Watch at least 2 settled slips from the same
                            seller to see them appear here.
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="s in record.sellers"
                            :key="s.seller_id"
                            :href="`/profile/${s.seller_code}`"
                            class="block rounded-lg border border-[#232d42] bg-[#161c2a] p-4 transition-colors hover:border-sky-500/40"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p
                                        class="truncate font-mono text-sm font-black tracking-wide text-slate-200 uppercase"
                                    >
                                        {{ s.seller_name }}
                                    </p>
                                    <p
                                        class="mt-0.5 truncate text-[10px] text-slate-500"
                                    >
                                        Code: {{ s.seller_code }} ·
                                        {{ s.settled_count }} watched slips
                                    </p>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <p
                                        class="font-mono text-lg font-black"
                                        :class="
                                            s.units > 0
                                                ? 'text-emerald-400'
                                                : s.units < 0
                                                  ? 'text-rose-400'
                                                  : 'text-slate-400'
                                        "
                                    >
                                        {{
                                            s.units > 0
                                                ? '+'
                                                : ''
                                        }}{{ s.units.toFixed(2) }}u
                                    </p>
                                    <p
                                        class="text-[10px] text-slate-500 uppercase"
                                    >
                                        {{ s.won_count }}W ·
                                        {{ s.lost_count }}L ·
                                        {{ s.voided_count }}V
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </section>
            </template>
        </div>

        <Footer />
    </div>
</template>