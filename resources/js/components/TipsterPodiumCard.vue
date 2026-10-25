<template>
    <button
        type="button"
        data-tipster-card
        @click="$emit('click')"
        class="group relative flex min-w-[290px] max-w-[290px] snap-start flex-col overflow-hidden rounded-3xl border border-[#232d42]/60 bg-gradient-to-br from-[#0d1527] to-[#0a1628] p-6 text-left transition-all hover:-translate-y-1 hover:border-sky-500/30 sm:min-w-[320px] sm:max-w-[320px]"
    >
        <!-- Tier glow -->
        <div
            class="pointer-events-none absolute -top-12 -right-12 h-32 w-32 rounded-full blur-3xl transition-opacity group-hover:opacity-100"
            :class="glowClass"
        ></div>

        <!-- Rank chip -->
        <div class="relative z-10 flex items-center justify-between">
            <span
                class="font-mono text-[10px] font-black tracking-[0.2em] text-slate-500 uppercase"
            >
                Rank
                <span class="ml-1 text-sky-400">
                    {{ String(rank).padStart(2, '0') }}
                </span>
            </span>
            <span
                v-if="tipster.roi"
                class="rounded-full px-2 py-0.5 font-mono text-[9px] font-black tracking-wider uppercase"
                :class="roiChipClass"
            >
                ROI +{{ tipster.roi }}%
            </span>
        </div>

        <!-- Avatar + name -->
        <div class="relative z-10 mt-8 flex items-center gap-4">
            <div
                class="relative h-16 w-16 flex-shrink-0 overflow-hidden rounded-2xl border border-[#232d42] bg-[#111622]"
            >
                <img
                    :src="tipster.avatar || dicebear"
                    :alt="tipster.name"
                    class="h-full w-full object-cover"
                />
            </div>
            <div class="min-w-0">
                <p
                    class="truncate text-base font-black tracking-wide text-white uppercase"
                >
                    {{ tipster.name }}
                </p>
                <p
                    class="mt-1 text-[9px] font-black tracking-[0.2em] text-slate-500 uppercase"
                >
                    @{{ tipster.code }}
                </p>
            </div>
        </div>

        <!-- Stat grid -->
        <div class="relative z-10 mt-6 grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-[#232d42]/60 bg-[#0a1628]/60 p-3">
                <p
                    class="text-[9px] font-black tracking-[0.2em] text-slate-500 uppercase"
                >
                    Win rate
                </p>
                <p class="mt-1 font-mono text-xl font-black text-white">
                    {{ tipster.win_rate }}%
                </p>
            </div>
            <div class="rounded-xl border border-[#232d42]/60 bg-[#0a1628]/60 p-3">
                <p
                    class="text-[9px] font-black tracking-[0.2em] text-slate-500 uppercase"
                >
                    Streak
                </p>
                <p class="mt-1 font-mono text-xl font-black text-sky-400">
                    {{ tipster.recent_form?.length ?? 0 }}
                </p>
            </div>
        </div>

        <!-- Recent form strip -->
        <div class="relative z-10 mt-5">
            <p
                class="text-[9px] font-black tracking-[0.2em] text-slate-500 uppercase"
            >
                Recent form
            </p>
            <div class="mt-2 flex gap-1">
                <span
                    v-for="(status, i) in displayedForm"
                    :key="i"
                    :class="[
                        'h-1.5 flex-1 rounded-full',
                        status === 'W'
                            ? 'bg-emerald-500/80'
                            : status === 'L'
                              ? 'bg-rose-500/70'
                              : 'bg-[#232d42]',
                    ]"
                ></span>
            </div>
        </div>

        <!-- CTA -->
        <div
            class="relative z-10 mt-6 flex items-center justify-between border-t border-[#232d42]/60 pt-5"
        >
            <span
                class="text-[10px] font-black tracking-[0.2em] text-slate-400 uppercase transition-colors group-hover:text-white"
            >
                View profile
            </span>
            <span
                class="flex h-7 w-7 items-center justify-center rounded-full border border-[#232d42] text-slate-400 transition-all group-hover:translate-x-0.5 group-hover:border-sky-500/40 group-hover:text-sky-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                    class="h-3 w-3"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m8.25 4.5 7.5 7.5-7.5 7.5"
                    />
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

const glowClass = computed(() => {
    if (props.rank === 1) return 'bg-amber-500/20';
    if (props.rank <= 3) return 'bg-sky-500/20';
    return 'bg-slate-500/10';
});

const roiChipClass = computed(() => {
    if (props.rank === 1) {
        return 'bg-amber-500/10 text-amber-400';
    }
    return 'bg-emerald-500/10 text-emerald-400';
});

// Show the last 5 form entries, padded to 5 with 'N' (no bet).
const displayedForm = computed(() => {
    const form = (props.tipster.recent_form ?? []).slice(-5);
    while (form.length < 5) form.unshift('N');
    return form;
});
</script>