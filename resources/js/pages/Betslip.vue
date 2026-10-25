<template>
    <Head :title="`Betslip ${slip.code} — Fainali`" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <!-- Back -->
            <Link :href="isOwner ? '/dashboard' : '/marketplace'"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">
                ← {{ isOwner ? 'Back to dashboard' : 'Back to marketplace' }}
            </Link>

            <!-- ═══ HERO ═══ -->
            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <span
                        class="rounded border border-[#232d42] bg-[#111622] px-2.5 py-1 font-mono text-[11px] font-bold tracking-widest text-slate-400 uppercase">
                        {{ slip.code }}
                    </span>
                    <span :class="[
                        'rounded border px-2.5 py-1 text-[9px] font-black tracking-widest uppercase',
                        slip.status === 'pending'
                            ? 'border-amber-500/20 bg-amber-500/10 text-amber-400'
                            : slip.status === 'underway'
                                ? 'border-sky-500/20 bg-sky-500/10 text-sky-400'
                                : slip.is_winner
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                    : 'border-rose-500/20 bg-rose-500/10 text-rose-400',
                    ]">
                        {{ statusLabel }}
                    </span>
                    <span v-if="isOwner"
                        class="rounded border border-sky-500/30 bg-sky-500/10 px-2.5 py-1 text-[9px] font-black tracking-widest text-sky-300 uppercase">
                        Your slip
                    </span>
                    <WatchButton v-if="!isOwner && slip.can_watch !== false" :code="slip.code"
                        :watching="slip.is_watching === true" />
                </div>

                <h1 class="text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    {{ slip.statistics.total_legs }}-selection betslip
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed font-medium text-slate-400">
                    <template v-if="isOwner">
                        You created this slip. Buyers only see your picks after
                        unlocking — and get their money back automatically if it
                        loses.
                    </template>
                    <template v-else>
                        {{ slip.purchase_info.message }}
                    </template>
                </p>

                <!-- Summary tiles -->
                <div class="mt-6 grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Total odds
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black text-emerald-400">
                            {{ slip.total_odds.toFixed(2) }}×
                        </span>
                    </div>
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            {{ isLocked ? 'Price to unlock' : 'Listed price' }}
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black text-white">
                            KES {{ Number(slip.purchase_info.price_to_purchase).toLocaleString('en-KE') }}
                        </span>
                    </div>
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Markets
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black text-sky-400">
                            {{ slip.statistics.unique_markets }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ═══ SELLER ═══ -->
            <Link v-if="!isOwner" :href="`/profile/${slip.seller.code}`"
                class="group mb-6 block overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a] transition-colors hover:border-sky-500/40">
                <header class="flex items-center justify-between border-b border-[#232d42] px-5 py-3">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Seller
                    </span>
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase transition-colors group-hover:text-sky-400">
                        View profile →
                    </span>
                </header>
                <div class="flex flex-wrap items-center gap-4 px-5 py-4">
                    <img :src="slip.seller.avatar" :alt="slip.seller.name"
                        class="h-12 w-12 flex-shrink-0 rounded border border-sky-500/30 bg-[#0a101f] object-cover" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate font-mono text-sm font-black tracking-wide text-slate-200 uppercase">
                                {{ slip.seller.name }}
                            </p>
                            <!-- ALWAYS SHOW REPUTATION BADGE -->
                            <ReputationBadge
                                :points="slip.seller.prediction_points ?? 0"
                                :rank="slip.seller.global_rank ?? null"
                            />
                        </div>
                        <p class="mt-0.5 truncate text-[11px] text-slate-500">
                            {{ slip.seller.statistics.total_betslips }} settled
                            betslips · since {{ slip.seller.member_since }}
                        </p>
                    </div>

                    <div class="flex flex-col items-end">
                        <div class="flex gap-1">
                            <span v-for="(result, index) in slip.seller.statistics.recent_form" :key="index" :class="[
                                'h-3 w-3 rounded-sm border',
                                result === 'W'
                                    ? 'border-emerald-500 bg-emerald-500/20'
                                    : result === 'L'
                                        ? 'border-rose-500 bg-rose-500/20'
                                        : 'border-slate-600 bg-slate-600/20',
                            ]" :title="result === 'W' ? 'Won' : result === 'L' ? 'Lost' : 'Pending'"></span>
                        </div>
                        <span class="mt-1.5 text-[9px] font-bold tracking-wider text-slate-500 uppercase">
                            Recent form
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-3 divide-x divide-[#232d42]/60 border-t border-[#232d42]">
                    <div class="p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Win rate
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black text-sky-400">
                            {{ slip.seller.statistics.win_rate }}%
                        </span>
                    </div>
                    <div class="p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            ROI
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black"
                            :class="slip.seller.statistics.roi >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                            {{ slip.seller.statistics.roi >= 0 ? '+' : '' }}{{ slip.seller.statistics.roi }}%
                        </span>
                    </div>
                    <div class="p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Won
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black text-slate-200">
                            {{ slip.seller.statistics.won_betslips }}
                        </span>
                    </div>
                </div>
            </Link>

            <!-- ═══ RECAP (partial-loss framing) ═══ -->
            <section v-if="showPartialSummary"
                class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <header
                    class="flex items-center justify-between gap-3 border-b border-[#232d42] bg-[#111a30] px-4 py-2.5">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                            Slip recap
                        </span>
                        <span
                            class="rounded border border-rose-500/30 bg-rose-500/10 px-1.5 py-0.5 text-[9px] font-black tracking-widest text-rose-400 uppercase">
                            Lost
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                            {{ pickStats.won }} of {{ pickStats.total }} legs landed
                        </span>
                        <button type="button" @click="copyRecap"
                            class="cursor-pointer text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300 hover:underline">
                            Share recap
                        </button>
                    </div>
                </header>

                <div class="grid grid-cols-2 divide-x divide-[#232d42]/60">
                    <div class="p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Picks hit
                        </span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="font-mono text-2xl font-black text-white">
                                {{ pickStats.won }}
                                <span class="text-slate-600">/</span>
                                <span class="text-slate-500">{{ pickStats.total }}</span>
                            </span>
                            <span class="font-mono text-sm font-black text-emerald-400">
                                {{ Math.round(animatedPickPct) }}%
                            </span>
                        </div>
                        <div class="mt-2 h-1 overflow-hidden rounded-full bg-[#0f1422]"
                            :title="`${pickStats.won} of ${pickStats.total} legs won`">
                            <div class="h-full rounded-full bg-emerald-500/70 transition-[width] duration-500 ease-out"
                                :style="{ width: `${pickPct}%` }"></div>
                        </div>
                    </div>

                    <div class="p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Odds reached
                        </span>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="font-mono text-2xl font-black text-white">
                                {{ oddsWon.toFixed(2) }}×
                            </span>
                            <span class="font-mono text-sm font-black text-sky-400">
                                {{ Math.round(animatedOddsPct) }}%
                            </span>
                        </div>
                        <p class="mt-0.5 font-mono text-[10px] text-slate-500">
                            of {{ totalOdds.toFixed(2) }}× target
                        </p>
                        <div class="mt-2 h-1 overflow-hidden rounded-full bg-[#0f1422]"
                            :title="`${oddsWon.toFixed(2)}× of ${totalOdds.toFixed(2)}× total`">
                            <div class="h-full rounded-full bg-sky-500/70 transition-[width] duration-500 ease-out"
                                :style="{ width: `${oddsPct}%` }"></div>
                        </div>
                    </div>
                </div>

                <div v-if="nearPayout"
                    class="flex items-center justify-between gap-3 border-t border-[#232d42]/60 bg-[#0f1422] px-4 py-2">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Almost paid
                    </span>
                    <span class="font-mono text-xs font-black text-amber-400">
                        KES {{ nearPayout.toLocaleString('en-KE', { maximumFractionDigits: 0 }) }}
                    </span>
                </div>

                <footer
                    class="border-t border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-[10px] font-semibold text-slate-400">
                    <template v-if="pickStats.lost === 1 && closestMiss">
                        One leg short —
                        <span class="text-slate-200">{{ closestMiss.selection }}</span>
                        @{{ Number(closestMiss.odds).toFixed(2) }} was the only
                        miss. That pick away from cashing {{ totalOdds.toFixed(2) }}×.
                    </template>
                    <template v-else-if="pickStats.lost === 2">
                        Two legs short. {{ pickStats.won }} landed — that's
                        {{ Math.round(pickPct) }}% of the slip correct.
                    </template>
                    <template v-else>
                        {{ pickStats.won }} of {{ pickStats.total }} picks
                        landed ({{ Math.round(pickPct) }}%) — real form to build on.
                    </template>
                </footer>

                <div v-if="!isOwner"
                    class="flex items-center justify-between gap-3 border-t border-[#232d42] bg-[#111a30] px-4 py-2">
                    <span class="text-[10px] text-slate-500">
                        Like how this seller picks?
                    </span>
                    <Link :href="`/profile/${slip.seller.code}`"
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300 hover:underline">
                        See more slips from {{ slip.seller.name }} →
                    </Link>
                </div>
            </section>

            <!-- ═══ SELECTIONS ═══ -->
            <section class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <header class="flex items-center justify-between border-b border-[#232d42] bg-[#111a30] px-4 py-2.5">
                    <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                        Selections
                    </span>
                    <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                        {{ slip.legs.length }}
                        {{ slip.legs.length === 1 ? 'match' : 'matches' }}
                    </span>
                </header>

                <ul class="divide-y divide-[#232d42]/60">
                    <li v-for="(leg, i) in slip.legs" :key="leg.id" class="px-4 py-2.5">
                        <div class="flex items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <!-- Only clickable if the fixture is finished -->
                                <Link 
                                    v-if="leg.fixture.status === 'FT' || leg.fixture.status === 'AET' || leg.fixture.status === 'PEN'"
                                    :href="`/fixtures/${leg.fixture.id}/results`"
                                    class="group flex items-center gap-2"
                                >
                                    <span class="text-[11px] text-slate-400 transition-colors group-hover:text-sky-400">
                                        <span class="text-slate-600">#{{ i + 1 }}</span>
                                        {{ leg.fixture.home_team }}
                                        <span class="text-slate-600">vs</span>
                                        {{ leg.fixture.away_team }}
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3 w-3 text-slate-600 transition-colors group-hover:text-sky-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                                <div v-else class="text-[11px] text-slate-400">
                                    <span class="text-slate-600">#{{ i + 1 }}</span>
                                    {{ leg.fixture.home_team }}
                                    <span class="text-slate-600">vs</span>
                                    {{ leg.fixture.away_team }}
                                </div>
                                
                                <!-- Market -->
                                <div class="mt-0.5 truncate text-[10px] font-bold tracking-wider text-slate-500 uppercase">
                                    {{ leg.fixture.league }} <span class="text-slate-700">·</span> {{ leg.market.name }}
                                </div>
                            </div>

                            <template v-if="isLocked">
                                <span
                                    class="inline-flex shrink-0 items-center gap-1 rounded border border-amber-500/20 bg-amber-500/5 px-2 py-0.5 text-[10px] font-black tracking-widest text-amber-500/80 uppercase">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="h-3 w-3">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    Locked
                                </span>
                                <span class="shrink-0 font-mono text-[11px] font-bold text-slate-700">
                                    —.——
                                </span>
                            </template>

                            <template v-else>
                                <span class="shrink-0 font-mono text-[11px] font-bold" :class="leg.status === 'lost'
                                    ? 'text-rose-400 line-through decoration-rose-500/60'
                                    : leg.status === 'won'
                                        ? 'text-emerald-400'
                                        : 'text-white'">
                                    {{ leg.selection }} |
                                </span>
                                <span class="shrink-0 font-mono text-[11px]"
                                    :class="leg.status === 'lost' ? 'text-slate-600' : 'text-white'">
                                    @{{ Number(leg.odds).toFixed(2) }}
                                </span>
                                <span :class="[
                                    'flex h-4 w-4 shrink-0 items-center justify-center rounded-sm font-mono text-[9px] font-black',
                                    leg.status === 'won'
                                        ? 'bg-emerald-500 text-[#070b14]'
                                        : leg.status === 'lost'
                                            ? 'bg-rose-500 text-white'
                                            : 'bg-slate-700 text-slate-500',
                                ]" :title="leg.status === 'won' ? 'Won' : leg.status === 'lost' ? 'Lost' : 'Pending'">
                                    {{ leg.status === 'won' ? '✓' : leg.status === 'lost' ? '✗' : '·' }}
                                </span>
                            </template>
                        </div>

                        <div v-if="!isLocked && leg.status === 'lost' && leg.correct_selection"
                            class="mt-1.5 flex items-center gap-2 border-l-2 border-emerald-500/40 pl-2">
                            <span class="text-[9px] font-black tracking-widest text-emerald-500 uppercase">
                                Correct pick
                            </span>
                            <span class="font-mono text-[10px] font-bold text-emerald-400">
                                {{ leg.correct_selection }}
                            </span>
                            <span class="font-mono text-[10px] text-slate-500">
                                @{{ Number(leg.correct_odds).toFixed(2) }}
                            </span>
                        </div>

                        <!-- Crowd wisdom — visible only when unlocked -->
                        <CrowdDistribution
                            v-if="!isLocked && leg.crowd_wisdom"
                            :crowd-wisdom="leg.crowd_wisdom"
                            :winning-selections="leg.result_selections" 
                            :user-selection="leg.selection"
                            :leg-status="leg.status"
                            class="mt-2"
                        />
                    </li>
                </ul>

                <footer class="flex items-center justify-between border-t border-[#232d42] bg-[#0f1422] px-4 py-2.5">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Total odds
                    </span>
                    <span class="font-mono text-lg font-black text-emerald-400">
                        {{ slip.total_odds.toFixed(2) }}×
                    </span>
                </footer>
            </section>

            <!-- ═══ ACTION ═══ -->
            <section class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                <template v-if="isOwner">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <span class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                {{ isOwner ? 'This is your betslip' : 'You have access' }}
                            </span>
                            <p class="mt-1 text-[11px] text-slate-500">
                                <template v-if="isSettled && !slip.has_access">
                                    This slip has settled, so the picks and results are public to anyone with the link.
                                </template>
                                <template v-else>
                                    {{ slip.purchase_info.message }}
                                </template>
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                                Listed price
                            </span>
                            <span class="mt-1 block font-mono text-xl font-black text-emerald-400">
                                KES {{ Number(slip.purchase_info.price_to_purchase).toLocaleString('en-KE') }}
                            </span>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <span class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                {{ isLocked ? 'Unlock this betslip' : 'You have access' }}
                            </span>
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ slip.purchase_info.message }}
                            </p>
                        </div>

                        <div v-if="isLocked" class="flex-shrink-0 text-right">
                            <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                                Price
                            </span>
                            <span class="mt-1 block font-mono text-xl font-black text-emerald-400">
                                KES {{ Number(slip.purchase_info.price_to_purchase).toLocaleString('en-KE') }}
                            </span>
                        </div>
                    </div>

                    <div v-if="isLocked" class="mt-4">
                        <UnlockPopover :amount="slip.purchase_info.price_to_purchase" :betslip_id="slip.id" />
                    </div>
                </template>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[#232d42]/60 pt-4">
                    <button type="button" @click="copyToClipboard(slip.code)"
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">
                        Copy code — {{ slip.code }}
                    </button>
                    <Link :href="isOwner ? '/dashboard' : '/marketplace'"
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase hover:text-slate-300">
                        {{ isOwner ? 'Back to dashboard →' : 'Browse more slips →' }}
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import UnlockPopover from '@/components/UnlockPopover.vue';
import WatchButton from '@/components/WatchButton.vue';
import ReputationBadge from '@/components/ReputationBadge.vue';
import CrowdDistribution from '@/components/CrowdDistribution.vue';

const page = usePage();

const slip = computed(() => page.props.betslip);
const isSettled = computed(
    () => slip.value.status !== 'pending' && slip.value.status !== 'underway',
);

const isLocked = computed(() => {
    if (isSettled.value) return false;
    return !slip.value.has_access;
});
const isOwner = computed(() => slip.value.is_owner === true);

const statusLabel = computed(() => {
    const s = slip.value.status;
    if (s === 'pending' || s === 'underway') return s;
    return slip.value.is_winner ? 'Won' : 'Lost';
});

const pickStats = computed(() => {
    const legs = slip.value.legs ?? [];
    const total = legs.length;
    if (total === 0) return null;
    const won = legs.filter((l) => l.status === 'won').length;
    const lost = legs.filter((l) => l.status === 'lost').length;
    const pending = legs.filter((l) => l.status === 'pending').length;
    return { total, won, lost, pending };
});

const oddsWon = computed(() => {
    const legs = slip.value.legs ?? [];
    return legs
        .filter((l) => l.status === 'won')
        .reduce((acc, l) => acc * Number(l.odds ?? 0), 1);
});

const totalOdds = computed(() => Number(slip.value.total_odds ?? 0));

const pickPct = computed(() => {
    const s = pickStats.value;
    if (!s || s.total === 0) return 0;
    return (s.won / s.total) * 100;
});

const oddsPct = computed(() => {
    if (!totalOdds.value) return 0;
    return Math.min(100, (oddsWon.value / totalOdds.value) * 100);
});

const showPartialSummary = computed(() => {
    if (isLocked.value) return false;
    const s = pickStats.value;
    if (!s || s.total < 4) return false;
    return s.won > 0 && s.lost > 0;
});

const closestMiss = computed(() => {
    const lost = (slip.value.legs ?? []).filter((l) => l.status === 'lost');
    if (!lost.length) return null;
    return lost.reduce((a, b) => (Number(a.odds) <= Number(b.odds) ? a : b));
});

const nearPayout = computed(() => {
    const stake = Number(slip.value.purchase_info?.price_to_purchase ?? 0);
    if (!stake || !totalOdds.value) return null;
    if (!pickStats.value) return null;
    if (pickStats.value.lost > 2) return null;
    return stake * totalOdds.value;
});

const animatedPickPct = ref(0);
const animatedOddsPct = ref(0);
let animFrame = null;

const animateTo = (targetRef, target) => {
    if (typeof window === 'undefined') {
        targetRef.value = target;
        return;
    }
    const start = performance.now();
    const from = targetRef.value;
    const dur = 550;
    const step = (t) => {
        const p = Math.min(1, (t - start) / dur);
        const eased = 1 - Math.pow(1 - p, 3);
        targetRef.value = from + (target - from) * eased;
        if (p < 1) {
            animFrame = requestAnimationFrame(step);
        }
    };
    animFrame = requestAnimationFrame(step);
};

watch(
    () => [showPartialSummary.value, pickPct.value, oddsPct.value],
    ([show]) => {
        if (!show) {
            animatedPickPct.value = 0;
            animatedOddsPct.value = 0;
            return;
        }
        animateTo(animatedPickPct, pickPct.value);
        animateTo(animatedOddsPct, oddsPct.value);
    },
    { immediate: true },
);

onMounted(() => {
    if (showPartialSummary.value) {
        animateTo(animatedPickPct, pickPct.value);
        animateTo(animatedOddsPct, oddsPct.value);
    }
});

const copyToClipboard = (text) => {
    if (typeof window === 'undefined' || !navigator) return;
    if (!navigator.clipboard) {
        fallbackCopyTextSelection(text);
        return;
    }
    navigator.clipboard
        .writeText(text)
        .then(() => alert('Copied!'))
        .catch((err) => console.error('Failed to copy:', err));
};

const copyRecap = () => {
    if (!pickStats.value) return;
    const lines = [
        `Slip ${slip.value.code} — ${pickStats.value.won}/${pickStats.value.total} picks landed, ${oddsWon.value.toFixed(2)}× of ${totalOdds.value.toFixed(2)}× total.`,
    ];
    if (pickStats.value.lost === 1 && closestMiss.value) {
        lines.push(
            `One leg short — ${closestMiss.value.selection} @${Number(closestMiss.value.odds).toFixed(2)} was the only miss.`,
        );
    } else {
        lines.push(`${pickStats.value.won} of ${pickStats.value.total} picks correct.`);
    }
    lines.push(`via @${slip.value.seller.name} on Fainali`);
    copyToClipboard(lines.join(' '));
};

const fallbackCopyTextSelection = (text) => {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.top = '0';
    textArea.style.left = '0';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
    } catch (err) {
        console.error('Fallback copy failed', err);
    }
    document.body.removeChild(textArea);
};
</script>