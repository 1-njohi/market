<template>
    <button
        type="button"
        data-tipster-card
        @click="$emit('click')"
        class="group relative flex min-w-[290px] max-w-[290px] snap-start flex-col overflow-hidden rounded-3xl border bg-[#0d1527] p-6 text-left transition-all duration-300 hover:-translate-y-1.5 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none sm:min-w-[320px] sm:max-w-[320px]"
        :class="tier.card"
    >
        <!-- Tier light: stronger for the podium, barely there for the rest -->
        <div class="pointer-events-none absolute -top-16 -right-10 h-48 w-48 rounded-full blur-3xl transition-opacity duration-500 group-hover:opacity-100" :class="tier.glow" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent" :class="tier.line" aria-hidden="true"></div>

        <!-- Oversized rank numeral -->
        <span
            class="pointer-events-none absolute -top-3 right-3 text-[7.5rem] leading-none font-black tracking-tighter select-none"
            :class="tier.numeral"
            aria-hidden="true"
        >{{ rank }}</span>

        <!-- Identity -->
        <div class="relative z-10 flex items-center gap-4">
            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-[#111622] ring-2 ring-offset-2 ring-offset-[#0d1527]" :class="tier.ring">
                <img :src="tipster.avatar || dicebear" :alt="tipster.name" class="h-full w-full object-cover" />
            </div>
            <div class="min-w-0">
                <p class="sr-only">Rank {{ rank }}</p>
                <p class="truncate text-lg font-black tracking-tight text-white">{{ tipster.name }}</p>
                <p class="mt-0.5 truncate text-sm text-slate-500">@{{ tipster.code }}</p>
            </div>
        </div>

        <!-- ROI is the headline number -->
        <div class="relative z-10 mt-8">
            <template v-if="tipster.roi">
                <p class="text-sm text-slate-400">ROI</p>
                <p class="text-5xl font-black tracking-[-0.04em] tabular-nums" :class="tier.roi">+{{ tipster.roi }}<span class="text-3xl">%</span></p>
            </template>
            <template v-else>
                <p class="text-sm text-slate-400">Win rate</p>
                <p class="text-5xl font-black tracking-[-0.04em] text-white tabular-nums">{{ tipster.win_rate }}<span class="text-3xl text-slate-500">%</span></p>
            </template>
        </div>

        <!-- Supporting stats -->
        <dl class="relative z-10 mt-5 flex divide-x divide-white/[0.08] rounded-2xl border border-white/[0.06] bg-white/[0.03]">
            <div v-if="tipster.roi" class="flex-1 px-4 py-3">
                <dt class="text-xs text-slate-500">Win rate</dt>
                <dd class="mt-0.5 text-lg font-bold text-white tabular-nums">{{ tipster.win_rate }}%</dd>
            </div>
            <div class="flex-1 px-4 py-3">
                <dt class="text-xs text-slate-500">Streak</dt>
                <dd class="mt-0.5 text-lg font-bold tabular-nums" :class="streak > 0 ? 'text-emerald-400' : 'text-slate-300'">{{ streak }}</dd>
            </div>
        </dl>

        <!-- Recent form -->
        <div class="relative z-10 mt-5">
            <p class="text-xs text-slate-500">Recent form</p>
            <div class="mt-2 flex gap-1.5" role="img" :aria-label="'Recent form: ' + displayedForm.join(', ')">
                <span
                    v-for="(status, i) in displayedForm"
                    :key="i"
                    class="h-5 flex-1 rounded-md"
                    :class="
                        status === 'W'
                            ? 'bg-emerald-400/80 shadow-[0_0_10px_rgba(52,211,153,0.35)]'
                            : status === 'L'
                              ? 'bg-rose-400/75 shadow-[0_0_10px_rgba(251,113,133,0.3)]'
                              : 'bg-white/[0.07]'
                    "
                ></span>
            </div>
        </div>

        <!-- CTA -->
        <div class="relative z-10 mt-6 flex items-center justify-between border-t border-white/[0.06] pt-5">
            <span class="text-sm font-bold text-slate-300 transition-colors group-hover:text-white">View profile</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 text-slate-400 transition-all duration-300 group-hover:translate-x-1 group-hover:text-black" :class="tier.cta">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </span>
        </div>
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    tipster: { type: Object, required: true },
    rank: { type: Number, required: true },
});

defineEmits(['click']);

const dicebear = computed(
    () =>
        `https://api.dicebear.com/10.x/lorelei-neutral/svg?seed=${props.tipster.code ?? props.tipster.id}`,
);

// Gold for first, sky for the podium, quiet slate for everyone else.
const tier = computed(() => {
    if (props.rank === 1) {
        return {
            card: 'border-amber-300/30 hover:border-amber-300/60 hover:shadow-[0_28px_60px_-24px_rgba(251,191,36,0.45)]',
            glow: 'bg-amber-400/25 opacity-80',
            line: 'via-amber-300/70',
            numeral: 'bg-gradient-to-b from-amber-300/40 to-transparent bg-clip-text text-transparent',
            ring: 'ring-amber-300/70',
            roi: 'bg-gradient-to-r from-amber-200 to-amber-400 bg-clip-text text-transparent',
            cta: 'group-hover:border-amber-300 group-hover:bg-amber-300',
        };
    }
    if (props.rank <= 3) {
        return {
            card: 'border-sky-400/20 hover:border-sky-400/50 hover:shadow-[0_28px_60px_-24px_rgba(14,165,233,0.4)]',
            glow: 'bg-sky-500/20 opacity-70',
            line: 'via-sky-300/60',
            numeral: 'bg-gradient-to-b from-sky-300/30 to-transparent bg-clip-text text-transparent',
            ring: 'ring-sky-400/60',
            roi: 'text-emerald-300',
            cta: 'group-hover:border-sky-300 group-hover:bg-sky-300',
        };
    }
    return {
        card: 'border-white/[0.07] hover:border-white/20 hover:shadow-[0_28px_60px_-28px_rgba(0,0,0,0.9)]',
        glow: 'bg-slate-400/10 opacity-60',
        line: 'via-white/20',
        numeral: 'bg-gradient-to-b from-white/15 to-transparent bg-clip-text text-transparent',
        ring: 'ring-white/20',
        roi: 'text-emerald-300',
        cta: 'group-hover:border-white group-hover:bg-white',
    };
});

// Show the last 5 form entries, padded to 5 with 'N' (no bet).
const displayedForm = computed(() => {
    const form = (props.tipster.recent_form ?? []).slice(-5);
    while (form.length < 5) form.unshift('N');
    return form;
});

// Current run of wins, counted back from the latest result.
const streak = computed(() => {
    const form = props.tipster.recent_form ?? [];
    let run = 0;
    for (let i = form.length - 1; i >= 0 && form[i] === 'W'; i--) run++;
    return run;
});
</script>