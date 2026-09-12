<template>
    <div
        :class="[
            'flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-4 shadow-lg transition-all hover:shadow-xl',
            highlighted
                ? 'border-marketplace-gold/50 bg-marketplace-gold/5'
                : 'border-marketplace-border bg-marketplace-card hover:bg-marketplace-card/80',
        ]"
    >
        <!-- ═══ LEFT: Rank + Avatar + Info ═══ -->
        <div class="flex min-w-0 flex-1 items-center space-x-3">
            <!-- Rank badge -->
            <div
                v-if="rank"
                :class="[
                    'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border text-sm font-black',
                    rank === 1
                        ? 'border-marketplace-gold bg-marketplace-gold/20 text-marketplace-gold'
                        : rank === 2
                          ? 'border-slate-300/40 bg-slate-300/10 text-slate-200'
                          : rank === 3
                            ? 'border-amber-600/40 bg-amber-600/10 text-amber-500'
                            : 'border-marketplace-border bg-marketplace-card/60 text-marketplace-muted',
                ]"
            >
                {{ rank }}
            </div>

            <!-- Avatar -->
            <div
                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border border-marketplace-gold/30 bg-marketplace-gold/10"
            >
                <img
                    :src="leader.avatar"
                    :alt="leader.name"
                    class="h-full w-full rounded-full object-cover"
                />
            </div>

            <!-- Name + ROI/WR -->
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <h4
                        class="truncate text-xs font-bold tracking-wider text-white uppercase"
                    >
                        {{ leader.name }}
                    </h4>
                    <span
                        v-if="leader.streak > 0"
                        class="flex flex-shrink-0 items-center gap-0.5 rounded bg-marketplace-green/10 px-1.5 py-0.5 text-[9px] font-black text-marketplace-green uppercase"
                        :title="`${leader.streak} win streak`"
                    >
                        🔥 {{ leader.streak }}
                    </span>
                </div>
                <div
                    class="mt-0.5 flex items-center space-x-1 text-[10px] font-semibold tracking-wide text-marketplace-muted uppercase"
                >
                    <span
                        >ROI:
                        <span class="font-bold text-marketplace-green"
                            >+{{ leader.roi }}%</span
                        ></span
                    >
                    <span class="text-marketplace-border">•</span>
                    <span
                        >WR:
                        <span class="font-bold text-marketplace-gold"
                            >{{ leader.win_rate }}%</span
                        ></span
                    >
                </div>
            </div>
        </div>

        <!-- ═══ RIGHT: Mini Heatmap ═══ -->
        <div class="flex flex-shrink-0 flex-col items-end">
            <div class="flex space-x-1">
                <span
                    v-for="(status, i) in (leader.recent_form || []).slice(0, 6)"
                    :key="i"
                    :class="[
                        'h-3 w-3 rounded-sm border',
                        status === 'W'
                            ? 'border-marketplace-green bg-marketplace-green/20'
                            : status === 'L'
                              ? 'border-red-500/60 bg-red-500/20'
                              : 'border-marketplace-border bg-marketplace-border/30',
                    ]"
                    :title="
                        status === 'W'
                            ? 'Won'
                            : status === 'L'
                              ? 'Lost'
                              : 'Pending'
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
</template>

<script setup>
defineProps({
    leader: {
        type: Object,
        required: true,
    },
    rank: {
        type: Number,
        default: null,
    },
    highlighted: {
        type: Boolean,
        default: false,
    },
});
</script>