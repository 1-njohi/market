<template>
    <Head :title="`Betslip ${slip.code} — Betslip Pirates`" />

    <div
        class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8"
    >
        <div class="mx-auto w-full max-w-3xl">
            <!-- Back -->
            <Link
                href="/marketplace"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to marketplace
            </Link>

            <!-- ═══ HERO ═══ -->
            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <span
                        class="rounded border border-[#232d42] bg-[#111622] px-2.5 py-1 font-mono text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                    >
                        {{ slip.code }}
                    </span>
                    <span
                        :class="[
                            'rounded border px-2.5 py-1 text-[9px] font-black tracking-widest uppercase',
                            slip.status === 'pending'
                                ? 'border-amber-500/20 bg-amber-500/10 text-amber-400'
                                : slip.status === 'underway'
                                  ? 'border-sky-500/20 bg-sky-500/10 text-sky-400'
                                  : slip.is_winner
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                    : 'border-rose-500/20 bg-rose-500/10 text-rose-400',
                        ]"
                    >
                        {{ statusLabel }}
                    </span>
                    <!-- WATCH BUTTON -->
                    <WatchButton
                        v-if="slip.can_watch !== false"
                        :code="slip.code"
                        :watching="slip.is_watching === true"
                    />
                </div>

                <h1
                    class="text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    {{ slip.statistics.total_legs }}-selection betslip
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-relaxed font-medium text-slate-400"
                >
                    <template v-if="isLocked">
                        Unlock this betslip to see the seller's actual picks. If
                        the slip loses, you get your money back automatically.
                    </template>
                    <template v-else>
                        You have full access to the picks and odds on this slip.
                    </template>
                </p>

                <!-- Summary tiles -->
                <div class="mt-6 grid grid-cols-3 gap-3">
                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <span
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Total odds
                        </span>
                        <span
                            class="mt-1 block font-mono text-lg font-black text-emerald-400"
                        >
                            {{ slip.total_odds.toFixed(2) }}×
                        </span>
                    </div>
                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <span
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Price to unlock
                        </span>
                        <span
                            class="mt-1 block font-mono text-lg font-black text-white"
                        >
                            KES
                            {{
                                Number(
                                    slip.purchase_info.price_to_purchase,
                                ).toLocaleString('en-KE')
                            }}
                        </span>
                    </div>
                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <span
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            If it wins
                        </span>
                        <span
                            class="mt-1 block font-mono text-lg font-black text-emerald-400"
                        >
                            {{
                                fmtCompact(slip.purchase_info.potential_winning)
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ═══ SELLER ═══ -->
            <Link
                :href="`/profile/${slip.seller.code}`"
                class="group mb-6 block overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a] transition-colors hover:border-sky-500/40"
            >
                <header
                    class="flex items-center justify-between border-b border-[#232d42] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Seller
                    </span>
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase transition-colors group-hover:text-sky-400"
                    >
                        View profile →
                    </span>
                </header>

                <div class="flex flex-wrap items-center gap-4 px-5 py-4">
                    <img
                        :src="slip.seller.avatar"
                        :alt="slip.seller.name"
                        class="h-12 w-12 flex-shrink-0 rounded border border-sky-500/30 bg-[#0a101f] object-cover"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate font-mono text-sm font-black tracking-wide text-slate-200 uppercase"
                        >
                            {{ slip.seller.name }}
                        </p>
                        <p class="mt-0.5 truncate text-[11px] text-slate-500">
                            {{ slip.seller.statistics.total_betslips }} settled
                            betslips · since {{ slip.seller.member_since }}
                        </p>
                    </div>

                    <!-- Recent form -->
                    <div class="flex flex-col items-end">
                        <div class="flex gap-1">
                            <span
                                v-for="(result, index) in slip.seller.statistics
                                    .recent_form"
                                :key="index"
                                :class="[
                                    'h-3 w-3 rounded-sm border',
                                    result === 'W'
                                        ? 'border-emerald-500 bg-emerald-500/20'
                                        : result === 'L'
                                          ? 'border-rose-500 bg-rose-500/20'
                                          : 'border-slate-600 bg-slate-600/20',
                                ]"
                                :title="
                                    result === 'W'
                                        ? 'Won'
                                        : result === 'L'
                                          ? 'Lost'
                                          : 'Pending'
                                "
                            ></span>
                        </div>
                        <span
                            class="mt-1.5 text-[9px] font-bold tracking-wider text-slate-500 uppercase"
                        >
                            Recent form
                        </span>
                    </div>
                </div>

                <div
                    class="grid grid-cols-3 divide-x divide-[#232d42]/60 border-t border-[#232d42]"
                >
                    <div class="p-4">
                        <span
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Win rate
                        </span>
                        <span
                            class="mt-1 block font-mono text-lg font-black text-sky-400"
                        >
                            {{ slip.seller.statistics.win_rate }}%
                        </span>
                    </div>
                    <div class="p-4">
                        <span
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            ROI
                        </span>
                        <span
                            class="mt-1 block font-mono text-lg font-black text-emerald-400"
                        >
                            +{{ slip.seller.statistics.roi }}%
                        </span>
                    </div>
                    <div class="p-4">
                        <span
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Won
                        </span>
                        <span
                            class="mt-1 block font-mono text-lg font-black text-slate-200"
                        >
                            {{ slip.seller.statistics.won_betslips }}
                        </span>
                    </div>
                </div>
            </Link>

            <!-- ═══ SELECTIONS ═══ -->
            <section
                class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <header
                    class="flex items-center justify-between border-b border-[#232d42] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Selections
                    </span>
                    <span
                        class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                    >
                        {{ slip.legs.length }}
                        {{ slip.legs.length === 1 ? 'match' : 'matches' }}
                    </span>
                </header>

                <ul class="divide-y divide-[#232d42]/60">
                    <li
                        v-for="leg in slip.legs"
                        :key="leg.id"
                        class="flex items-start justify-between gap-4 px-5 py-4"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-[10px] font-black tracking-widest text-slate-500 uppercase"
                            >
                                {{ leg.fixture.league }}
                            </p>
                            <p
                                class="mt-1 truncate text-sm font-bold text-slate-200"
                            >
                                {{ leg.fixture.home_team }}
                                <span class="text-slate-500">vs</span>
                                {{ leg.fixture.away_team }}
                            </p>
                            <p
                                class="mt-1 truncate text-[11px] font-semibold text-sky-400"
                            >
                                {{ leg.market.name }}
                            </p>
                        </div>

                        <div class="flex-shrink-0 text-right">
                            <template v-if="isLocked">
                                <span
                                    class="inline-flex items-center gap-1 rounded border border-amber-500/20 bg-amber-500/5 px-2 py-0.5 text-[10px] font-black tracking-widest text-amber-500/80 uppercase"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                        class="h-3 w-3"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                                        />
                                    </svg>
                                    Locked
                                </span>
                                <p
                                    class="mt-1.5 font-mono text-sm font-black text-slate-700"
                                >
                                    —.——
                                </p>
                            </template>
                            <template v-else>
                                <p
                                    class="font-mono text-sm font-black text-emerald-400"
                                >
                                    {{ Number(leg.odds).toFixed(2) }}
                                </p>
                                <p
                                    class="mt-0.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                                >
                                    {{ leg.selection }}
                                </p>
                            </template>
                        </div>
                    </li>
                </ul>

                <footer
                    class="flex items-center justify-between border-t border-[#232d42] bg-[#0f1422] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Total odds
                    </span>
                    <span class="font-mono text-lg font-black text-emerald-400">
                        {{ slip.total_odds.toFixed(2) }}×
                    </span>
                </footer>
            </section>

            <!-- ═══ ACTION ═══ -->
            <section
                class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <span
                            class="block text-[10px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            {{
                                isLocked
                                    ? 'Unlock this betslip'
                                    : 'You have access'
                            }}
                        </span>
                        <p class="mt-1 text-[11px] text-slate-500">
                            {{ slip.purchase_info.message }}
                        </p>
                    </div>

                    <div v-if="isLocked" class="flex-shrink-0 text-right">
                        <span
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Price
                        </span>
                        <span
                            class="mt-1 block font-mono text-xl font-black text-emerald-400"
                        >
                            KES
                            {{
                                Number(
                                    slip.purchase_info.price_to_purchase,
                                ).toLocaleString('en-KE')
                            }}
                        </span>
                    </div>
                </div>

                <div v-if="isLocked" class="mt-4">
                    <UnlockPopover
                        :amount="slip.purchase_info.price_to_purchase"
                        :betslip_id="slip.id"
                    />
                </div>

                <div
                    class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[#232d42]/60 pt-4"
                >
                    <button
                        type="button"
                        @click="copyToClipboard(slip.code)"
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
                    >
                        Copy code — {{ slip.code }}
                    </button>
                    <Link
                        href="/marketplace"
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase hover:text-slate-300"
                    >
                        Browse more slips →
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import UnlockPopover from '@/components/UnlockPopover.vue';
import WatchButton from '@/components/WatchButton.vue';

const page = usePage();

const slip = computed(() => page.props.betslip);
const isLocked = computed(() => !slip.value.has_access);

const statusLabel = computed(() => {
    const s = slip.value.status;
    if (s === 'pending' || s === 'underway') return s;
    return slip.value.is_winner ? 'Won' : 'Lost';
});

const fmtCompact = (n) => {
    const v = Number(n || 0);
    if (Math.abs(v) >= 1_000_000)
        return 'KES ' + (v / 1_000_000).toFixed(2) + 'M';
    if (Math.abs(v) >= 1_000) return 'KES ' + (v / 1_000).toFixed(1) + 'K';
    return 'KES ' + v.toFixed(0);
};

const copyToClipboard = (text) => {
    if (typeof window === 'undefined' || !navigator) return;
    if (!navigator.clipboard) {
        fallbackCopyTextSelection(text);
        return;
    }
    navigator.clipboard
        .writeText(text)
        .then(() => alert('Code copied!'))
        .catch((err) => console.error('Failed to copy:', err));
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
