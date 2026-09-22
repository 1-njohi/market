<template>
    <div
        class="w-full overflow-hidden rounded bordd font-sans text-slate-200"
    >
        <!-- Empty state -->
        <div
            v-if="!betslips || betslips.length === 0"
            class="p-8 text-center font-mono text-xs tracking-wider text-slate-500 uppercase"
        >
            No betslips to display yet.
        </div>

        <template v-else>
            <!-- ─────── MOBILE ─────── -->
            <div class="space-y-3 py-3 px-0 md:hidden bg-[#070b14]">
                <div
                    v-for="slip in betslips"
                    :key="slip.id"
                    class="space-y-3 rounded-lg border border-[#232d42] bg-[#111622]/40 p-4 transition-colors hover:border-sky-500/20"
                >
                    <!-- Header: code + status + sold + watching -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded border border-[#232d42] bg-[#0a101f] px-2 py-0.5 font-mono text-[11px] font-black tracking-wider text-white"
                        >
                            {{ slip.code }}
                        </span>

                        <span
                            v-if="
                                slip.status === 'pending' ||
                                slip.status === 'underway'
                            "
                            :class="[
                                'rounded border px-2 py-0.5 font-mono text-[9px] font-black tracking-wider uppercase',
                                slip.status === 'pending'
                                    ? 'border-amber-500/20 bg-amber-500/5 text-amber-400'
                                    : 'border-purple-500/20 bg-purple-500/5 text-purple-400',
                            ]"
                        >
                            {{ slip.status }}
                        </span>
                        <span
                            v-else
                            :class="[
                                'rounded border px-2 py-0.5 font-mono text-[9px] font-black tracking-wider uppercase',
                                slip.is_winner
                                    ? 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400'
                                    : 'border-rose-500/20 bg-rose-500/5 text-rose-400',
                            ]"
                        >
                            {{ slip.is_winner ? 'WON' : 'LOST' }}
                        </span>

                        <span
                            :class="[
                                'rounded border px-2 py-0.5 font-mono text-[9px] font-black tracking-wider uppercase',
                                slip.purchases < 1
                                    ? 'border-slate-500/20 bg-slate-500/5 text-slate-400'
                                    : 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400',
                            ]"
                        >
                            {{ slip.purchases }} sold
                        </span>

                        <span
                            v-if="slip.watch_count > 0"
                            class="flex items-center gap-1 rounded border border-sky-500/20 bg-sky-500/5 px-2 py-0.5 font-mono text-[9px] font-black tracking-wider text-sky-400 uppercase"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-2.5 w-2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                />
                            </svg>
                            {{ slip.watch_count }} watching
                        </span>
                    </div>

                    <!-- Stats grid -->
                    <div
                        class="grid grid-cols-2 gap-x-4 gap-y-3 rounded border border-[#232d42]/70 bg-[#0a101f] p-3"
                    >
                        <div>
                            <span
                                class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                            >
                                Markets
                            </span>
                            <span
                                class="mt-0.5 block font-mono text-xs font-black text-white"
                            >
                                {{ slip.legs }}
                                {{ slip.legs === 1 ? 'Leg' : 'Legs' }}
                            </span>
                        </div>

                        <div>
                            <span
                                class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                            >
                                Total Odds
                            </span>
                            <span
                                class="mt-0.5 block font-mono text-xs font-black text-amber-400"
                            >
                                {{ Number(slip.total_odds).toFixed(2) }}
                            </span>
                        </div>

                        <div>
                            <span
                                class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                            >
                                Price Point
                            </span>
                            <span
                                class="mt-0.5 block font-mono text-xs font-black text-emerald-400"
                            >
                                KES {{ slip.price.toLocaleString() }}
                            </span>
                        </div>

                        <div>
                            <span
                                class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                            >
                                Markets Left
                            </span>
                            <span
                                class="mt-0.5 block font-mono text-xs font-black"
                                :class="
                                    slip.is_expiring_soon
                                        ? 'text-rose-400'
                                        : 'text-sky-400'
                                "
                            >
                                {{ slip.remaining }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <Link
                            :href="`/betslip/view/g/${slip.code}`"
                            class="flex flex-1 items-center justify-center rounded border border-sky-500/30 bg-sky-500/5 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-all hover:border-sky-500 hover:bg-sky-500 hover:text-[#070b14]"
                        >
                            Inspect
                        </Link>
                        <button
                            type="button"
                            @click="shareSlip(slip.code, slip.total_odds)"
                            class="flex flex-1 cursor-pointer items-center justify-center rounded border border-[#232d42] bg-[#111622] py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-all hover:border-slate-600 hover:text-slate-200"
                        >
                            Share
                        </button>
                    </div>
                </div>
            </div>

            <!-- ─────── DESKTOP ─────── -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-800/60 bg-[#0f1422] font-mono text-[10px] font-black tracking-widest text-slate-400 uppercase"
                        >
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Markets</th>
                            <th class="px-4 py-3">Odds</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">
                                Remaining / Sold / Watching
                            </th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/30">
                        <tr
                            v-for="slip in betslips"
                            :key="slip.id"
                            class="group border-b border-gray-800/20 bg-[#111622]/10 text-xs transition-colors hover:bg-[#111622]/40"
                        >
                            <td
                                class="px-4 py-3.5 font-mono font-black tracking-wider text-white"
                            >
                                {{ slip.code }}
                            </td>

                            <td
                                class="px-4 py-3.5 font-mono font-bold text-slate-300"
                            >
                                {{ slip.legs }}
                            </td>

                            <td
                                class="px-4 py-3.5 font-mono font-black text-amber-400"
                            >
                                {{ Number(slip.total_odds).toFixed(2) }}
                            </td>

                            <td
                                class="px-4 py-3.5 font-mono font-bold text-emerald-400"
                            >
                                KES {{ slip.price.toLocaleString() }}
                            </td>

                            <td
                                class="px-4 py-3.5 font-mono text-[11px] font-semibold"
                            >
                                <span
                                    :class="
                                        slip.is_expiring_soon
                                            ? 'font-bold text-rose-400'
                                            : 'text-sky-400'
                                    "
                                >
                                    {{ slip.remaining }} left
                                </span>
                                <span class="ml-1 text-[10px] text-slate-500">
                                    · {{ slip.purchases || 0 }} sold
                                </span>
                                <span
                                    v-if="slip.watch_count > 0"
                                    class="ml-1 text-[10px] text-sky-400"
                                >
                                    · {{ slip.watch_count }} watching
                                </span>
                            </td>

                            <td class="px-4 py-3.5">
                                <span
                                    v-if="
                                        slip.status === 'pending' ||
                                        slip.status === 'underway'
                                    "
                                    :class="[
                                        'rounded border px-2 py-0.5 font-mono text-[9px] font-black uppercase',
                                        slip.status === 'pending'
                                            ? 'border-amber-500/20 bg-amber-500/5 text-amber-400'
                                            : 'border-purple-500/20 bg-purple-500/5 text-purple-400',
                                    ]"
                                >
                                    {{ slip.status }}
                                </span>
                                <span
                                    v-else
                                    :class="[
                                        'rounded border px-2 py-0.5 font-mono text-[9px] font-black uppercase',
                                        slip.is_winner
                                            ? 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400'
                                            : 'border-rose-500/20 bg-rose-500/5 text-rose-400',
                                    ]"
                                >
                                    {{ slip.is_winner ? 'WON' : 'LOST' }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <Link
                                    :href="`/betslip/view/g/${slip.code}`"
                                    class="inline-block h-7 rounded border border-sky-500/20 bg-sky-500/5 px-3 py-1.5 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-all duration-200 hover:border-sky-500 hover:bg-sky-500 hover:text-white"
                                >
                                    Inspect
                                </Link>

                                <button
                                    type="button"
                                    @click="
                                        shareSlip(slip.code, slip.total_odds)
                                    "
                                    class="ml-2 inline-block h-7 cursor-pointer rounded border border-[#232d42] bg-[#111622] px-3 py-1.5 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-all duration-200 hover:border-slate-600 hover:text-slate-200"
                                >
                                    Share
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    betslips: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const shareSlip = (code, total_odds) => {
    const shareUrl = window.location.origin + '/betslip/view/g/' + code;
    const shareText = `Check out my betslip with total odds of ${total_odds}! `;

    if (navigator.share) {
        navigator
            .share({
                title: 'Betslip',
                text: shareText,
                url: shareUrl,
            })
            .catch((err) => {
                console.warn('Share cancelled or failed:', err);
            });
    } else {
        navigator.clipboard
            .writeText(shareUrl)
            .then(() => {
                alert('Link copied to clipboard! Share it anywhere.');
            })
            .catch(() => {
                fallbackCopyTextSelection(shareUrl);
            });
    }
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
        document.execCommand('copy');
    } catch (err) {
        console.error('Fallback copy failed', err);
    }
    document.body.removeChild(textArea);
};
</script>
