<template>
    <div
        class="mb-1 w-full max-w-xl overflow-hidden rounded-xl border border-marketplace-border bg-marketplace-card font-sans shadow-lg transition-shadow hover:shadow-xl"
    >
        <!-- 1. HEADER: Seller Branding & Mini-Heatmap -->
        <div
            class="flex items-center justify-between border-b border-marketplace-border/60 bg-marketplace-card/80 p-4"
        >
            <div
                class="flex cursor-pointer items-center space-x-3"
                @click="viewSellerProfile(slip.seller.code)"
            >
                <!-- Seller Avatar -->
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-marketplace-gold/30 bg-marketplace-gold/10 text-sm font-bold tracking-wider text-marketplace-gold"
                >
                    <!-- {{ slip.seller.name.substring(0, 2).toUpperCase() }} -->

                <img
                    src="https://api.dicebear.com/10.x/lorelei-neutral/svg?seed=Felix"
                    :alt="slip.seller.name"
                    class="h-full w-full rounded-full object-cover"
                />
                
                </div>
                <div>
                    <h4
                        class="text-xs font-bold tracking-wider text-white uppercase"
                    >
                        {{ slip.seller.name }}
                    </h4>
                    <div
                        class="mt-0.5 flex items-center space-x-1 text-[10px] font-semibold tracking-wide text-marketplace-muted uppercase"
                    >
                        <span
                            >ROI:
                            <span class="font-bold text-marketplace-green"
                                >+{{ slip.seller.roi }}%</span
                            ></span
                        >
                        <span class="text-marketplace-border">•</span>
                        <span
                            >Win Rate:
                            <span class="font-bold text-marketplace-gold"
                                >{{ slip.seller.win_rate }}%</span
                            ></span
                        >
                    </div>
                </div>
            </div>

            <!-- Mini-Heatmap -->
            <div class="flex flex-col items-end">
                <div class="flex space-x-1">
                    <span
                        v-for="(status, index) in flipRecentForm(
                            slip.seller.recent_form,
                        )"
                        :key="index"
                        :class="[
                            'h-3 w-3 rounded-sm border',
                            status === 'W'
                                ? 'border-marketplace-green bg-marketplace-green/20'
                                : status == 'L'
                                  ? 'border-red-500/60 bg-red-500/20'
                                  : 'border-marketplace-border bg-marketplace-border/30',
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
                    class="mt-1 text-[9px] font-bold tracking-wider text-marketplace-muted uppercase"
                >
                    Recent Form
                </span>
            </div>
        </div>

        <!-- 2. Bet Summary: Multibet / Singlebet + Total Odds -->
        <div
            class="flex flex-col border-b border-marketplace-border/40 bg-marketplace-card/30 select-none"
        >
            <div
                class="flex items-center justify-between px-4 pt-3 pb-1.5 text-xs font-bold tracking-wider uppercase"
            >
                <div class="flex items-center space-x-2 text-marketplace-gold">
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
                    <span>{{
                        slip.total_markets > 1
                            ? `Multibet (${slip.total_markets})`
                            : 'Singlebet'
                    }}</span>
                </div>
                <div class="text-white">
                    Total Odds:
                    <span class="text-sm font-extrabold text-marketplace-amber">
                        {{ calculateTotalOdds() }}
                    </span>
                </div>
            </div>

            <!-- Tracking Code & Share -->
            <div
                v-if="slip.code"
                class="flex items-center justify-between border-t border-marketplace-border/20 px-4 pt-1.5 pb-3"
            >
                <div
                    class="flex items-center gap-1.5 text-[10px] font-bold tracking-widest text-marketplace-muted uppercase"
                >
                    <span> Code:</span>
                    <span
                        class="rounded border border-marketplace-border bg-marketplace-card/60 px-2 py-0.5 font-mono text-xs font-black tracking-normal text-white"
                    >
                        {{ slip.code }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="copyToClipboard(slip.code)"
                        class="cursor-pointer text-[10px] font-black tracking-wider text-marketplace-gold uppercase transition-colors hover:text-marketplace-gold/80"
                    >
                        Copy
                    </button>
                    <button
                        @click="shareSlip"
                        class="cursor-pointer text-[10px] font-black tracking-wider text-marketplace-gold uppercase transition-colors hover:text-marketplace-gold/80"
                        title="Share this betslip"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. LEGS (Bet Slip Entries) -->
        <div class="divide-y divide-marketplace-border/40">
            <div
                v-for="(leg, idx) in slip.legs"
                :key="leg.id"
                class="p-4 transition-colors hover:bg-marketplace-card/60"
            >
                <!-- Leg Meta -->
                <div
                    class="mb-2 flex items-center justify-between text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                >
                    <span>Leg {{ idx + 1 }} • {{ leg.league }}</span>
                    <span>{{ formatTime(leg.kickoff_at) }}</span>
                </div>

                <!-- Fixture Grid -->
                <div class="grid grid-cols-12 items-center gap-2">
                    <!-- Teams -->
                    <div class="col-span-7 flex flex-col space-y-0.5">
                        <span
                            class="text-xs font-bold tracking-wide text-white uppercase"
                            >{{ leg.home_team }}</span
                        >
                        <span
                            class="text-xs font-bold tracking-wide text-marketplace-muted uppercase"
                            >{{ leg.away_team }}</span
                        >
                    </div>

                    <!-- Market -->
                    <div class="col-span-3 text-right">
                        <span
                            class="block text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                            >Market</span
                        >
                        <span
                            class="text-xs font-semibold tracking-wide text-marketplace-gold"
                            >{{ leg.market_name }}</span
                        >
                    </div>

                    <!-- Selection / Lock Icon -->
                    <div class="col-span-2 text-right">
                        <div
                            v-if="isLocked"
                            class="flex w-full cursor-pointer flex-col items-center justify-center rounded border border-dashed border-marketplace-gold/40 bg-marketplace-card/50 py-1.5 text-marketplace-gold select-none"
                            @click="$emit('unlock-clicked', slip)"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                class="h-3 w-3 text-marketplace-amber"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div
                            v-else
                            class="flex w-full flex-col rounded border border-marketplace-green bg-marketplace-green/10 py-1.5 text-center text-xs font-bold text-marketplace-green"
                        >
                            <span
                                class="text-[9px] leading-none tracking-tighter text-marketplace-green/70 uppercase"
                                >{{ leg.selection }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. SELLER'S CAPTION / PITCH -->
        <div
            v-if="slip.caption"
            class="relative mx-2 mb-2 mt-1 overflow-hidden rounded-xl bg-gradient-to-br from-marketplace-card/80 to-marketplace-card p-4 shadow-sm"
        >
            <!-- <div
                class="absolute -top-2 -left-2 text-7xl font-black text-marketplace-gold/10 select-none"
            >
                "
            </div>
            <div class="relative flex items-start space-x-3">
                <div class="flex-1">
                    <p
                        class="text-sm leading-relaxed font-medium text-white/90"
                    >
                        {{ slip.caption }}
                    </p>
                    <span
                        class="mt-1 block text-[10px] font-bold tracking-widest text-marketplace-gold/60 uppercase"
                    >
                        — Seller's insight
                    </span>
                </div>
            </div> -->
            <div
                class="absolute -right-4 -bottom-4 h-12 w-12 rounded-full bg-marketplace-gold/5 blur-xl"
            ></div>
        </div>

        <!-- 5. CTA / PRICE -->
        <div
            v-if="isLocked"
            class="flex items-center justify-between border-t border-marketplace-border/80 bg-marketplace-card/90 p-4"
        >
            <div class="flex flex-col">
                <span
                    class="text-[10px] font-bold tracking-widest text-marketplace-muted uppercase"
                    >Price</span
                >
                <span class="text-lg font-black tracking-wide text-white"
                    >KSH {{ slip.price.toFixed(2) }}</span
                >
            </div>
            <button
                @click="unlockClicked(slip)"
                class="cursor-pointer rounded-lg bg-marketplace-gold px-6 py-3 text-xs font-bold tracking-wider text-marketplace-bg uppercase shadow-md transition-all hover:bg-marketplace-gold/90 active:scale-[0.98]"
            >
                Unlock
            </button>
        </div>
        <div
            v-else
            class="border-t border-marketplace-green/30 bg-marketplace-green/10 p-3 text-center text-xs font-bold tracking-widest text-marketplace-green uppercase"
        >
            ✓ You have unlocked this slip
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

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