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
    is_analyst: { type: Boolean, default: false },
    analyst_published_today: { type: Boolean, default: false },
});


const isAnalystSlip = ref(false);
const analystTitle = ref('');
const reasoning = ref('');
const comments = ref<Record<number, string>>(
    Object.fromEntries(props.selections.map((s: any) => [s.odd_id, ''])),
);

const setComment = (oddId: number, value: string) => {
    comments.value = { ...comments.value, [oddId]: value };
};

const reasoningWordCount = computed(() =>
    reasoning.value.trim().split(/\s+/).filter(Boolean).length,
);

const reasoningValid = computed(() =>
    reasoningWordCount.value >= 100 && reasoningWordCount.value <= 500,
);

const allCommentsSet = computed(() =>
    props.selections.every((s: any) => (comments.value[s.odd_id] ?? '').trim().length > 0),
);

const analystFormValid = computed(() =>
    analystTitle.value.trim().length > 0
    && reasoningValid.value
    && allCommentsSet.value,
);

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

// How many legs are rated so far, for the header progress.
const ratedCount = computed(() =>
    props.selections.filter((s: any) => (confidences.value[s.odd_id] ?? 0) > 0).length,
);

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
    if (isAnalystSlip.value && !analystFormValid.value) return false;
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

    const payload: Record<string, any> = {
        should_list: props.can_sell ? shouldList.value : false,
        price: shouldList.value && props.can_sell ? price.value : null,
        caption: caption.value.trim() || null,
        break_even_stake: breakEvenStake.value,
        odds: props.selections.map((s: any) => ({
            odd_id: s.odd_id,
            confidence: confidences.value[s.odd_id],
            ...(isAnalystSlip.value ? { comment: (comments.value[s.odd_id] ?? '').trim() } : {}),
        })),
    };

    if (isAnalystSlip.value) {
        payload.is_analyst_slip = true;
        payload.analyst_title = analystTitle.value.trim();
        payload.reasoning = reasoning.value.trim();
    }

    router.post('/betslip', payload, { onFinish: () => { submitting.value = false; } });

};

// Shared field look (full class strings so Tailwind can see them)
const FIELD =
    'w-full rounded-xl border border-white/10 bg-white/[0.04] px-4 text-base font-medium text-white placeholder:text-slate-500 transition-all focus:border-amber-300/60 focus:bg-white/[0.06] focus:ring-2 focus:ring-amber-300/20 focus:outline-none';
</script>

