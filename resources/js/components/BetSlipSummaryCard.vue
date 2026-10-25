<template>
    <article
        class="group/card relative mb-1 w-full max-w-xl overflow-hidden rounded-3xl border border-marketplace-border bg-marketplace-card font-sans shadow-[0_10px_40px_-16px_rgba(0,0,0,0.8)] transition-all duration-300 hover:-translate-y-1 hover:border-marketplace-gold/40 hover:shadow-[0_24px_60px_-24px_rgba(251,191,36,0.3)]"
    >
        <!-- Ambient light -->
        <div class="pointer-events-none absolute -top-28 -right-24 h-56 w-56 rounded-full bg-marketplace-gold/10 opacity-60 blur-3xl transition-opacity duration-500 group-hover/card:opacity-100" aria-hidden="true"></div>

        <!-- 1. Seller -->
        <header class="relative flex items-center justify-between gap-4 p-5">
            <button
                type="button"
                class="group -m-2 flex min-w-0 cursor-pointer items-center gap-3.5 rounded-2xl p-2 text-left transition-colors duration-200 hover:bg-white/[0.04] focus-visible:ring-2 focus-visible:ring-marketplace-gold focus-visible:outline-none"
                @click="viewSellerProfile(slip.seller.code)"
                :title="`View ${slip.seller.name}'s profile`"
            >
                <div class="relative shrink-0">
                    <div class="h-12 w-12 overflow-hidden rounded-full bg-marketplace-muted/10 ring-2 ring-white/10 transition-all duration-200 group-hover:ring-marketplace-gold/60">
                        <img
                            :src="slip.seller.avatar || `https://api.dicebear.com/10.x/lorelei-neutral/svg?seed=${slip.seller.code}`"
                            :alt="slip.seller.name"
                            class="h-full w-full rounded-full object-cover"
                        />
                    </div>
                    <span class="absolute -right-0.5 -bottom-0.5 h-3.5 w-3.5 rounded-full border-2 border-marketplace-card bg-marketplace-green"></span>
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="truncate text-base font-bold text-white transition-colors group-hover:text-marketplace-gold">
                            {{ slip.seller.name }}
                        </h4>
                        <ReputationBadge
                            v-if="slip.seller?.prediction_points > 0"
                            :points="slip.seller.prediction_points"
                            :rank="slip.seller.global_rank ?? null"
                            :compact="true"
                        />
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3 w-3 shrink-0 text-marketplace-muted transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-marketplace-gold" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                    <p class="mt-0.5 text-sm text-marketplace-muted">
                        ROI <span class="font-semibold text-marketplace-green">+{{ slip.seller.roi }}%</span>
                        <span class="mx-1 text-marketplace-border">/</span>
                        Win rate <span class="font-semibold text-white">{{ slip.seller.win_rate }}%</span>
                    </p>
                </div>
            </button>

            <!-- Recent form -->
            <div class="flex shrink-0 flex-col items-end">
                <div class="flex gap-1">
                    <span
                        v-for="(status, index) in flipRecentForm(slip.seller.recent_form)"
                        :key="index"
                        :class="[
                            'h-3.5 w-3.5 rounded-[4px] transition-transform duration-200 hover:scale-125',
                            status === 'W'
                                ? 'bg-marketplace-green/80 shadow-[0_0_8px_rgba(34,197,94,0.5)]'
                                : status == 'L'
                                  ? 'bg-red-400/80 shadow-[0_0_8px_rgba(248,113,113,0.4)]'
                                  : 'bg-white/10',
                        ]"
                        :title="status === 'W' ? 'Won' : status == 'L' ? 'Lost' : 'No Bet'"
                    ></span>
                </div>
                <span class="mt-1.5 text-xs text-marketplace-muted">Recent form</span>
            </div>
        </header>

        <!-- 2. Slip summary: type + total odds -->
        <div class="relative border-y border-white/[0.06] bg-black/20">
            <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                <p class="text-sm font-semibold text-slate-300">
                    {{ slip.total_markets > 1 ? `Multibet` : 'Singlebet' }}
                    <span v-if="slip.total_markets > 1" class="font-normal text-marketplace-muted">({{ slip.total_markets }} legs)</span>
                </p>
                <p class="text-sm text-marketplace-muted">
                    Total odds
                    <span class="ml-1.5 align-middle text-2xl font-black tracking-tight text-marketplace-amber tabular-nums">{{ calculateTotalOdds() }}</span>
                </p>
            </div>

            <!-- Code + actions -->
            <div v-if="slip.code" class="flex flex-wrap items-center justify-between gap-2 border-t border-white/[0.04] px-5 py-3">
                <p class="flex items-center gap-2 text-xs text-marketplace-muted">
                    Code
                    <span class="rounded-md border border-marketplace-border bg-black/30 px-2 py-1 font-mono text-xs font-bold text-white">{{ slip.code }}</span>
                </p>
                <div class="flex items-center gap-1.5">
                    <WatchButton v-if="slip.can_watch !== false" :code="slip.code" :watching="slip.is_watching === true" />
                    <button
                        type="button"
                        @click="copyToClipboard(slip.code)"
                        title="Copy tracking code"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-marketplace-border bg-white/[0.03] px-3 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-sky-400/50 hover:bg-sky-400/10 hover:text-sky-300 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none active:scale-95"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                        </svg>
                        Copy
                    </button>
                    <button
                        type="button"
                        @click="shareSlip"
                        title="Share this betslip"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-marketplace-border bg-white/[0.03] px-3 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-marketplace-gold/50 hover:bg-marketplace-gold/10 hover:text-marketplace-gold focus-visible:ring-2 focus-visible:ring-marketplace-gold focus-visible:outline-none active:scale-95"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                        </svg>
                        Share
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Legs -->
        <ul class="divide-y divide-white/[0.05]">
            <li v-for="leg in slip.legs" :key="leg.id" class="relative flex items-center gap-4 px-5 py-4 transition-colors duration-150 hover:bg-white/[0.03]">
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-1.5 text-xs text-marketplace-muted">
                        <span class="font-semibold text-slate-400">{{ leg.league }}</span>
                        <span aria-hidden="true">·</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3 w-3 opacity-60" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ formatTime(leg.kickoff_at) }}
                    </p>
                    <p class="mt-1.5 truncate text-sm font-semibold text-white">{{ leg.home_team }}</p>
                    <p class="truncate text-sm text-marketplace-muted">{{ leg.away_team }}</p>
                </div>

                <div class="shrink-0 text-right">
                    <p class="text-xs text-marketplace-muted">Market</p>
                    <p class="mt-0.5 text-sm font-semibold text-slate-200">{{ leg.market_name }}</p>
                </div>

                <!-- Selection: locked or revealed -->
                <div class="w-20 shrink-0">
                    <button
                        v-if="isLocked"
                        type="button"
                        class="group/lock flex h-11 w-full cursor-pointer items-center justify-center rounded-xl border border-dashed border-marketplace-border bg-white/[0.03] text-slate-500 transition-colors select-none hover:border-marketplace-amber/50 hover:bg-marketplace-amber/10 hover:text-marketplace-amber focus-visible:ring-2 focus-visible:ring-marketplace-amber focus-visible:outline-none"
                        @click="$emit('unlock-clicked', slip)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 transition-transform duration-150 group-hover/lock:scale-110" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">Locked selection. Unlock to see it.</span>
                    </button>
                    <div
                        v-else
                        class="flex h-11 w-full items-center justify-center rounded-xl border border-marketplace-green/50 bg-marketplace-green/10 px-1 text-center text-sm leading-tight font-bold text-marketplace-green shadow-[0_0_14px_-4px_rgba(34,197,94,0.5)]"
                    >
                        {{ leg.selection }}
                    </div>
                </div>
            </li>
        </ul>

        <!-- 4. Seller's caption -->
        <blockquote v-if="slip.caption" class="relative mx-4 my-4 overflow-hidden rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4">
            <div class="pointer-events-none absolute -top-4 left-1 text-8xl font-black text-marketplace-gold/10 select-none" aria-hidden="true">"</div>
            <p class="relative pl-5 text-sm leading-relaxed text-white/90">{{ slip.caption }}</p>
            <footer class="relative mt-2.5 flex items-center gap-1.5 pl-5 text-xs font-medium text-marketplace-gold/80">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                </svg>
                Seller's insight
            </footer>
        </blockquote>

        <!-- 5. Price + unlock -->
        <footer v-if="isLocked" class="relative flex items-center justify-between gap-4 border-t border-white/[0.06] bg-black/20 p-5">
            <div class="min-w-0">
                <p class="text-xs text-marketplace-muted">Price</p>
                <p class="text-2xl font-black tracking-tight text-white tabular-nums">KSH {{ slip.price.toFixed(2) }}</p>
                <p v-if="slip.break_even_stake !== null && slip.break_even_stake !== undefined" class="mt-1 flex items-center gap-1.5 text-xs text-marketplace-muted">
                    Break-even: KSH {{ slip.break_even_stake.toFixed(2) }}
                    <InfoPopover title="What is break-even?">
                        <p>
                            The price you pay for a slip is a cost the moment
                            you unlock it. To get that money back, you need to
                            win enough on the actual bet elsewhere.
                        </p>
                        <p>
                            <span class="font-bold text-slate-200">
                                Break-even stake
                            </span>
                            is the amount you'd need to place at a bookmaker
                            to earn back exactly what you paid — no profit,
                            no loss.
                        </p>
                        <p>
                            <span class="font-bold text-slate-200">
                                How it's calculated:
                            </span>
                        </p>
                        <p class="font-mono text-[11px] text-slate-300">
                            stake × (total odds − 1) = price
                        </p>
                        <p class="font-mono text-[11px] text-slate-300">
                            ⇒ stake = price ÷ (total odds − 1)
                        </p>
                        <p>
                            For this slip:
                            <span class="font-mono text-slate-300">
                                KSH {{ slip.price.toFixed(2) }} ÷
                                ({{ calculateTotalOdds() }} − 1) =
                                KSH {{ slip.break_even_stake.toFixed(2) }}
                            </span>
                        </p>
                        <p>
                            Stake anything <span class="font-bold text-slate-200">above</span>
                            this and you're in profit beyond covering the
                            slip's price.
                        </p>
                        <p class="text-slate-400">
                            Higher total odds mean a lower break-even stake —
                            a slip at 10.00 only needs a tenth of its price
                            staked to break even.
                        </p>
                    </InfoPopover>
                </p>
            </div>
            <button
                type="button"
                @click="unlockClicked(slip)"
                class="group/btn relative inline-flex shrink-0 cursor-pointer items-center gap-2 overflow-hidden rounded-xl bg-marketplace-gold px-7 py-3.5 text-sm font-black text-marketplace-bg shadow-[0_10px_30px_-10px_rgba(251,191,36,0.8)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-marketplace-gold/90 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]"
            >
                <span class="relative z-10">Unlock</span>
                <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full motion-reduce:hidden"></span>
            </button>
        </footer>

        <footer v-else class="relative flex items-center justify-center gap-2 border-t border-marketplace-green/30 bg-marketplace-green/10 p-4 text-center text-sm font-bold text-marketplace-green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            You have unlocked this slip
        </footer>
    </article>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import InfoPopover from '@/components/InfoPopover.vue';
