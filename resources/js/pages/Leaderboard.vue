<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Footer from '@/components/Footer.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import { computed } from 'vue';

const page = usePage();
const leaders = computed(() => page.props.leaders);
const window = computed(() => page.props.window);

const hero = computed(() => {
    // Only page 1 has a hero — the #1 seller.
    if (leaders.value.current_page !== 1) return null;
    return leaders.value.data[0] ?? null;
});

const restOfList = computed(() => {
    // On page 1, skip the hero row from the table below.
    if (leaders.value.current_page === 1) {
        return leaders.value.data.slice(1);
    }
    return leaders.value.data;
});

const windows = [
    { key: '30d', label: 'Last 30 days' },
    { key: '90d', label: 'Last 90 days' },
    { key: 'all', label: 'All time' },
];

const setWindow = (key) => {
    router.get(
        '/leaderboard',
        { window: key },
        { preserveScroll: true, preserveState: false },
    );
};

const rangeLabel = computed(() => {
    const from = leaders.value.from ?? 0;
    const to = leaders.value.to ?? 0;
    const total = leaders.value.total ?? 0;

    if (total === 0) return 'No sellers yet';
    return `Ranks ${from}–${to} of ${total}`;
});

const unitsClass = (units) => {
    if (units > 0) return 'text-emerald-400';
    if (units < 0) return 'text-rose-400';
    return 'text-slate-400';
};

const unitsLabel = (units) => {
    const sign = units > 0 ? '+' : '';
    return `${sign}${Number(units).toFixed(2)}u`;
};

const formDotClass = (mark) => {
    if (mark === 'W')
        return 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-400';
    if (mark === 'L')
        return 'border border-rose-500/30 bg-rose-500/10 text-rose-400';
    return 'border border-slate-500/30 bg-slate-500/10 text-slate-400';
};
</script>

