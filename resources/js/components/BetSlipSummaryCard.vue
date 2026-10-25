<template>
    <div
        class="group/card relative mb-1 w-full max-w-xl overflow-hidden rounded-2xl border border-marketplace-border bg-marketplace-card font-sans shadow-[0_4px_24px_-8px_rgba(0,0,0,0.6)] transition-all duration-300 hover:-translate-y-0.5 hover:border-marketplace-gold/30 hover:shadow-[0_12px_40px_-12px_rgba(251,191,36,0.15)]">
        <!-- Ambient glow -->
        <div
            class="pointer-events-none absolute -top-24 -right-24 h-48 w-48 rounded-full bg-marketplace-gold/5 blur-3xl transition-opacity duration-500 group-hover/card:opacity-100 opacity-60">
        </div>

        <!-- 1. HEADER: Seller Branding & Mini-Heatmap -->
        <div
            class="relative flex items-center justify-between border-b border-marketplace-border/60 bg-gradient-to-b from-marketplace-card/90 to-marketplace-card/40 p-4">
            <!-- Clickable seller block -->
            <button type="button"
                class="group -m-2 flex cursor-pointer items-center space-x-3 rounded-xl p-2 text-left transition-all duration-200 hover:bg-marketplace-gold/5"
                @click="viewSellerProfile(slip.seller.code)" :title="`View ${slip.seller.name}'s profile`">
                <!-- Seller Avatar -->
                <div class="relative flex-shrink-0">
                    <div
                        class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-marketplace-border bg-marketplace-muted/10 ring-1 ring-transparent transition-all duration-200 group-hover:ring-marketplace-gold/40">
                        <img :src="slip.seller.avatar ||
                            `https://api.dicebear.com/10.x/lorelei-neutral/svg?seed=${slip.seller.code}`
                            " :alt="slip.seller.name" class="h-full w-full rounded-full object-cover" />
                    </div>
                    <!-- Online/live dot -->
                    <span
                        class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full border-2 border-marketplace-card bg-marketplace-green"></span>
                </div>
                <!-- Name + stats + view-profile hint -->
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <h4
                            class="truncate text-xs font-bold tracking-wider text-white uppercase transition-colors group-hover:text-marketplace-gold">
                            {{ slip.seller.name }}
                        </h4>
                        <ReputationBadge v-if="slip.seller?.prediction_points > 0"
                            :points="slip.seller.prediction_points" :rank="slip.seller.global_rank ?? null"
                            :compact="true" class="ml-1.5" />
                        <!-- Chevron — brightens on hover -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3"
                            stroke="currentColor"
                            class="h-3 w-3 flex-shrink-0 text-marketplace-muted transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-marketplace-gold">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>

                    <div
                        class="mt-1 flex items-center space-x-1 text-[10px] font-semibold tracking-wide text-marketplace-muted uppercase">
                        <span class="inline-flex items-center gap-1">
                            ROI:
                            <span class="font-bold text-marketplace-green">
                                +{{ slip.seller.roi }}%
                            </span>
                        </span>
                        <span class="text-marketplace-border">•</span>
                        <span class="inline-flex items-center gap-1">
                            Win Rate:
                            <span class="font-bold text-white">
                                {{ slip.seller.win_rate }}%
                            </span>
                        </span>
                    </div>

                    <!-- Explicit "View profile" hint -->
                    <span
                        class="mt-1.5 inline-flex items-center gap-1 text-[9px] font-black tracking-widest text-marketplace-muted uppercase transition-colors duration-200 group-hover:text-marketplace-gold">
                        View profile
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3"
                            stroke="currentColor"
                            class="h-2.5 w-2.5 transition-transform duration-200 group-hover:translate-x-0.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                </div>
            </button>

            <!-- Mini-Heatmap -->
            <div class="flex flex-shrink-0 flex-col items-end">
                <div class="flex space-x-1">
                    <span v-for="(status, index) in flipRecentForm(slip.seller.recent_form)" :key="index" :class="[
                        'h-3 w-3 rounded-[3px] border transition-transform duration-200 hover:scale-110',
                        status === 'W'
                            ? 'border-marketplace-green bg-marketplace-green/20 shadow-[0_0_6px_-1px_rgba(34,197,94,0.5)]'
                            : status == 'L'
                                ? 'border-red-500/60 bg-red-500/20 shadow-[0_0_6px_-1px_rgba(239,68,68,0.4)]'
                                : 'border-marketplace-border bg-marketplace-border/30',
                    ]" :title="status === 'W' ? 'Won' : status == 'L' ? 'Lost' : 'No Bet'"></span>
                </div>
                <span class="mt-1.5 text-[9px] font-bold tracking-wider text-marketplace-muted uppercase">
                    Recent Form
                </span>
            </div>
        </div>

        <!-- 2. Bet Summary: Multibet / Singlebet + Total Odds -->
        <div class="relative flex flex-col border-b border-marketplace-border/40 bg-marketplace-card/30 select-none">
            <div class="flex items-center justify-between px-4 pt-3 pb-1.5 text-xs font-bold tracking-wider uppercase">
                <div class="flex items-center space-x-2 text-slate-400">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-md border border-marketplace-border/60 bg-marketplace-card/60 px-2 py-1 text-[10px] font-black tracking-widest">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor" class="h-3.5 w-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18" />
                        </svg>
                        {{
                            slip.total_markets > 1
                                ? `Multibet (${slip.total_markets})`
                                : 'Singlebet'
                        }}
                    </span>
                </div>
                <div
                    class="inline-flex items-center gap-2 rounded-lg border border-marketplace-amber/20 bg-marketplace-amber/5 px-2.5 py-1 text-white">
                    <span class="text-[10px] font-black tracking-widest text-marketplace-amber/70 uppercase">Total
                        Odds</span>
                    <span class="text-base font-black tracking-tight text-marketplace-amber">
                        {{ calculateTotalOdds() }}
                    </span>
                </div>
            </div>

            <!-- Tracking Code & Share -->
            <div v-if="slip.code"
                class="flex items-center justify-between border-t border-marketplace-border/20 px-4 pt-2 pb-3">
                <div
                    class="flex items-center gap-1.5 text-[10px] font-bold tracking-widest text-marketplace-muted uppercase">
                    <span>Code:</span>
                    <span
                        class="rounded-md border border-marketplace-border bg-marketplace-card/80 px-2 py-0.5 font-mono text-xs font-black tracking-normal text-white shadow-inner">
                        {{ slip.code }}
                    </span>
                </div>
                <div class="flex items-center gap-1.5">
                    <!-- WATCH BUTTON -->
                    <WatchButton v-if="slip.can_watch !== false" :code="slip.code"
                        :watching="slip.is_watching === true" />
                    <!-- COPY BUTTON -->
                    <button type="button" @click="copyToClipboard(slip.code)" title="Copy tracking code"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-marketplace-border bg-marketplace-card/60 px-2.5 py-1.5 text-[10px] font-black tracking-wider text-slate-300 uppercase transition-all duration-150 hover:border-sky-500/50 hover:bg-sky-500/10 hover:text-sky-400 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor" class="h-3 w-3">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                        </svg>
                        Copy
                    </button>

                    <!-- SHARE BUTTON -->
                    <button type="button" @click="shareSlip" title="Share this betslip"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-marketplace-border bg-marketplace-card/60 px-2.5 py-1.5 text-[10px] font-black tracking-wider text-slate-300 uppercase transition-all duration-150 hover:border-marketplace-gold/50 hover:bg-marketplace-gold/10 hover:text-marketplace-gold active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor" class="h-3 w-3">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                        </svg>
                        Share
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. LEGS (Bet Slip Entries) -->
        <div class="divide-y divide-marketplace-border/40">
            <div v-for="(leg, idx) in slip.legs" :key="leg.id"
                class="group/leg relative p-4 transition-colors duration-150 hover:bg-marketplace-card/60">
                <!-- Accent bar on hover -->
                <div
                    class="absolute top-0 bottom-0 left-0 w-[2px] bg-marketplace-gold/0 transition-colors duration-200 group-hover/leg:bg-marketplace-gold/60">
                </div>

                <!-- Leg Meta -->
                <div
                    class="mb-2 flex items-center justify-between text-[10px] font-bold tracking-wider text-marketplace-muted uppercase">
                    <span class="inline-flex items-center gap-1.5">
                        <span
                            class="flex h-4 w-4 items-center justify-center rounded-full border border-marketplace-border/60 bg-marketplace-card/60 text-[8px] font-black text-marketplace-muted">
                            {{ idx + 1 }}
                        </span>
                        {{ leg.league }}
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="h-3 w-3 text-marketplace-muted/60">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ formatTime(leg.kickoff_at) }}
                    </span>
                </div>

                <!-- Fixture Grid -->
                <div class="grid grid-cols-12 items-center gap-2">
                    <!-- Teams -->
                    <div class="col-span-7 flex flex-col space-y-0.5">
                        <span
                            class="truncate text-xs font-bold tracking-wide text-white uppercase">{{ leg.home_team }}</span>
                        <span
                            class="truncate text-xs font-bold tracking-wide text-marketplace-muted uppercase">{{ leg.away_team
                            }}</span>
                    </div>

                    <!-- Market -->
                    <div class="col-span-3 text-right">
                        <span
                            class="block text-[10px] font-bold tracking-wider text-marketplace-muted uppercase">Market</span>
                        <span class="text-xs font-semibold tracking-wide text-slate-200">{{ leg.market_name }}</span>
                    </div>

                    <!-- Selection / Lock Icon -->
                    <div class="col-span-2 text-right">
                        <div v-if="isLocked"
                            class="group/lock flex w-full cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-marketplace-border bg-marketplace-card/50 py-1.5 text-slate-500 transition-colors select-none hover:border-marketplace-amber/40 hover:bg-marketplace-amber/5 hover:text-marketplace-amber"
                            @click="$emit('unlock-clicked', slip)">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="h-3.5 w-3.5 transition-transform duration-150 group-hover/lock:scale-110">
                                <path fill-rule="evenodd"
                                    d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div v-else
                            class="flex w-full flex-col rounded-lg border border-marketplace-green/50 bg-marketplace-green/10 py-1.5 text-center text-xs font-bold text-marketplace-green shadow-[0_0_12px_-4px_rgba(34,197,94,0.4)]">
                            <span
                                class="text-[9px] leading-none tracking-tighter text-marketplace-green/70 uppercase">{{
                                    leg.selection }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. SELLER'S CAPTION / PITCH -->
        <div v-if="slip.caption"
            class="group/caption relative mx-3 mt-3 mb-3 overflow-hidden rounded-xl border border-marketplace-border/40 bg-gradient-to-br from-marketplace-card/90 to-marketplace-card p-4 shadow-inner">
            <div
                class="absolute -top-3 -left-1 text-8xl font-black text-marketplace-gold/5 select-none transition-colors duration-300 group-hover/caption:text-marketplace-gold/10">
                "
            </div>
            <div class="relative flex items-start space-x-3">
                <div class="flex-1">
                    <p class="text-sm leading-relaxed font-medium text-white/90">
                        {{ slip.caption }}
                    </p>
                    <span
                        class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold tracking-widest text-marketplace-muted uppercase">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor" class="h-3 w-3 text-marketplace-gold/60">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                        Seller's insight
                    </span>
                </div>
            </div>
            <div class="absolute -right-4 -bottom-4 h-16 w-16 rounded-full bg-marketplace-gold/5 blur-2xl"></div>
        </div>

        <!-- 5. CTA / PRICE -->
        <div v-if="isLocked"
            class="relative flex items-center justify-between border-t border-marketplace-border/80 bg-gradient-to-r from-marketplace-card/95 to-marketplace-card/90 p-4">
            <div class="flex flex-col">
                <span class="text-[10px] font-bold tracking-widest text-marketplace-muted uppercase">Price</span>
                <span class="text-lg font-black tracking-wide text-white">KSH {{ slip.price.toFixed(2) }}</span>
                <span v-if="slip.break_even_stake !== null && slip.break_even_stake !== undefined"
                    class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold tracking-wide text-marketplace-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                        stroke="currentColor" class="h-3 w-3 text-marketplace-muted/70">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Break-even: KSH
                    {{ slip.break_even_stake.toFixed(2) }}
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
                </span>
            </div>
            <button @click="unlockClicked(slip)"
                class="group/btn relative cursor-pointer overflow-hidden rounded-xl bg-marketplace-gold px-6 py-3 text-xs font-black tracking-widest text-marketplace-bg uppercase shadow-[0_4px_16px_-4px_rgba(251,191,36,0.5)] transition-all duration-200 hover:bg-marketplace-gold/90 hover:shadow-[0_6px_24px_-4px_rgba(251,191,36,0.6)] active:scale-[0.98]">
                <span class="relative z-10">Unlock</span>
                <span
                    class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>
            </button>
        </div>

        <div v-else
            class="relative flex items-center justify-center gap-2 border-t border-marketplace-green/30 bg-marketplace-green/10 p-3 text-center text-xs font-bold tracking-widest text-marketplace-green uppercase">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3"
                stroke="currentColor" class="h-3.5 w-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            You have unlocked this slip
        </div>
    </div>
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