import WatchButton from '@/components/WatchButton.vue';
import ReputationBadge from '@/components/ReputationBadge.vue';

const props = defineProps({
    slip: {
        type: Object,
        required: true,
    },
    isLocked: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['unlock-clicked']);

const flipRecentForm = (recent_form) => {
    return [...recent_form].reverse();
};

const unlockClicked = (slip) => {
    router.get(`/betslip/view/g/${slip.code}`);
};

const viewSellerProfile = (seller_code) => {
    router.get(`/profile/${seller_code}`);
};

const calculateTotalOdds = () => {
    if (!props.slip?.legs) return '0.00';
    return props.slip.legs
        .reduce((total, leg) => total * leg.odds, 1)
        .toFixed(2);
};

const formatTime = (timeStr) => {
    const timestamp =
        typeof timeStr === 'number' || !isNaN(timeStr)
            ? parseInt(timeStr) * 1000
            : timeStr;
    const date = new Date(timestamp);
    return (
        date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) +
        ' UTC'
    );
};

const shareSlip = () => {
    const shareUrl =
        window.location.origin + '/betslip/view/g/' + props.slip.code;
    const shareText = `Check out this betslip from ${props.slip.seller.name} with total odds of ${calculateTotalOdds()}! `;
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

const copyToClipboard = (text) => {
    if (typeof window === 'undefined' || !navigator) return;
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