<template>
    <Head title="Leaderboard — Betslip Pirates" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-[1200px] px-2 py-12 md:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 border-b border-[#232d42] pb-6">
                <div class="flex items-center gap-2">
                    <p
                        class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                    >
                        Leaderboard
                    </p>
                    <InfoPopover title="How the leaderboard works">
                        <p>
                            Sellers are ranked by <strong>units won</strong>
                            at a flat 1 unit stake per settled slip.
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
                        <p>
                            Every seller with at least one settled slip appears.
                            Ties are broken by slip count, then name.
                        </p>
                        <p class="text-slate-400">
                            The board refreshes every 10 minutes.
                        </p>
                    </InfoPopover>
                </div>
                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    Top Tipsters
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400"
                >
                    Every seller ranked by how much they'd have returned on a
                    flat 1u stake. Use this to find who's worth following.
                </p>
            </div>

            <!-- Window toggle -->
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <button
                    v-for="w in windows"
                    :key="w.key"
                    type="button"
                    @click="setWindow(w.key)"
                    :class="[
                        'cursor-pointer rounded border px-4 py-2 text-[10px] font-black tracking-widest uppercase transition-all',
                        window === w.key
                            ? 'border-sky-500/60 bg-sky-500/10 text-sky-300'
                            : 'border-[#232d42] bg-[#111622] text-slate-400 hover:border-sky-500/30 hover:text-sky-400',
                    ]"
                >
                    {{ w.label }}
                </button>

                <span
                    class="ml-auto font-mono text-[10px] tracking-widest text-slate-500 uppercase"
                >
                    {{ rangeLabel }}
                </span>
            </div>

            <!-- Empty state -->
            <div
                v-if="leaders.data.length === 0"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] p-12 text-center"
            >
                <p
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                >
                    No sellers ranked yet
                </p>
                <p class="mt-3 text-sm text-slate-400">
                    The leaderboard fills as sellers publish and settle
                    betslips. Check back soon.
                </p>
                <Link
                    href="/marketplace"
                    class="mt-6 inline-block rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
                >
                    Browse marketplace
                </Link>
            </div>

            <template v-else>
                <!-- HERO: #1 -->
                <div
                    v-if="hero"
                    class="mb-6 overflow-hidden rounded-lg border border-amber-500/30 bg-gradient-to-br from-amber-500/5 via-transparent to-transparent"
                >
                    <div
                        class="flex flex-col gap-5 p-6 md:flex-row md:items-center"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full border-2 border-amber-500/40 bg-amber-500/10 font-mono text-2xl font-black text-amber-400"
                            >
                                #1
                            </div>

                            <img
                                :src="hero.avatar"
                                :alt="hero.name"
                                class="h-16 w-16 flex-shrink-0 rounded-full border border-amber-500/30 object-cover"
                            />

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p
                                        class="truncate font-mono text-lg font-black tracking-wide text-white uppercase"
                                    >
                                        {{ hero.name }}
                                    </p>
                                    <span
                                        v-if="hero.is_verified"
                                        class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full bg-sky-500 text-[8px] font-black text-[#070b14]"
                                        title="Verified"
                                        >✓</span
                                    >
                                </div>
                                <p
                                    class="mt-0.5 font-mono text-[11px] text-slate-500"
                                >
                                    {{ hero.code }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="grid flex-1 grid-cols-3 gap-4 md:justify-items-end"
                        >
                            <div>
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    Units
                                </span>
                                <span
                                    class="mt-1 block font-mono text-xl font-black"
                                    :class="unitsClass(hero.units)"
                                >
                                    {{ unitsLabel(hero.units) }}
                                </span>
                            </div>
                            <div>
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    Win Rate
                                </span>
                                <span
                                    class="mt-1 block font-mono text-xl font-black text-white"
                                >
                                    {{ hero.win_rate }}%
                                </span>
                            </div>
                            <div>
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    Settled
                                </span>
                                <span
                                    class="mt-1 block font-mono text-xl font-black text-white"
                                >
                                    {{ hero.settled_count }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent form strip -->
                    <div
                        class="flex items-center gap-2 border-t border-amber-500/20 px-6 py-3"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Recent form
                        </span>
                        <div class="flex gap-1">
                            <span
                                v-for="(mark, i) in hero.recent_form"
                                :key="i"
                                :class="[
                                    'flex h-5 w-5 items-center justify-center rounded-sm font-mono text-[9px] font-black',
                                    formDotClass(mark),
                                ]"
                            >
                                {{ mark }}
                            </span>
                        </div>
                        <Link
                            :href="`/profile/${hero.code}`"
                            class="ml-auto text-[10px] font-black tracking-widest text-amber-400 uppercase hover:text-amber-300"
                        >
                            View profile →
                        </Link>
                    </div>
                </div>

                <!-- TABLE: ranks 2-N -->
                <div
                    v-if="restOfList.length > 0"
                    class="overflow-hidden rounded-lg border border-[#232d42] bg-[#111622]/40"
                >
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr
                                class="border-b border-[#232d42] bg-[#0f1422] font-mono text-[10px] font-black tracking-widest text-slate-500 uppercase"
                            >
                                <th class="w-16 px-4 py-3">Rank</th>
                                <th class="px-4 py-3">Seller</th>
                                <th class="px-4 py-3 text-right">Units</th>
                                <th class="px-4 py-3 text-right">Win Rate</th>
                                <th
                                    class="hidden px-4 py-3 text-right md:table-cell"
                                >
                                    Settled
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-right lg:table-cell"
                                >
                                    Form
                                </th>
                                <th class="w-20 px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in restOfList"
                                :key="row.user_id"
                                class="border-b border-[#232d42]/40 transition-colors hover:bg-[#111a30]/40"
                            >
                                <td
                                    class="px-4 py-3 font-mono text-sm font-black text-slate-500"
                                >
                                    #{{ row.rank }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="row.avatar"
                                            :alt="row.name"
                                            class="h-8 w-8 flex-shrink-0 rounded-full border border-[#232d42] object-cover"
                                        />
                                        <div class="min-w-0">
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <span
                                                    class="truncate font-mono text-sm font-black tracking-wide text-white"
                                                >
                                                    {{ row.name }}
                                                </span>
                                                <span
                                                    v-if="row.is_verified"
                                                    class="flex h-3.5 w-3.5 flex-shrink-0 items-center justify-center rounded-full bg-sky-500 text-[7px] font-black text-[#070b14]"
                                                    title="Verified"
                                                    >✓</span
                                                >
                                            </div>
                                            <p
                                                class="mt-0.5 truncate font-mono text-[10px] text-slate-500"
                                            >
                                                {{ row.code }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td
                                    class="px-4 py-3 text-right font-mono text-sm font-black"
                                    :class="unitsClass(row.units)"
                                >
                                    {{ unitsLabel(row.units) }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right font-mono text-sm font-black text-white"
                                >
                                    {{ row.win_rate }}%
                                </td>

                                <td
                                    class="hidden px-4 py-3 text-right font-mono text-sm text-slate-400 md:table-cell"
                                >
                                    {{ row.settled_count }}
                                </td>

                                <td class="hidden px-4 py-3 lg:table-cell">
                                    <div class="flex justify-end gap-0.5">
                                        <span
                                            v-for="(mark, i) in row.recent_form"
                                            :key="i"
                                            :class="[
                                                'flex h-4 w-4 items-center justify-center rounded-sm font-mono text-[8px] font-black',
                                                formDotClass(mark),
                                            ]"
                                        >
                                            {{ mark }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="`/profile/${row.code}`"
                                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:text-sky-300"
                                    >
                                        View →
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="leaders.last_page > 1"
                    class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#232d42] bg-[#111622]/40 px-4 py-3"
                >
                    <Link
                        v-if="leaders.prev_page_url"
                        :href="leaders.prev_page_url"
                        preserve-scroll
                        class="rounded border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/40 hover:text-sky-400"
                    >
                        ← Previous
                    </Link>
                    <span v-else class="w-24"></span>

                    <span
                        class="font-mono text-[10px] tracking-widest text-slate-500 uppercase"
                    >
                        Page {{ leaders.current_page }} of
                        {{ leaders.last_page }}
                    </span>

                    <Link
                        v-if="leaders.next_page_url"
                        :href="leaders.next_page_url"
                        preserve-scroll
                        class="rounded border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/40 hover:text-sky-400"
                    >
                        Next →
                    </Link>
                    <span v-else class="w-24"></span>
                </div>
            </template>
        </div>

        <Footer />
    </div>
</template>
