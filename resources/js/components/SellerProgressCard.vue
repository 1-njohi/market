<script setup lang="ts">
import { computed } from 'vue';
import InfoPopover from '@/components/InfoPopover.vue';

const props = defineProps({
    progress: { type: Object, required: true },
    status: { type: String, default: 'ineligible' },
});

const picksProgressPct = computed(() => {
    const p = props.progress;
    if (!p || !p.required_picks) return 0;
    return Math.min(100, (p.current_picks / p.required_picks) * 100);
});

const rateProgressPct = computed(() => {
    const p = props.progress;
    if (!p || !p.required_win_rate || p.current_win_rate === null) return 0;
    return Math.min(100, (p.current_win_rate / p.required_win_rate) * 100);
});

/**
 * One-line summary of how far the user is from qualifying.
 * Branches on the binding constraint.
 */
const summary = computed<string>(() => {
    const p = props.progress;
    if (!p) return '';

    const picksDeficit = Math.max(0, p.required_picks - p.current_picks);
    const hasRate = p.current_win_rate !== null;
    const rateMissing = !hasRate || p.current_win_rate < p.required_win_rate;
    const requiredRatePct = (p.required_win_rate * 100).toFixed(0);

    if (props.status === 'delisted') {
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
        return `${gap}% below the ${requiredRatePct}% win rate threshold.`;
    }

    return 'Your numbers qualify. The daily status check will unlock selling on its next run.';
});
</script>

<template>
    <section class="relative overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 sm:p-6">
        <div class="pointer-events-none absolute -top-16 -right-12 h-44 w-44 rounded-full bg-sky-400/10 blur-3xl" aria-hidden="true"></div>

        <!-- Header -->
        <div class="relative flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-white">Become a seller</h2>
                <InfoPopover title="How to become a seller">
                    <p>
                        Selling on the marketplace opens once you've built
                        a track record. This keeps buyers safe from unproven
                        sellers and rewards people who pick well consistently.
                    </p>

                    <p>
                        <span class="font-bold text-slate-100">What counts as a pick:</span>
                        every selection you make counts. Whether it's one leg
                        on a betslip you create, an entry in a contest, or a
                        matchday submission — they all feed the same record.
                        A pick only counts once its game has finished.
                    </p>

                    <p>
                        <span class="font-bold text-slate-100">The threshold:</span>
                        {{ progress.required_picks }} settled
                        picks and a {{ (progress.required_win_rate * 100).toFixed(0) }}% win rate, counted over the last 90 days.
                    </p>

                    <p><span class="font-bold text-slate-100">Fastest ways to build your record:</span></p>

                    <div class="rounded-2xl border border-amber-400/20 bg-amber-400/5 p-3.5">
                        <p class="mb-1 text-sm font-bold text-amber-300">Matchday league</p>
                        <p class="text-sm text-slate-300">
                            Every weekend there's a fresh set of fixtures. One
                            pick per game — usually 10 picks at once. No
                            entry fee. If you're in a sub-league, you get
                            auto-entered. If not, join one with an invite code.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-emerald-400/20 bg-emerald-400/5 p-3.5">
                        <p class="mb-1 text-sm font-bold text-emerald-300">Contests</p>
                        <p class="text-sm text-slate-300">
                            Public or private. Same rules as matchday: pick one
                            selection per leg, get it right, count the point.
                            Private contests with friends are usually 5–10
                            picks. Free to enter.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-sky-400/20 bg-sky-400/5 p-3.5">
                        <p class="mb-1 text-sm font-bold text-sky-300">Betslips</p>
                        <p class="text-sm text-slate-300">
                            Build your own selections from any fixture. A
                            five-leg slip is five picks toward your record.
                            You don't need to sell it to count it — the picks
                            count whether or not the slip is listed.
                        </p>
                    </div>

                    <p><span class="font-bold text-slate-100">Practical tips:</span></p>
                    <p>
                        Don't chase long odds just to pad your count — the
                        win rate matters as much as the volume. A slip with
                        five safe picks beats one with five long shots. If
                        you're stuck on picks, look at what the top sellers
                        are backing before a weekend and see if you agree.
                    </p>
                    <p>
                        Every pick only counts once. Duplicating the same
                        selection across several slips won't move your
                        progress faster.
                    </p>
                    <p class="text-slate-400">
                        You don't need to do anything special. The status
                        check runs daily — as soon as both numbers clear,
                        the Sell tab appears.
                    </p>
                </InfoPopover>
            </div>

            <span
                class="rounded-full px-3 py-1 text-xs font-bold capitalize"
                :class="{
                    'bg-amber-400/10 text-amber-300': status === 'ineligible',
                    'bg-emerald-400/10 text-emerald-300': status === 'sellable',
                    'bg-rose-400/10 text-rose-300': status === 'warned' || status === 'delisted',
                }"
            >
                {{ status }}
            </span>
        </div>

        <!-- Two gates: picks, then win rate -->
        <div class="relative mt-5 grid gap-5 sm:grid-cols-2">
            <div>
                <div class="flex items-end justify-between">
                    <p class="text-sm text-slate-400">Settled picks</p>
                    <p class="text-sm text-slate-500 tabular-nums">
                        <span class="text-2xl font-black text-white">{{ progress.current_picks }}</span> / {{ progress.required_picks }}
                    </p>
                </div>
                <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-white/[0.07]" role="progressbar" :aria-valuenow="Math.round(picksProgressPct)" aria-valuemin="0" aria-valuemax="100" aria-label="Settled picks progress">
                    <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-indigo-400 transition-all duration-500" :style="{ width: picksProgressPct + '%' }"></div>
                </div>
            </div>

            <div>
                <div class="flex items-end justify-between">
                    <p class="text-sm text-slate-400">Win rate</p>
                    <p class="text-sm text-slate-500 tabular-nums">
                        <span class="text-2xl font-black text-white">
                            <template v-if="progress.current_win_rate === null">—</template>
                            <template v-else>{{ (progress.current_win_rate * 100).toFixed(1) }}%</template>
                        </span>
                        / {{ (progress.required_win_rate * 100).toFixed(0) }}%
                    </p>
                </div>
                <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-white/[0.07]" role="progressbar" :aria-valuenow="Math.round(rateProgressPct)" aria-valuemin="0" aria-valuemax="100" aria-label="Win rate progress">
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-lime-300 transition-all duration-500" :style="{ width: rateProgressPct + '%' }"></div>
                </div>
            </div>
        </div>

        <!-- Human summary -->
        <p class="relative mt-5 rounded-2xl bg-black/25 px-4 py-3 text-sm leading-relaxed text-slate-300">{{ summary }}</p>
    </section>
</template>