<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import InfoPopover from '@/components/InfoPopover.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    selections: { type: Array, required: true },
    total_odds: { type: Number, required: true },
    can_sell: { type: Boolean, default: false },
    seller_progress: { type: Object, default: null },
    seller_status: { type: String, default: 'ineligible' },
});

// ────────────── Confidence state ──────────────
const confidences = ref<Record<number, number>>(
    Object.fromEntries(props.selections.map((s: any) => [s.odd_id, 0])),
);

const setConfidence = (oddId: number, value: number) => {
    confidences.value = {
        ...confidences.value,
        [oddId]: Math.max(0, Math.min(5, Math.round(value))),
    };
};

const confidenceLabels = [
    'Not rated',
    'Wild guess',
    'Weak',
    'Fair',
    'Strong',
    'Locked in',
];

const allConfidencesSet = computed(() => {
    return props.selections.every((s: any) => (confidences.value[s.odd_id] ?? 0) > 0);
});

// ────────────── Seller eligibility progress ──────────────
const progressSummary = computed<string>(() => {
    const p = props.seller_progress;
    if (!p) return '';

    const picksDeficit = Math.max(0, p.required_picks - p.current_picks);
    const hasRate = p.current_win_rate !== null;
    const rateMissing = !hasRate || p.current_win_rate < p.required_win_rate;
    const requiredRatePct = (p.required_win_rate * 100).toFixed(0);

    if (props.seller_status === 'delisted') {
        if (!rateMissing) {
            return 'Your numbers have recovered. The daily status check will restore selling on its next run.';
        }
        return `Rebuild your win rate to ${requiredRatePct}% to re-qualify.`;
    }

    if (picksDeficit > 0 && rateMissing) {
        const unit = picksDeficit === 1 ? 'pick' : 'picks';
        return `${picksDeficit} more settled ${unit}, plus a ${requiredRatePct}% win rate.`;
    }

    if (picksDeficit > 0) {
        const unit = picksDeficit === 1 ? 'pick' : 'picks';
        return `${picksDeficit} more settled ${unit} to qualify.`;
    }

    if (rateMissing) {
        const gap = ((p.required_win_rate - (p.current_win_rate ?? 0)) * 100).toFixed(1);
        return `${gap}% below the ${requiredRatePct}% win-rate threshold.`;
    }

    return 'Your numbers qualify. The daily status check will unlock selling on its next run.';
});

const picksProgressPct = computed<number>(() => {
    const p = props.seller_progress;
    if (!p || p.required_picks === 0) return 0;
    return Math.min(100, (p.current_picks / p.required_picks) * 100);
});

const rateProgressPct = computed<number>(() => {
    const p = props.seller_progress;
    if (!p || p.required_win_rate === 0 || p.current_win_rate === null) return 0;
    return Math.min(100, (p.current_win_rate / p.required_win_rate) * 100);
});

// ────────────── Page copy ──────────────
const pageCopy = computed(() => {
    if (props.can_sell && shouldList.value) {
        return {
            title: 'Name your price',
            eyebrow: 'Step 2 of 2',
            heading: 'Name your price',
            subheading:
                'Rate your confidence on every leg, then set what buyers will pay to unlock these picks.',
        };
    }

    if (props.can_sell && !shouldList.value) {
        return {
            title: 'Save your betslip',
            eyebrow: 'Step 2 of 2',
            heading: 'Save your betslip',
            subheading:
                'Rate your confidence on every leg. This one goes to your record only — nothing to set.',
        };
    }

    return {
        title: 'Save your betslip',
        eyebrow: 'Step 2 of 2',
        heading: 'Save your betslip',
        subheading:
            'Rate your confidence on every leg. This slip builds your seller record.',
    };
});

// ────────────── Price + caption state ──────────────
const shouldList = ref<boolean>(props.can_sell);
const price = ref<number | null>(null);
const caption = ref('');
const submitting = ref(false);

const canSubmit = computed(() => {
    if (submitting.value) return false;
    if (!allConfidencesSet.value) return false;
    if (!props.can_sell) return true;
    if (!shouldList.value) return true;
    return price.value !== null && price.value >= 1;
});

const breakEvenStake = computed<number | null>(() => {
    if (!props.can_sell || !shouldList.value) return null;
    if (!price.value || price.value < 1) return null;
    if (props.total_odds <= 1.0) return null;
    return Math.round((price.value / (props.total_odds - 1)) * 100) / 100;
});