<template>

    <Head :title="`${pageCopy.title} — Fainali`" />

    <div class="min-h-screen bg-[#070b14] px-4 pt-6 pb-8 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl pr-3 text-sm font-bold text-slate-400 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Cancel and go back
            </Link>

            <!-- ═══ HEADING ═══ -->
            <header class="mt-4 mb-8">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-amber-400">{{ pageCopy.eyebrow }}</p>
                    <InfoPopover title="Why the confidence rating matters">
                        <p>
                            Every leg of every betslip gets rated from 1 to 5.
                            This rating is not decoration — it feeds the
                            platform's crowd-wisdom engine and your reputation.
                        </p>
                        <p>
                            <span class="font-bold text-slate-100">1 (Wild guess):</span>
                            low signal, low weight. You're barely committing.
                        </p>
                        <p>
                            <span class="font-bold text-slate-100">5 (Locked in):</span>
                            high conviction, high weight. Your rating carries
                            meaningful influence in the crowd consensus.
                        </p>
                        <p>
                            <span class="font-bold text-slate-100">Reputation impact:</span>
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
                <!-- Two steps, so a two-segment bar says where you are -->
                <div class="mt-3 flex gap-1.5" role="img" aria-label="Step 2 of 2">
                    <span class="h-1.5 flex-1 rounded-full bg-amber-400"></span>
                    <span class="h-1.5 flex-1 rounded-full bg-amber-400"></span>
                </div>
                <h1 class="mt-5 text-4xl font-black tracking-tight text-white md:text-5xl">{{ pageCopy.heading }}</h1>
                <p class="mt-3 max-w-2xl text-base leading-relaxed text-slate-400">{{ pageCopy.subheading }}</p>
            </header>

            <!-- ═══ SELECTIONS WITH CONFIDENCE ═══ -->
            <section class="mb-6 overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]">
                <header class="flex items-center justify-between gap-3 border-b border-white/[0.06] bg-black/25 px-5 py-3.5">
                    <h2 class="text-base font-bold text-white">Your selections</h2>
                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-bold tabular-nums"
                        :class="allConfidencesSet ? 'bg-emerald-400/10 text-emerald-300' : 'bg-amber-400/10 text-amber-300'"
                    >
                        {{ allConfidencesSet ? `${selections.length} / ${selections.length} rated` : `${ratedCount} / ${selections.length} rated` }}
                    </span>
                </header>

                <ul class="divide-y divide-white/[0.06]">
                    <li v-for="(leg, i) in selections" :key="leg.odd_id" class="px-5 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-lg font-bold text-white">
                                    {{ leg.home_team }}
                                    <span class="font-normal text-slate-500">vs</span>
                                    {{ leg.away_team }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    <span v-if="leg.league">{{ leg.league }}</span>
                                    <span v-if="leg.league && leg.kickoff" class="mx-1 text-slate-600">·</span>
                                    <span v-if="leg.kickoff">{{ leg.kickoff }}</span>
                                </p>
                                <p class="mt-2 inline-block rounded-lg bg-sky-400/10 px-2.5 py-1 text-sm font-bold text-sky-300">
                                    {{ `${leg.market} - ${leg.selection}` }}
                                </p>
                            </div>
                            <p class="shrink-0 text-2xl font-black text-emerald-300 tabular-nums">{{ Number(leg.odds).toFixed(2) }}</p>
                        </div>

                        <!-- Confidence: tap 1 to 5 (tap the chosen one again to clear it) -->
                        <div
                            class="mt-4 rounded-2xl border p-4 transition-colors"
                            :class="(confidences[leg.odd_id] ?? 0) > 0 ? 'border-white/[0.07] bg-black/25' : 'border-amber-400/30 bg-amber-400/[0.05]'"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-300">Confidence</span>
                                    <InfoPopover :title="`Confidence on ${leg.home_team} vs ${leg.away_team}`">
                                        <!-- The specific question -->
                                        <p class="font-medium text-white">
                                            How confident are you that your selection of
                                            <strong class="text-amber-300">{{ leg.selection }}</strong>
                                            on the <strong class="text-sky-300">{{ leg.market }}</strong> market
                                            will win for this fixture?
                                        </p>

                                        <div class="mt-3 rounded-2xl border border-white/10 bg-black/30 px-4 py-3 text-sm">
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
                                <span class="text-sm font-black" :class="(confidences[leg.odd_id] ?? 0) > 0 ? 'text-amber-300' : 'text-amber-400'">
                                    {{ (confidences[leg.odd_id] ?? 0) > 0 ? `${confidences[leg.odd_id]} · ${confidenceLabels[confidences[leg.odd_id]]}` : 'Rate your conviction' }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-5 gap-1.5" role="radiogroup" :aria-label="`Confidence for ${leg.home_team} vs ${leg.away_team}`">
                                <button
                                    v-for="n in [1, 2, 3, 4, 5]"
                                    :key="n"
                                    type="button"
                                    role="radio"
                                    :aria-checked="(confidences[leg.odd_id] ?? 0) === n"
                                    :aria-label="`${n}, ${confidenceLabels[n]}`"
                                    @click="setConfidence(leg.odd_id, (confidences[leg.odd_id] ?? 0) === n ? 0 : n)"
                                    :class="[
                                        'flex min-h-14 cursor-pointer flex-col items-center justify-center gap-0.5 rounded-xl border px-1 transition-all duration-150 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95',
                                        (confidences[leg.odd_id] ?? 0) >= n
                                            ? (confidences[leg.odd_id] ?? 0) === n
                                                ? 'border-amber-400 bg-amber-400 text-[#070b14] shadow-[0_8px_20px_-8px_rgba(251,191,36,0.8)]'
                                                : 'border-amber-400/40 bg-amber-400/20 text-amber-100'
                                            : 'border-white/[0.08] bg-white/[0.04] text-slate-300 hover:border-amber-300/50',
                                    ]"
                                >
                                    <span class="text-lg font-black tabular-nums">{{ n }}</span>
                                    <span class="hidden w-full truncate text-center text-[11px] leading-none font-semibold sm:block" :class="(confidences[leg.odd_id] ?? 0) === n ? 'text-[#070b14]/70' : 'text-slate-500'">{{ confidenceLabels[n] }}</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="isAnalystSlip" class="mt-4">
                            <label :for="`comment-${leg.odd_id}`" class="text-sm font-semibold text-sky-300">Comment on this leg</label>
                            <textarea
                                :id="`comment-${leg.odd_id}`"
                                :value="comments[leg.odd_id] ?? ''"
                                @input="setComment(leg.odd_id, ($event.target as HTMLTextAreaElement).value)"
                                rows="2"
                                maxlength="200"
                                placeholder="One or two sentences on why you like this leg specifically."
                                :class="[FIELD, 'mt-1.5 resize-none py-3']"
                            ></textarea>
                            <p class="mt-1 text-right text-xs text-slate-500 tabular-nums">{{ (comments[leg.odd_id] ?? '').length }} / 200</p>
                        </div>
                    </li>
                </ul>

                <footer class="flex items-center justify-between border-t border-white/[0.06] bg-black/25 px-5 py-4">
                    <span class="text-sm text-slate-400">Total odds</span>
                    <span class="text-2xl font-black text-emerald-300 tabular-nums">{{ Number(total_odds).toFixed(2) }}×</span>
                </footer>
            </section>

            <!-- ═══ ANALYST PICK ═══ -->
            <section v-if="is_analyst" class="mb-6 rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 sm:p-6">
                <div class="flex items-start gap-4">
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="isAnalystSlip"
                        aria-label="Publish as analyst pick"
                        :disabled="analyst_published_today"
                        @click="isAnalystSlip = !isAnalystSlip"
                        class="relative mt-0.5 h-7 w-12 shrink-0 cursor-pointer rounded-full border transition-colors focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                        :class="isAnalystSlip ? 'border-amber-400 bg-amber-400' : 'border-white/15 bg-white/[0.06]'"
                    >
                        <span class="absolute top-0.5 left-0.5 block h-5.5 w-5.5 rounded-full bg-white shadow transition-transform" :class="isAnalystSlip ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="text-base font-bold text-amber-300">Publish as analyst pick</p>
                        <p class="mt-1 text-sm leading-relaxed text-slate-500">
                            <template v-if="analyst_published_today">You've already published an analyst pick today. Come back tomorrow.</template>
                            <template v-else>Analyst picks appear in the public archive at /analyst-picks with your byline. Free to view, one per day.</template>
                        </p>
                    </div>
                </div>

                <div v-if="isAnalystSlip" class="mt-6 space-y-5">
                    <div>
                        <label for="analyst-title" class="block text-sm font-semibold text-sky-300">Headline</label>
                        <input id="analyst-title" v-model="analystTitle" type="text" maxlength="140" placeholder="e.g. Three bankers from the KPL this weekend" :class="[FIELD, 'mt-2 h-12']" />
                        <p class="mt-1 text-right text-xs text-slate-500 tabular-nums">{{ analystTitle.length }} / 140</p>
                    </div>

                    <div>
                        <label for="analyst-reasoning" class="block text-sm font-semibold text-sky-300">
                            Reasoning
                            <span class="font-normal text-slate-500">— 100 to 500 words</span>
                        </label>
                        <textarea
                            id="analyst-reasoning"
                            v-model="reasoning"
                            rows="8"
                            placeholder="Write the case for this slip. What did you see in the form, the line movement, the team news? This is what the archive will judge you on."
                            :class="[FIELD, 'mt-2 resize-none py-3']"
                        ></textarea>
                        <p class="mt-1 text-right text-sm font-bold tabular-nums" :class="reasoningValid ? 'text-emerald-300' : 'text-amber-300'">{{ reasoningWordCount }} / 100–500 words</p>
                    </div>
                </div>
            </section>

            <!-- ═══ LISTING + PRICE + CAPTION ═══ -->
            <section class="mb-6 rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 sm:p-6">
                <template v-if="can_sell">
                    <div class="flex items-start gap-4">
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="shouldList"
                            aria-label="List on marketplace"
                            @click="shouldList = !shouldList"
                            class="relative mt-0.5 h-7 w-12 shrink-0 cursor-pointer rounded-full border transition-colors focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
                            :class="shouldList ? 'border-amber-400 bg-amber-400' : 'border-white/15 bg-white/[0.06]'"
                        >
                            <span class="absolute top-0.5 left-0.5 block h-5.5 w-5.5 rounded-full bg-white shadow transition-transform" :class="shouldList ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-base font-bold text-white">List on marketplace</p>
                                <InfoPopover title="Listing on the marketplace">
                                    <p>
                                        When you list a betslip, buyers can
                                        purchase it and unlock the picks
                                        inside. The price you set is what they
                                        pay; you're paid into your pending
                                        balance when the slip settles as a win.
                                    </p>
                                    <p>
                                        <span class="font-bold text-slate-100">If it wins:</span>
                                        you keep the price minus the platform
                                        fee. Buyers get their payout.
                                    </p>
                                    <p>
                                        <span class="font-bold text-slate-100">If it loses:</span>
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
                            <p class="mt-1 text-sm leading-relaxed text-slate-500">
                                {{ shouldList ? 'Buyers can see and purchase these picks once you set a price.' : 'Saved to your record only. You can still build your seller history, and nobody can buy it.' }}
                            </p>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="flex items-center gap-2">
                        <p class="text-base font-bold text-slate-300">Not listed</p>
                        <InfoPopover title="Unlocking marketplace selling">
                            <p>
                                Selling opens once you've shown a track
                                record. It keeps buyers safe from unproven
                                sellers and rewards people who pick well
                                consistently.
                            </p>
                            <p>
                                <span class="font-bold text-slate-100">What counts:</span>
                                every selection on a betslip or contest you
                                submit is one pick. Once the fixture settles,
                                the pick settles with it. Voided picks don't
                                count either way.
                            </p>
                            <p>
                                <span class="font-bold text-slate-100">The threshold:</span>
                                {{ seller_progress?.required_picks ?? 20 }}
                                settled picks and a
                                {{ ((seller_progress?.required_win_rate ?? 0.55) * 100).toFixed(0) }}% win rate,
                                counted over the last 90 days.
                            </p>
                            <p class="text-slate-400">You don't need to do anything special. The status check runs daily.</p>
                        </InfoPopover>
                    </div>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                        This betslip will be saved to your record. Listing on the marketplace opens once you qualify as a seller.
                    </p>

                    <div v-if="seller_progress" class="mt-5 rounded-2xl border border-white/[0.06] bg-black/25 p-4">
                        <p class="text-sm font-semibold text-slate-300">Your progress</p>

                        <div class="mt-3 flex items-baseline justify-between">
                            <span class="text-sm text-slate-400">Settled picks</span>
                            <span class="text-sm text-slate-500 tabular-nums">
                                <span class="text-lg font-black text-white">{{ seller_progress.current_picks }}</span> / {{ seller_progress.required_picks }}
                            </span>
                        </div>
                        <div class="mt-1.5 h-2.5 w-full overflow-hidden rounded-full bg-white/[0.07]" role="progressbar" :aria-valuenow="Math.round(picksProgressPct)" aria-valuemin="0" aria-valuemax="100" aria-label="Settled picks progress">
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-indigo-400 transition-all duration-500" :style="{ width: picksProgressPct + '%' }"></div>
                        </div>

                        <div class="mt-4 flex items-baseline justify-between">
                            <span class="text-sm text-slate-400">Win rate</span>
                            <span class="text-sm text-slate-500 tabular-nums">
                                <span class="text-lg font-black text-white">
                                    <template v-if="seller_progress.current_win_rate === null">—</template>
                                    <template v-else>{{ (seller_progress.current_win_rate * 100).toFixed(1) }}%</template>
                                </span>
                                / {{ (seller_progress.required_win_rate * 100).toFixed(0) }}%
                            </span>
                        </div>
                        <div class="mt-1.5 h-2.5 w-full overflow-hidden rounded-full bg-white/[0.07]" role="progressbar" :aria-valuenow="Math.round(rateProgressPct)" aria-valuemin="0" aria-valuemax="100" aria-label="Win rate progress">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-lime-300 transition-all duration-500" :style="{ width: rateProgressPct + '%' }"></div>
                        </div>

                        <p class="mt-4 text-sm leading-relaxed text-slate-400">{{ progressSummary }}</p>
                    </div>
                </template>

                <div v-if="can_sell && shouldList" class="mt-7">
                    <div class="flex items-center gap-2">
                        <label for="price" class="block text-base font-bold text-white">Price to unlock</label>
                        <InfoPopover title="How to set your price">
                            <p>
                                Buyers pay this amount to unlock your picks.
                                It goes straight into your pending balance on
                                purchase.
                            </p>
                            <p>
                                <span class="font-bold text-slate-100">Pricing advice:</span>
                                higher prices mean more earnings per sale
                                but fewer purchases. Lower prices move faster
                                but each sale is worth less.
                            </p>
                            <p>
                                <span class="font-bold text-slate-100">Break-even stake:</span>
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
                    <p class="mt-1 mb-3 text-sm text-slate-500">What a buyer pays to see these picks. Paid into your pending balance.</p>
                    <div class="relative">
                        <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-base font-bold text-slate-500">KES</span>
                        <input
                            id="price"
                            v-model.number="price"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            step="1"
                            placeholder="0"
                            :class="[FIELD, 'h-16 pl-16 text-3xl font-black tabular-nums']"
                        />
                    </div>

                    <div v-if="breakEvenStake !== null" class="mt-4 rounded-2xl border border-sky-400/20 bg-sky-400/[0.06] p-4">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-slate-300">Break-even stake for buyers</p>
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
                        <p class="mt-1.5 text-3xl font-black text-sky-300 tabular-nums">
                            KES
                            {{ breakEvenStake.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                        </p>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            A buyer staking this much at {{ total_odds.toFixed(2) }} odds earns back what they paid for this slip.
                        </p>
                    </div>
                </div>

                <div v-if="can_sell && shouldList" class="mt-7">
                    <div class="flex items-center gap-2">
                        <label for="caption" class="block text-base font-bold text-white">
                            Caption
                            <span class="text-sm font-normal text-slate-500">— optional</span>
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
                            <p class="text-slate-400">Skip it if you'd rather let the odds speak for themselves.</p>
                        </InfoPopover>
                    </div>
                    <p class="mt-1 mb-3 text-sm text-slate-500">A one-line pitch. Buyers see this on the marketplace card.</p>
                    <textarea
                        id="caption"
                        v-model="caption"
                        rows="3"
                        maxlength="280"
                        placeholder="e.g. Three bankers from the Premier League this weekend. Studied every fixture."
                        :class="[FIELD, 'resize-none py-3']"
                    ></textarea>
                    <p class="mt-1 text-right text-xs text-slate-500 tabular-nums">{{ caption.length }} / 280</p>
                </div>
            </section>
        </div>

        <!-- ═══ ACTIONS: stuck to the bottom so the finish line is always one thumb away ═══ -->
        <div class="sticky bottom-0 z-30 -mx-4 border-t border-white/[0.08] bg-[#070b14]/90 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur-xl md:-mx-6 md:px-6 lg:-mx-8 lg:px-8">
            <div class="mx-auto flex w-full max-w-3xl items-center gap-3">
                <Link
                    href="/"
                    class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] px-6 text-sm font-bold text-slate-300 transition-colors hover:border-white/25 hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
                >
                    Cancel
                </Link>
                <button
                    type="button"
                    @click="submit"
                    :disabled="!canSubmit"
                    :aria-busy="submitting"
                    class="min-h-12 flex-1 cursor-pointer rounded-xl bg-amber-400 px-6 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all duration-200 hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none disabled:hover:bg-amber-400"
                >
                    <template v-if="submitting">Creating…</template>
                    <template v-else-if="!allConfidencesSet">Rate every leg to continue</template>
                    <template v-else-if="isAnalystSlip && !analystFormValid">Complete the analyst fields</template>
                    <template v-else-if="isAnalystSlip">Publish analyst pick</template>
                    <template v-else>Create betslip</template>
                </button>
            </div>
        </div>
    </div>
</template>