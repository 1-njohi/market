<template>
    <div
        class="flex w-full flex-col items-start gap-3 rounded-xl border border-marketplace-border bg-marketplace-card p-4 shadow-lg transition-shadow hover:shadow-xl sm:flex-row sm:items-center sm:gap-4"
    >
        <!-- LEFT: Avatar + Name + Badges -->
        <div class="flex w-full items-center gap-3 sm:w-auto sm:flex-1">
            <!-- Avatar -->
            <div
                class="relative h-12 w-12 flex-shrink-0 overflow-hidden rounded-full border-2 border-marketplace-gold/30 bg-marketplace-gold/10"
            >
                <img
                    v-if="leader.avatar"
                    :src="leader.avatar"
                    :alt="leader.name"
                    class="h-full w-full object-cover"
                />
                
                <span
                    v-else
                    class="flex h-full w-full items-center justify-center text-sm font-bold text-marketplace-gold"
                >
                    {{ leader.name.substring(0, 2).toUpperCase() }}
                </span>
                <!-- Streak indicator ring (optional) -->
                <div
                    v-if="leader.streak > 0"
                    class="absolute -bottom-0.5 -right-0.5 rounded-full border-2 border-marketplace-bg bg-marketplace-green px-1 text-[8px] font-black text-white"
                >
                    {{ leader.streak }}
                </div>
            </div>

            <!-- Name + Badges -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <div class="flex items-center gap-2">
                    <h4
                        class="truncate text-sm font-extrabold tracking-wide text-white"
                    >
                        {{ leader.name }}
                    </h4>
                    <span
                        v-if="leader.streak > 0"
                        class="rounded-full bg-marketplace-green/20 px-2 py-0.5 text-[8px] font-black tracking-wider text-marketplace-green uppercase"
                    >
                        🔥 {{ leader.streak }} streak
                    </span>
                </div>

                <!-- Badges -->
                <div v-if="leader.badges && leader.badges.length" class="mt-1 flex flex-wrap items-center gap-1.5">
                    <div
                        v-for="(badge, idx) in visibleBadges"
                        :key="idx"
                        class="flex items-center gap-0.5 rounded-full border border-marketplace-gold/20 bg-marketplace-gold/10 px-1.5 py-0.5"
                        :title="badge.name + ' · ' + formatDate(badge.created_at)"
                    >
                        <img
                            v-if="badge.avatar"
                            :src="badge.avatar"
                            class="h-4 w-4 rounded-full object-cover"
                        />
                        <span
                            v-else
                            class="text-[8px] font-black tracking-wider text-marketplace-gold"
                        >
                            {{ badge.name.substring(0, 1) }}
                        </span>
                        <span class="text-[8px] font-bold text-marketplace-gold/80">
                            {{ badge.name }}
                        </span>
                    </div>
                    <span
                        v-if="leader.badges.length > maxVisibleBadges"
                        class="text-[8px] font-bold text-marketplace-muted"
                    >
                        +{{ leader.badges.length - maxVisibleBadges }}
                    </span>
                </div>
            </div>
        </div>

        <!-- RIGHT: Stats + Recent Form -->
        <div class="flex w-full flex-wrap items-center justify-between gap-2 sm:w-auto sm:flex-nowrap">
            <!-- Stats -->
            <div class="flex flex-1 items-center gap-3 sm:flex-nowrap">
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase text-marketplace-muted">ROI</div>
                    <div class="text-sm font-black text-marketplace-green">
                        +{{ leader.roi }}%
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase text-marketplace-muted">Win Rate</div>
                    <div class="text-sm font-black text-marketplace-gold">
                        {{ leader.win_rate }}%
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase text-marketplace-muted">Active</div>
                    <div class="text-sm font-black text-white">
                        {{ leader.active_tips }}
                    </div>
                </div>
            </div>

            <!-- Recent Form Heatmap -->
            <div class="flex flex-col items-end">
                <div class="flex space-x-1">
                    <span
                        v-for="(status, index) in flippedRecentForm"
                        :key="index"
                        :class="[
                            'h-3.5 w-3.5 rounded-sm border',
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
                                  : 'No Bet'
                        "
                    ></span>
                </div>
                <span
                    class="mt-0.5 text-[8px] font-bold tracking-wider text-marketplace-muted uppercase"
                >
                    Form
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    leader: {
        type: Object,
        required: true,
        validator: (obj) => {
            return (
                obj.name &&
                typeof obj.roi === 'number' &&
                typeof obj.win_rate === 'number' &&
                typeof obj.streak === 'number' &&
                typeof obj.active_tips === 'number' &&
                (typeof obj.recent_form === 'string' || Array.isArray(obj.recent_form))
            );
        },
    },
});

// Parse recent_form: it's a string like "L, W, W, W, W, L, W"
// We'll split and trim, and reverse to show most recent first.
const recentFormArray = computed(() => {
    if (!props.leader.recent_form) return [];
    if (Array.isArray(props.leader.recent_form)) {
        return props.leader.recent_form.map(s => s.trim());
    }
    return props.leader.recent_form.split(',').map(s => s.trim());
});

// Reverse to show latest on the right (most recent first?)
// The original component reversed, but we want latest on the right for chronological reading.
// But they used reverse(), so we'll do the same: reverse to show recent form from oldest to newest? Actually they reversed and displayed left-to-right, so the leftmost is the oldest.
// We'll keep the same logic: reverse the array so the first item is the oldest, last is newest, and they display left-to-right.
const flippedRecentForm = computed(() => {
    return [...recentFormArray.value].reverse();
});

// Badges: show max 3 visible, plus count
const maxVisibleBadges = 3;
const visibleBadges = computed(() => {
    if (!props.leader.badges) return [];
    return props.leader.badges.slice(0, maxVisibleBadges);
});

// Helper to format date for tooltip
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        return new Date(dateStr).toLocaleDateString();
    } catch {
        return dateStr;
    }
};
</script>