// ────────────── Submit ──────────────
const submit = () => {
    if (!canSubmit.value) return;
    submitting.value = true;

    const odds = props.selections.map((s: any) => ({
        odd_id: s.odd_id,
        confidence: confidences.value[s.odd_id],
    }));

    router.post(
        '/betslip',
        {
            should_list: props.can_sell ? shouldList.value : false,
            price: shouldList.value && props.can_sell ? price.value : null,
            caption: caption.value.trim() || null,
            break_even_stake: breakEvenStake.value,
            odds,
        },
        {
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`${pageCopy.title} — Fainali`" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <Link href="/" class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline">
                ← Cancel and go back
            </Link>

            <!-- ═══ HERO ═══ -->
            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <div class="flex items-center gap-2">
                    <p class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                        {{ pageCopy.eyebrow }}
                    </p>
                    <InfoPopover title="Why the confidence rating matters">
                        <p>
                            Every leg of every betslip gets rated from 1 to 5.
                            This rating is not decoration — it feeds the
                            platform's crowd-wisdom engine and your reputation.
                        </p>
                        <p>
                            <span class="font-bold text-slate-200">1 (Wild guess):</span>
                            low signal, low weight. You're barely committing.
                        </p>
                        <p>
                            <span class="font-bold text-slate-200">5 (Locked in):</span>
                            high conviction, high weight. Your rating carries
                            meaningful influence in the crowd consensus.
                        </p>
                        <p>
                            <span class="font-bold text-slate-200">Reputation impact:</span>
                            a wrong 5-star pick hurts your prediction score more
                            than a wrong 1-star pick. Rate honestly — inflated
                            confidence is self-punishing.
                        </p>
                        <p class="text-slate-400">
                            You cannot submit until every leg is rated. This
                            ensures the crowd wisdom is built on considered
                            picks, not guesses.
                        </p>
                    </InfoPopover>
                </div>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    {{ pageCopy.heading }}
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed font-medium text-slate-400">
                    {{ pageCopy.subheading }}
                </p>
            </div>

            <!-- ═══ SELECTIONS WITH CONFIDENCE ═══ -->
            <section class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <header class="flex items-center justify-between border-b border-[#232d42] px-5 py-3">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Your selections
                    </span>
                    <div class="flex items-center gap-2">
                        <span
                            class="text-[10px] font-bold tracking-widest uppercase"
                            :class="allConfidencesSet ? 'text-emerald-400' : 'text-amber-400'"
                        >
                            {{ allConfidencesSet
                                ? `${selections.length} / ${selections.length} rated`
                                : `Rate every leg to continue` }}
                        </span>
                    </div>
                </header>

                <ul class="divide-y divide-[#232d42]/60">
                    <li v-for="(leg, i) in selections" :key="leg.odd_id" class="px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-200">
                                    {{ leg.home_team }}
                                    <span class="text-slate-500">vs</span>
                                    {{ leg.away_team }}
                                </p>
                                <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                    <span v-if="leg.league">{{ leg.league }}</span>
                                    <span v-if="leg.league && leg.kickoff" class="text-slate-600">·</span>
                                    <span v-if="leg.kickoff">{{ leg.kickoff }}</span>
                                </p>
                                <p class="mt-1 truncate text-[11px] font-semibold text-sky-400">
                                    {{ `${leg.market} - ${leg.selection}` }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="font-mono text-sm font-black text-emerald-400">
                                    {{ Number(leg.odds).toFixed(2) }}
                                </p>
                            </div>
                        </div>

                                                <!-- Confidence slider -->
                                                <div class="mt-3 rounded-lg border px-4 py-3"
                            :class="(confidences[leg.odd_id] ?? 0) > 0
                                ? 'border-[#232d42] bg-[#0f1422]'
                                : 'border-amber-500/40 bg-amber-500/5'"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                                        Confidence
                                    </span>
                                    <InfoPopover :title="`Confidence on ${leg.home_team} vs ${leg.away_team}`">
                                        <!-- The specific question -->
                                        <p class="text-sm font-medium text-white leading-relaxed">
                                            How confident are you that your selection of
                                            <strong class="text-amber-400">{{ leg.selection }}</strong>
                                            on the <strong class="text-sky-400">{{ leg.market }}</strong> market
                                            will win for this fixture?
                                        </p>
                                        
                                        <div class="mt-3 rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-[11px]">
                                            <p><span class="text-slate-400">1 ·</span> Wild guess — you're guessing</p>
                                            <p><span class="text-slate-400">2 ·</span> Weak — slight lean</p>
                                            <p><span class="text-slate-400">3 ·</span> Fair — reasonable read</p>
                                            <p><span class="text-slate-400">4 ·</span> Strong — confident</p>
                                            <p><span class="text-slate-400">5 ·</span> Locked in — you'd bet your reputation</p>
                                        </div>
                                        
                                        <p class="mt-2 text-slate-400">
                                            Whatever you choose, the crowd sees
                                            your rating weighted against everyone
                                            else's. Higher ratings have more
                                            influence on the aggregate signal.
                                        </p>
                                    </InfoPopover>
                                </div>
                                <span
                                    class="text-[10px] font-black tracking-widest uppercase"
                                    :class="(confidences[leg.odd_id] ?? 0) > 0
                                        ? 'text-amber-400'
                                        : 'text-amber-500'"
                                >
                                    {{ (confidences[leg.odd_id] ?? 0) > 0
                                        ? `${confidences[leg.odd_id]} · ${confidenceLabels[confidences[leg.odd_id]]}`
                                        : 'Rate your conviction' }}
                                </span>
                            </div>

                            <!-- Custom slider -->
                            <div class="relative mt-5 mb-6 select-none">
                                <!-- Track -->
                                <div class="relative h-2 rounded-full bg-[#232d42]">
                                    <!-- Fill -->
                                    <div
                                        class="absolute top-0 left-0 h-2 rounded-full transition-all"
                                        :class="(confidences[leg.odd_id] ?? 0) > 0
                                            ? 'bg-amber-500'
                                            : 'bg-[#232d42]'"
                                        :style="{ width: `${((confidences[leg.odd_id] ?? 0) / 5) * 100}%` }"
                                    ></div>
                                </div>

                                <!-- Ticks (clickable) -->
                                <div class="absolute inset-0 flex justify-between">
                                    <button
                                        v-for="n in [0, 1, 2, 3, 4, 5]"
                                        :key="n"
                                        type="button"
                                        @click="setConfidence(leg.odd_id, n)"
                                        class="relative flex h-2 w-2 -translate-y-1/2 cursor-pointer items-center justify-center"
                                        :aria-label="`Set confidence to ${n}`"
                                    >
                                        <span
                                            class="block h-1 w-1 rounded-full transition-colors"
                                            :class="(confidences[leg.odd_id] ?? 0) >= n
                                                ? 'bg-amber-200'
                                                : 'bg-[#3a4459]'"
                                        ></span>
                                    </button>
                                </div>

                                <!-- Thumb -->
                                <div
                                    class="pointer-events-none absolute top-1/2 -translate-y-1/2 -translate-x-1/2 transition-all"
                                    :style="{ left: `${((confidences[leg.odd_id] ?? 0) / 5) * 100}%` }"
                                >
                                    <div
                                        class="h-5 w-5 rounded-full border-2 transition-colors"
                                        :class="(confidences[leg.odd_id] ?? 0) > 0
                                            ? 'border-amber-300 bg-amber-500'
                                            : 'border-amber-500/50 bg-amber-500/20'"
                                    ></div>
                                </div>

                                <!-- Invisible range for accessibility + drag -->
                                <input
                                    type="range"
                                    min="0"
                                    max="5"
                                    step="1"
                                    :value="confidences[leg.odd_id] ?? 0"
                                    @input="setConfidence(leg.odd_id, ($event.target as HTMLInputElement).valueAsNumber)"
                                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                    aria-label="Confidence rating"
                                />
                            </div>

                            <!-- Tick labels -->
                            <div class="flex justify-between">
                                <span
                                    v-for="n in [0, 1, 2, 3, 4, 5]"
                                    :key="n"
                                    class="text-[9px] font-bold uppercase tracking-widest transition-colors"
                                    :class="(confidences[leg.odd_id] ?? 0) === n
                                        ? 'text-amber-400'
                                        : 'text-slate-600'"
                                >
                                    {{ n }}
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>

                <footer class="flex items-center justify-between border-t border-[#232d42] bg-[#0f1422] px-5 py-3">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Total odds
                    </span>
                    <span class="font-mono text-lg font-black text-emerald-400">
                        {{ Number(total_odds).toFixed(2) }}×
                    </span>
                </footer>
            </section>

            <!-- ═══ LISTING + PRICE + CAPTION ═══ -->
            <section class="mb-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                <template v-if="can_sell">
                    <div class="flex items-start gap-3">
                        <button type="button" role="switch" :aria-checked="shouldList" @click="shouldList = !shouldList"
                            class="mt-0.5 h-5 w-9 shrink-0 cursor-pointer rounded-full border transition-colors"
                            :class="shouldList ? 'border-amber-500 bg-amber-500' : 'border-[#232d42] bg-[#070b14]'">
                            <span class="block h-4 w-4 rounded-full bg-white transition-transform"
                                :class="shouldList ? 'translate-x-4' : 'translate-x-0.5'"></span>
                        </button>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                    List on marketplace
                                </p>
                                <InfoPopover title="Listing on the marketplace">
                                    <p>
                                        When you list a betslip, buyers can
                                        purchase it and unlock the picks
                                        inside. The price you set is what they
                                        pay; you're paid into your pending
                                        balance when the slip settles as a win.
                                    </p>
                                    <p>
                                        <span class="font-bold text-slate-200">If it wins:</span>
                                        you keep the price minus the platform
                                        fee. Buyers get their payout.
                                    </p>
                                    <p>
                                        <span class="font-bold text-slate-200">If it loses:</span>
                                        buyers are refunded their purchase
                                        price. You earn nothing on the slip
                                        but the losing prediction still counts
                                        toward your reputation.
                                    </p>
                                    <p class="text-slate-400">
                                        Buyers see your confidence ratings per
                                        leg when viewing the slip, so an
                                        honest rating helps sell it.
                                    </p>
                                </InfoPopover>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ shouldList
                                    ? 'Buyers can see and purchase these picks once you set a price.'
                                    : 'Saved to your record only. You can still build your seller history, and nobody can buy it.'
                                }}
                            </p>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Not listed
                        </p>
                        <InfoPopover title="Unlocking marketplace selling">
                            <p>
                                Selling opens once you've shown a track
                                record. It keeps buyers safe from unproven
                                sellers and rewards people who pick well
                                consistently.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">What counts:</span>
                                every selection on a betslip or contest you
                                submit is one pick. Once the fixture settles,
                                the pick settles with it. Voided picks don't
                                count either way.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">The threshold:</span>
                                {{ seller_progress?.required_picks ?? 20 }}
                                settled picks and a
                                {{ ((seller_progress?.required_win_rate ?? 0.55) * 100).toFixed(0) }}% win rate,
                                counted over the last 90 days.
                            </p>
                            <p class="text-slate-400">
                                You don't need to do anything special. The
                                status check runs daily.
                            </p>
                        </InfoPopover>
                    </div>
                    <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                        This betslip will be saved to your record. Listing on
                        the marketplace opens once you qualify as a seller.
                    </p>

                    <div v-if="seller_progress" class="mt-4 rounded-lg border border-[#232d42] bg-[#0f1422] px-3 py-3">
                        <div class="flex items-baseline justify-between">
                            <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Your progress</span>
                            <span class="font-mono text-[10px] text-slate-600">
                                {{ seller_progress.current_picks }} / {{ seller_progress.required_picks }} picks
                            </span>
                        </div>
                        <div class="mt-2 h-1 w-full overflow-hidden rounded-full bg-[#232d42]">
                            <div class="h-full rounded-full bg-sky-500/70 transition-all"
                                :style="{ width: picksProgressPct + '%' }"></div>
                        </div>
                        <div class="mt-3 flex items-baseline justify-between">
                            <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Win rate</span>
                            <span class="font-mono text-[10px] text-slate-600">
                                <template v-if="seller_progress.current_win_rate === null">—</template>
                                <template v-else>{{ (seller_progress.current_win_rate * 100).toFixed(1) }}%</template>
                                / {{ (seller_progress.required_win_rate * 100).toFixed(0) }}%
                            </span>
                        </div>
                        <div class="mt-2 h-1 w-full overflow-hidden rounded-full bg-[#232d42]">
                            <div class="h-full rounded-full bg-emerald-500/70 transition-all"
                                :style="{ width: rateProgressPct + '%' }"></div>
                        </div>
                        <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
                            {{ progressSummary }}
                        </p>
                    </div>
                </template>

                <div v-if="can_sell && shouldList" class="mt-6">
                    <div class="flex items-center gap-2">
                        <label for="price" class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                            Price to unlock
                        </label>
                        <InfoPopover title="How to set your price">
                            <p>
                                Buyers pay this amount to unlock your picks.
                                It goes straight into your pending balance on
                                purchase.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">Pricing advice:</span>
                                higher prices mean more earnings per sale
                                but fewer purchases. Lower prices move faster
                                but each sale is worth less.
                            </p>
                            <p>
                                <span class="font-bold text-slate-200">Break-even stake:</span>
                                the amount a buyer must stake at your odds to
                                earn back the price they paid. Buyers with
                                tight budgets filter on this — showing a
                                reasonable break-even makes your slip more
                                attractive.
                            </p>
                            <p class="text-slate-400">
                                Refund policy: if the betslip loses, buyers
                                are refunded their purchase price. You keep
                                nothing on a loss.
                            </p>
                        </InfoPopover>
                    </div>
                    <p class="mt-1 mb-3 text-[11px] text-slate-500">
                        What a buyer pays to see these picks. Paid into your pending balance.
                    </p>
                    <div class="relative">
                        <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm font-black tracking-widest text-slate-500">
                            KES
                        </span>
                        <input id="price" v-model.number="price" type="number" min="1" step="1" placeholder="0"
                            class="w-full rounded-lg border border-[#232d42] bg-[#070b14] py-3 pr-4 pl-16 font-mono text-lg font-black text-white placeholder-slate-700 focus:border-sky-500 focus:outline-none" />
                    </div>

                    <div v-if="breakEvenStake !== null"
                        class="mt-4 rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-3">
                        <div class="flex items-center gap-2">
                            <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                                Break-even stake for buyers
                            </p>
                            <InfoPopover title="What is break-even stake?">
                                <p>
                                    If a buyer stakes exactly this amount at
                                    {{ total_odds.toFixed(2) }}x odds and the
                                    slip wins, they earn back precisely what
                                    they paid for the slip — nothing more,
                                    nothing less.
                                </p>
                                <p>
                                    It's a useful filter for buyers with a
                                    fixed budget. A high break-even means a
                                    buyer needs to commit more capital to make
                                    the purchase worthwhile.
                                </p>
                            </InfoPopover>
                        </div>
                        <p class="mt-1.5 font-mono text-lg font-black text-sky-400">
                            KES
                            {{ breakEvenStake.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                            A buyer staking this much at
                            {{ total_odds.toFixed(2) }} odds earns back what
                            they paid for this slip.
                        </p>
                    </div>
                </div>

                <div v-if="can_sell && shouldList" class="mt-6">
                    <div class="flex items-center gap-2">
                        <label for="caption" class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                            Caption
                            <span class="font-medium text-slate-600">— optional</span>
                        </label>
                        <InfoPopover title="Captions sell slips">
                            <p>
                                A short pitch appears on your betslip's
                                marketplace card. Buyers see it before
                                they click through.
                            </p>
                            <p>
                                Good captions answer: why should they trust
                                this slip? What analysis went into it? What's
                                the edge?
                            </p>
                            <p class="text-slate-400">
                                Skip it if you'd rather let the odds speak
                                for themselves.
                            </p>
                        </InfoPopover>
                    </div>
                    <p class="mt-1 mb-3 text-[11px] text-slate-500">
                        A one-line pitch. Buyers see this on the marketplace card.
                    </p>
                    <textarea id="caption" v-model="caption" rows="3" maxlength="280"
                        placeholder="e.g. Three bankers from the Premier League this weekend. Studied every fixture."
                        class="w-full resize-none rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"></textarea>
                    <p class="mt-1 text-right text-[10px] font-bold tracking-widest text-slate-600 uppercase">
                        {{ caption.length }} / 280
                    </p>
                </div>
            </section>

            <!-- ═══ ACTIONS ═══ -->
            <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link href="/"
                    class="rounded-lg border border-[#232d42] px-6 py-3 text-center text-xs font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/50 hover:text-slate-200">
                    Cancel
                </Link>
                <button type="button" @click="submit" :disabled="!canSubmit"
                    class="rounded-lg bg-[#ff8c00] px-8 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100">
                    <template v-if="submitting">Creating…</template>
                    <template v-else-if="!allConfidencesSet">
                        Rate every leg to continue
                    </template>
                    <template v-else>
                        Create betslip
                    </template>
                </button>
            </div>
        </div>
    </div>
</template>