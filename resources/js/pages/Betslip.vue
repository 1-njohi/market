<template>
    <div
        class="max- m-6 mx-auto flex flex-col overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a] px-2 font-sans text-slate-300 shadow-2xl select-none md:w-[700px]"
    >
        <!-- HEADER PANEL -->
        <div
            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
        >
            <div class="flex cursor-pointer items-center space-x-3">
                <!-- Seller Avatar Placeholder -->
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-blue-500/40 bg-blue-600/20 text-sm font-bold tracking-wider text-blue-400"
                >
                    {{ slip.seller.name.substring(0, 2).toUpperCase() }}
                </div>
                <div>
                    <h4
                        class="text-xs font-bold tracking-wider text-gray-200 uppercase"
                    >
                        {{ slip.seller.name }}
                    </h4>
                    <div
                        class="mt-0.5 flex items-center space-x-1 text-[10px] font-semibold tracking-wide text-gray-400 uppercase"
                    >
                        <span
                            >ROI:
                            <span class="font-bold text-green-400"
                                >+{{ slip.seller.statistics.roi }}%</span
                            ></span
                        >
                        <span class="text-gray-600">•</span>
                        <span
                            >Win Rate:
                            <span class="font-bold text-blue-400"
                                >{{ slip.seller.statistics.win_rate }}%</span
                            ></span
                        >
                    </div>
                </div>
            </div>

            <!-- Verified Mini-Heatmap Row -->
            <div class="flex flex-col items-end">
                <div class="flex space-x-1">
                    <span
                        v-for="(status, index) in slip.seller.statistics
                            .recent_form"
                        :key="index"
                        :class="[
                            'h-3 w-3 rounded-sm border-[0.5px]',
                            status === 'W'
                                ? 'border-green-500 bg-green-500/20 shadow-sm shadow-green-500/10'
                                : status == 'L'
                                  ? 'border-red-500 bg-red-500/20'
                                  : 'border-gray-500 bg-gray-500/20',
                        ]"
                        :title="
                            status === 'W'
                                ? 'Won'
                                : status == 'L'
                                  ? 'Lost'
                                  : 'No Bet'
                        "
                    ></span>
                </div>
                <span
                    class="mt-2 text-[9px] font-bold tracking-wider text-gray-500 uppercase"
                >
                    Recent Form (Verified)
                </span>
            </div>
        </div>

        <div
            class="items-center justify-between px-4 pt-3 pb-1.5 text-xs font-bold tracking-wider uppercase md:flex"
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
                        d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18"
                    />
                </svg>
                <span
                    >{{ slip.statistics.total_legs }}-Selections
                    Accumulator</span
                >
            </div>
            <br />
            <div class="text-gray-200">
                Total Odds:
                <span class="text-sm font-extrabold text-amber-400">
                    {{ slip.total_odds.toFixed(2) }}
                </span>
            </div>
        </div>

        <div
            class="flex items-center justify-between border-t border-gray-800/10 px-4 pt-1.5 pb-3"
        >
            <div
                class="flex items-center gap-1.5 text-[10px] font-bold tracking-widest text-[#64748b] uppercase"
            >
                <span>TRACKING ID:</span>
                <span
                    class="rounded border border-[#232d42] bg-[#111622] px-2 py-0.5 font-mono text-[11px] text-slate-400"
                >
                    {{ slip.code }}
                </span>

                <span
                    class="rounded border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold tracking-widest text-emerald-400 uppercase"
                    :class="[
                        'rounded border px-2 py-0.5 font-mono text-[9px] font-black uppercase',
                        slip.status === 'pending'
                            ? 'border-amber-500/20 bg-amber-500/10 text-amber-400'
                            : slip.status === 'underway'
                              ? 'border-emerald-500/20 bg-purple-500/10 text-purple-400'
                              : slip.is_winner
                                ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                : 'border-rose-500/20 bg-rose-500/10 text-rose-400',
                    ]"
                >
                    {{
                        slip.status == 'pending' || slip.status == 'underway'
                            ? slip.status
                            : slip.is_winner
                              ? 'WON'
                              : 'LOST'
                    }}
                </span>
            </div>

            <button
                @click="copyToClipboard(slip.code)"
                class="cursor-pointer text-[10px] font-black tracking-wider text-sky-400 uppercase transition-colors hover:text-sky-300"
            >
                Copy
            </button>
        </div>

        <!-- SELECTIONS PANEL LIST -->
        <div
            class="no-scrollbar divide-y divide-[#232d42]/60 overflow-y-auto bg-[#111622]/30"
        >
            <div
                v-for="leg in slip.legs"
                :key="leg.id"
                class="flex flex-col gap-1 p-4 transition-all"
            >
                <!-- Fixture Metadata (Always Visible) -->
                <span
                    class="mb-0.5 text-[9px] font-black tracking-widest text-[#64748b] uppercase"
                >
                    ⚽ {{ leg.fixture.league }}
                </span>

                <!-- Match Head to Head (Always Visible) -->
                <div
                    class="text-xs font-black tracking-wide text-slate-200 uppercase"
                >
                    {{ leg.fixture.home_team }}
                    <span class="px-0.5 font-medium text-[#64748b]">vs</span>
                    {{ leg.fixture.away_team }}
                </div>

                <!-- Market Details Row -->
                <div class="mt-2 flex items-center justify-between">
                    <div
                        class="text-[10px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                        Market:
                        <span class="font-extrabold text-slate-300">{{
                            leg.market.name
                        }}</span>
                    </div>

                    <!-- Pick / Odds Inline Lock Treatment -->
                    <div class="flex items-center gap-2">
                        <!-- SELECTION CONTROL BADGE -->
                        <span
                            class="rounded border px-2 py-0.5 text-[11px] font-black tracking-widest uppercase transition-all"
                            :class="
                                isLocked
                                    ? 'border-amber-500/20 bg-amber-500/5 text-[10px] text-amber-500/70'
                                    : 'border-sky-500/30 bg-[#242f48]/40 text-sky-400'
                            "
                        >
                            <span
                                v-if="isLocked"
                                class="flex items-center gap-1"
                            >
                                <span>🔒</span> LOCKED
                            </span>
                            <span v-else>{{ leg.selection }}</span>
                        </span>

                        <!-- ODDS VALUE DRUM BADGE -->
                        <span
                            class="rounded border px-2 py-0.5 font-mono text-xs font-black transition-all"
                            :class="
                                isLocked
                                    ? 'border-amber-500/20 bg-[#121824] text-amber-500/40'
                                    : 'border-sky-500/40 bg-[#111622] text-white'
                            "
                        >
                            {{ isLocked ? '—.——' : leg.odds.toFixed(2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="space-y-3 border-t border-[#232d42] bg-[#1a2233] bg-[#121824] p-4"
        >
            <div class="">
                <span
                    class="text-[10px] tracking-widest text-slate-400 uppercase"
                    >Access Price</span
                >
                <span class="font-mono text-sm font-black text-emerald-400">
                    KES {{ slip.purchase_info.price_to_purchase }}
                </span>
            </div>

            <!-- UNLOCK TRIGGER BUTTON (Fixed to Footer Area if Locked) -->
            <UnlockPopover
                v-if="isLocked"
                :amount="slip.purchase_info.price_to_purchase"
                :betslip_id="slip.id"
            />
        </div>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import UnlockPopover from '@/components/UnlockPopover.vue';

const page = usePage();

// Reactive — reads from page.props every time they change
const slip = computed(() => page.props.betslip);
const isLocked = computed(() => !slip.value.has_access);

const copyToClipboard = (text) => {
    if (typeof window === 'undefined' || !navigator) {
        return;
    }

    if (!navigator.clipboard) {
        fallbackCopyTextSelection(text);
        return;
    }

    navigator.clipboard
        .writeText(text)
        .then(() => {
            alert('Tracking code copied to clipboard!');
        })
        .catch((err) => {
            console.error('Failed to copy text: ', err);
        });
};

const fallbackCopyTextSelection = (text) => {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.top = '0';
    textArea.style.left = '0';
    textArea.style.position = 'fixed';

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            alert('Tracking code copied to clipboard!');
        } else {
            console.warn('Fallback copy command was unsuccessful');
        }
    } catch (err) {
        console.error('Fallback execution failed', err);
    }

    document.body.removeChild(textArea);
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
