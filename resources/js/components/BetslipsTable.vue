<template>
    <div
        class="w-full overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40 font-sans text-slate-200"
    >
        <!-- <div class="border-b border-gray-800/60 bg-[#111a30] p-4 flex items-center justify-between">
            <div class="flex items-center space-x-2 text-sky-400">
                <span class="text-[10px] font-black tracking-widest uppercase">// INVENTORY: LOGGED BETSLIPS</span>
            </div>
            <span class="text-[9px] font-mono font-bold text-slate-500 uppercase select-none">[ REGISTRY_STREAM ]</span>
        </div>

        <div v-if="!betslips || betslips.length === 0" class="p-8 text-center text-xs uppercase font-mono text-slate-500 tracking-wider">
            No tracked betslip signatures present in this epoch.
        </div> -->

        <div class="w-full">
            <div class="block divide-y divide-gray-800/40 md:hidden">
                <div
                    v-for="slip in betslips"
                    :key="slip.id"
                    class="space-y-3 bg-[#111622]/20 p-4 transition-colors hover:bg-[#111622]/40"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="rounded border border-[#232d42] bg-[#0a101f] px-2 py-0.5 font-mono text-xs font-black tracking-wider text-white"
                        >
                            {{ slip.code }}
                        </span>
                        <span
                            class="rounded border bg-[#0a101f] px-2 py-0.5 font-mono text-xs font-black tracking-wider"
                            :class="
                                slip.purchases < 1
                                    ? `border-red-500 text-red-500`
                                    : `border-green-500 text-green-500`
                            "
                        >
                            {{ `${slip.purchases} sold` }} </span
                        ><span
                            v-if="
                                slip.status === 'pending' ||
                                slip.status === 'underway'
                            "
                            :class="[
                                'rounded border px-2 py-0.5 font-mono text-[9px] font-black uppercase',
                                slip.status === 'pending'
                                    ? 'border-amber-500/20 bg-amber-500/10 text-amber-400'
                                    : 'border-emerald-500/20 bg-purple-500/10 text-purple-400',
                            ]"
                        >
                            {{ slip.status }}
                        </span>
                        
                        <span
                            v-else
                            :class="[
                                'rounded border px-2 py-0.5 font-mono text-[9px] font-black uppercase',
                                slip.is_winner
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                    : 'border-rose-500/20 bg-rose-500/10 text-rose-400',
                            ]"
                        >
                            {{ slip.is_winner ? 'WON' : 'LOST' }}
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-x-4 gap-y-2 font-mono text-[10px] font-semibold uppercase"
                    >
                        <div>
                            <span
                                class="block text-[9px] font-bold tracking-wider text-slate-500"
                                >Markets</span
                            >
                            <span class="font-bold text-white"
                                >{{ slip.legs }}
                                {{ slip.legs === 1 ? 'Leg' : 'Legs' }}</span
                            >
                        </div>
                        <div>
                            <span
                                class="block text-[9px] font-bold tracking-wider text-slate-500"
                                >TOTAL ODDS</span
                            >
                            <span class="font-mono font-black text-amber-400">{{
                                Number(slip.total_odds).toFixed(2)
                            }}</span>
                        </div>
                        <div>
                            <span
                                class="block text-[9px] font-bold tracking-wider text-slate-500"
                                >PRICE POINT</span
                            >
                            <span class="font-mono font-bold text-emerald-400"
                                >KES {{ slip.price.toLocaleString() }}</span
                            >
                        </div>
                        <div>
                            <span
                                class="block text-[9px] font-bold tracking-wider text-slate-500"
                                >MARKETS LEFT</span
                            >
                            <span
                                :class="
                                    slip.is_expiring_soon
                                        ? 'animate-pulse font-bold text-rose-400'
                                        : 'font-bold text-sky-400'
                                "
                            >
                                {{ slip.remaining }} Keys
                            </span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <Link
                            :href="`/betslip/view/g/${slip.code}`"
                            class="flex w-full items-center justify-center rounded border border-[#2b3a54] bg-[#1c273a] py-2 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-all duration-200 hover:border-sky-500 hover:bg-sky-500 hover:text-[#070b14]"
                        >
                            Inspect
                        </Link>
                    </div>

                    <div>
                        <Button
                            @click="shareSlip(slip.code, slip.total_odds)"
                            class="flex w-full items-center justify-center rounded border border-[#2b3a54] bg-[#1c273a] py-2 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-all duration-200 hover:border-sky-500 hover:bg-sky-500 hover:text-[#070b14]"
                        >
                            Share
                        </Button>
                    </div>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-800/60 bg-[#0f1422] font-mono text-[10px] font-black tracking-widest text-slate-400 uppercase"
                        >
                            <th class="px-4 py-3">CODE</th>
                            <th class="px-4 py-3">MARKETS</th>
                            <th class="px-4 py-3">ODDS</th>
                            <th class="px-4 py-3">PRICE</th>
                            <th class="px-4 py-3">REMAINING / SOLD</th>
                            <th class="px-4 py-3">STATUS</th>
                            <th class="px-4 py-3 text-right">ACTION</th>
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
                                    {{ slip.remaining }} markets left
                                </span>
                                <span class="ml-1 text-[10px] text-slate-500"
                                    >({{ slip.purchases || 0 }} sold)</span
                                >
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
                                            ? 'border-amber-500/20 bg-amber-500/10 text-amber-400'
                                            : 'border-emerald-500/20 bg-purple-500/10 text-purple-400',
                                    ]"
                                >
                                    {{ slip.status }}
                                </span>
                                <span
                                    v-else
                                    :class="[
                                        'rounded border px-2 py-0.5 font-mono text-[9px] font-black uppercase',
                                        slip.is_winner
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                            : 'border-rose-500/20 bg-rose-500/10 text-rose-400',
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

                                <Button
                                    @click="
                                        shareSlip(slip.code, slip.total_odds)
                                    "
                                    class="ml-2 inline-block h-7 rounded border border-sky-500/20 bg-sky-500/5 px-3 py-1.5 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-all duration-200 hover:border-sky-500 hover:bg-sky-500 hover:text-white"
                                >
                                    Share
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Button from './ui/button/Button.vue';

// Establish strictly checked component expectations
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
        // Fallback: copy link to clipboard
        navigator.clipboard
            .writeText(shareUrl)
            .then(() => {
                alert('Link copied to clipboard! Share it anywhere.');
            })
            .catch(() => {
                // If clipboard fails, open a simple share popup? We can open a new window with a generic share link.
                // For simplicity, we'll just copy using our fallback.
                fallbackCopyTextSelection(shareUrl);
            });
    }
};
</script>
