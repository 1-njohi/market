<template>
    <div class="w-full overflow-hidden font-sans text-slate-200">
        <!-- Empty state -->
        <div v-if="!betslips || betslips.length === 0" class="p-10 text-center text-sm text-slate-500">
            No betslips to display yet.
        </div>

        <template v-else>
            <!-- ─────── MOBILE ─────── -->
            <div class="space-y-3 p-3 md:hidden">
                <article
                    v-for="slip in betslips"
                    :key="slip.id"
                    class="space-y-4 rounded-2xl border border-white/[0.07] bg-white/[0.03] p-4 transition-colors hover:border-sky-400/30"
                >
                    <!-- Code + status + sold + watching -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-lg bg-black/30 px-2.5 py-1 font-mono text-sm font-bold tracking-wide text-white">{{ slip.code }}</span>

                        <span
                            v-if="slip.status === 'pending' || slip.status === 'underway'"
                            :class="[
                                'rounded-full px-2.5 py-1 text-xs font-bold capitalize',
                                slip.status === 'pending' ? 'bg-amber-400/10 text-amber-300' : 'bg-purple-400/10 text-purple-300',
                            ]"
                        >
                            {{ slip.status }}
                        </span>
                        <span
                            v-else
                            :class="[
                                'rounded-full px-2.5 py-1 text-xs font-bold',
                                slip.is_winner ? 'bg-emerald-400/10 text-emerald-300' : 'bg-rose-400/10 text-rose-300',
                            ]"
                        >
                            {{ slip.is_winner ? 'Won' : 'Lost' }}
                        </span>

                        <span
                            :class="[
                                'rounded-full px-2.5 py-1 text-xs font-semibold',
                                slip.purchases < 1 ? 'bg-white/[0.06] text-slate-400' : 'bg-emerald-400/10 text-emerald-300',
                            ]"
                        >
                            {{ slip.purchases }} sold
                        </span>

                        <span v-if="slip.watch_count > 0" class="inline-flex items-center gap-1 rounded-full bg-sky-400/10 px-2.5 py-1 text-xs font-semibold text-sky-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3 w-3" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            {{ slip.watch_count }} watching
                        </span>
                    </div>

                    <!-- Stats -->
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-2xl bg-black/25 p-4">
                        <div>
                            <dt class="text-xs text-slate-500">Markets</dt>
                            <dd class="mt-0.5 text-base font-bold text-white tabular-nums">{{ slip.legs }} {{ slip.legs === 1 ? 'leg' : 'legs' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Total odds</dt>
                            <dd class="mt-0.5 text-base font-black text-amber-300 tabular-nums">{{ Number(slip.total_odds).toFixed(2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Price</dt>
                            <dd class="mt-0.5 text-base font-bold text-emerald-300 tabular-nums">KES {{ slip.price.toLocaleString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Markets left</dt>
                            <dd class="mt-0.5 text-base font-bold tabular-nums" :class="slip.is_expiring_soon ? 'text-rose-300' : 'text-sky-300'">{{ slip.remaining }}</dd>
                        </div>
                    </dl>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <Link
                            :href="`/betslip/view/g/${slip.code}`"
                            class="flex min-h-11 flex-1 items-center justify-center rounded-xl bg-sky-400 text-sm font-bold text-[#070b14] transition-all hover:bg-sky-300 focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:outline-none active:scale-[0.98]"
                        >
                            Inspect
                        </Link>
                        <button
                            type="button"
                            @click="shareSlip(slip.code, slip.total_odds)"
                            class="flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-sm font-bold text-slate-300 transition-all hover:border-white/25 hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none active:scale-[0.98]"
                        >
                            Share
                        </button>
                    </div>
                </article>
            </div>

            <!-- ─────── DESKTOP ─────── -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-white/[0.07] bg-black/20 text-xs font-semibold text-slate-500">
                            <th class="px-5 py-3.5">Code</th>
                            <th class="px-5 py-3.5">Markets</th>
                            <th class="px-5 py-3.5">Odds</th>
                            <th class="px-5 py-3.5">Price</th>
                            <th class="px-5 py-3.5">Remaining / sold / watching</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.05]">
                        <tr v-for="slip in betslips" :key="slip.id" class="group text-sm transition-colors hover:bg-white/[0.03]">
                            <td class="px-5 py-4 font-mono font-bold tracking-wide text-white">{{ slip.code }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-300 tabular-nums">{{ slip.legs }}</td>
                            <td class="px-5 py-4 font-black text-amber-300 tabular-nums">{{ Number(slip.total_odds).toFixed(2) }}</td>
                            <td class="px-5 py-4 font-semibold text-emerald-300 tabular-nums">KES {{ slip.price.toLocaleString() }}</td>

                            <td class="px-5 py-4 text-sm">
                                <span :class="slip.is_expiring_soon ? 'font-bold text-rose-300' : 'font-semibold text-sky-300'">{{ slip.remaining }} left</span>
                                <span class="ml-1 text-slate-500">· {{ slip.purchases || 0 }} sold</span>
                                <span v-if="slip.watch_count > 0" class="ml-1 text-sky-300">· {{ slip.watch_count }} watching</span>
                            </td>

                            <td class="px-5 py-4">
                                <span
                                    v-if="slip.status === 'pending' || slip.status === 'underway'"
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-bold capitalize',
                                        slip.status === 'pending' ? 'bg-amber-400/10 text-amber-300' : 'bg-purple-400/10 text-purple-300',
                                    ]"
                                >
                                    {{ slip.status }}
                                </span>
                                <span
                                    v-else
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-bold',
                                        slip.is_winner ? 'bg-emerald-400/10 text-emerald-300' : 'bg-rose-400/10 text-rose-300',
                                    ]"
                                >
                                    {{ slip.is_winner ? 'Won' : 'Lost' }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <Link
                                    :href="`/betslip/view/g/${slip.code}`"
                                    class="inline-block rounded-lg bg-sky-400 px-4 py-2 text-xs font-bold text-[#070b14] transition-all duration-200 hover:bg-sky-300 focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:outline-none"
                                >
                                    Inspect
                                </Link>
                                <button
                                    type="button"
                                    @click="shareSlip(slip.code, slip.total_odds)"
                                    class="ml-2 inline-block cursor-pointer rounded-lg border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-bold text-slate-300 transition-all duration-200 hover:border-white/25 hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
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