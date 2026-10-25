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
    <div class="rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-4">
        <!-- Header -->
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    Seller progress
                </span>
                <InfoPopover title="How to become a seller">
                    <p>
                        Selling on the marketplace opens once you've built
                        a track record. This keeps buyers safe from unproven
                        sellers and rewards people who pick well consistently.
                    </p>

                    <p>
                        <span class="font-bold text-slate-200">
                            What counts as a pick:
                        </span>
                        every selection you make counts. Whether it's one leg
                        on a betslip you create, an entry in a contest, or a
                        matchday submission — they all feed the same record.
                        A pick only counts once its game has finished.
                    </p>

                    <p>
                        <span class="font-bold text-slate-200">
                            The threshold:
                        </span>
                        {{ progress.required_picks }} settled
                        picks and a {{
                            (progress.required_win_rate * 100).toFixed(0)
                        }}% win rate, counted over the last 90 days.
                    </p>

                    <p>
                        <span class="font-bold text-slate-200">
                            Fastest ways to build your record:
                        </span>
                    </p>

                    <div class="rounded border border-amber-500/20 bg-amber-500/5 px-3 py-2">
                        <p class="mb-1 text-[10px] font-black tracking-widest text-amber-400 uppercase">
                            Matchday league
                        </p>
                        <p class="text-[11px] text-slate-300">
                            Every weekend there's a fresh set of fixtures. One
                            pick per game — usually 10 picks at once. No
                            entry fee. If you're in a sub-league, you get
                            auto-entered. If not, join one with an invite code.
                        </p>
                    </div>

                    <div class="rounded border border-emerald-500/20 bg-emerald-500/5 px-3 py-2">
                        <p class="mb-1 text-[10px] font-black tracking-widest text-emerald-400 uppercase">
                            Contests
                        </p>
                        <p class="text-[11px] text-slate-300">
                            Public or private. Same rules as matchday: pick one
                            selection per leg, get it right, count the point.
                            Private contests with friends are usually 5–10
                            picks. Free to enter.
                        </p>
                    </div>

                    <div class="rounded border border-sky-500/20 bg-sky-500/5 px-3 py-2">
                        <p class="mb-1 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                            Betslips
                        </p>
                        <p class="text-[11px] text-slate-300">
                            Build your own selections from any fixture. A
                            five-leg slip is five picks toward your record.
                            You don't need to sell it to count it — the picks
                            count whether or not the slip is listed.
                        </p>
                    </div>

                    <p>
                        <span class="font-bold text-slate-200">
                            Practical tips:
                        </span>
                    </p>
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

            <span class="rounded border px-2 py-0.5 font-mono text-[9px] font-black tracking-wider uppercase" :class="{
                'border-amber-500/20 bg-amber-500/10 text-amber-400':
                    status === 'ineligible',
                'border-emerald-500/20 bg-emerald-500/10 text-emerald-400':
                    status === 'sellable',
                'border-rose-500/20 bg-rose-500/10 text-rose-400':
                    status === 'warned' || status === 'delisted',
            }">
                {{ status }}
            </span>
        </div>

        <!-- Picks row -->
        <div class="mt-4 flex items-baseline justify-between">
            <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                Settled picks
            </span>
            <span class="font-mono text-[11px] text-slate-400">
                {{ progress.current_picks }} / {{ progress.required_picks }}
            </span>
        </div>
        <div class="mt-1.5 h-1 w-full overflow-hidden rounded-full bg-[#232d42]">
            <div class="h-full rounded-full bg-sky-500/70 transition-all" :style="{ width: picksProgressPct + '%' }">
            </div>
        </div>

        <!-- Rate row -->
        <div class="mt-3 flex items-baseline justify-between">
            <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                Win rate
            </span>
            <span class="font-mono text-[11px] text-slate-400">
                <template v-if="progress.current_win_rate === null">—</template>
                <template v-else>
                    {{ (progress.current_win_rate * 100).toFixed(1) }}%
                </template>
                /
                {{ (progress.required_win_rate * 100).toFixed(0) }}%
            </span>
        </div>
        <div class="mt-1.5 h-1 w-full overflow-hidden rounded-full bg-[#232d42]">
            <div class="h-full rounded-full bg-emerald-500/70 transition-all" :style="{ width: rateProgressPct + '%' }">
            </div>
        </div>

        <!-- Human summary -->
        <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
            {{ summary }}
        </p>
    </div>
</template>