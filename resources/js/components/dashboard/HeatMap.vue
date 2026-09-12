<template>
    <div
        class="rounded-xl border border-marketplace-border bg-marketplace-card p-5 shadow-lg select-none"
    >
        <!-- ═══ HEADER ═══ -->
        <div class="mb-5 flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-marketplace-gold/30 bg-marketplace-gold/10 text-marketplace-gold"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2.5"
                        stroke="currentColor"
                        class="h-3.5 w-3.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"
                        />
                    </svg>
                </div>
                <div>
                    <h3
                        class="text-xs font-black tracking-widest text-white uppercase"
                    >
                        Bet History Audit
                    </h3>
                    <p
                        class="mt-0.5 text-[10px] font-semibold tracking-wide text-marketplace-muted uppercase"
                    >
                        Last {{ data.length }} Days
                    </p>
                </div>
            </div>

            <span
                class="rounded border border-marketplace-green/20 bg-marketplace-green/10 px-2 py-0.5 font-mono text-xs font-black text-marketplace-green"
            >
                {{ roi }} ROI
            </span>
        </div>

        <!-- ═══ EMPTY ═══ -->
        <div
            v-if="!data || data.length === 0"
            class="p-6 text-center font-mono text-[10px] tracking-wider text-marketplace-muted uppercase"
        >
            No bet history yet.
        </div>

        <!-- ═══ GRID ═══ -->
        <div v-else class="hide-scrollbar overflow-x-auto pb-1">
            <div class="inline-flex gap-3">
                <!-- Weekday labels column -->
                <div class="flex flex-col gap-1 pt-6">
                    <div
                        v-for="(d, i) in ['M', '', 'W', '', 'F', '', '']"
                        :key="i"
                        class="flex h-3.5 items-center text-[9px] font-bold tracking-wider text-marketplace-muted uppercase"
                    >
                        {{ d }}
                    </div>
                </div>

                <!-- Month blocks -->
                <div
                    v-for="(month, mIndex) in structuredMonths"
                    :key="mIndex"
                    class="flex flex-col"
                >
                    <!-- Month label -->
                    <div class="mb-2 h-4">
                        <span
                            class="flex text-[9px] font-bold tracking-wider text-marketplace-muted uppercase"
                        >
                            {{ month.name }}
                        </span>
                    </div>

                    <!-- Week columns -->
                    <div class="flex gap-1">
                        <div
                            v-for="(col, cIndex) in month.columns"
                            :key="cIndex"
                            class="flex shrink-0 flex-col gap-1"
                        >
                            <div
                                v-for="(day, dIndex) in col"
                                :key="dIndex"
                                class="h-3.5 w-3.5 cursor-crosshair rounded-full border transition-all duration-150 hover:z-50 hover:scale-[1.6]"
                                :class="getDayClass(day)"
                                :style="getDayStyle(day)"
                                @mouseenter="handleHover($event, day)"
                                @mousemove="handleHover($event, day)"
                                @mouseleave="tooltip.show = false"
                            ></div>
                            <!-- Pad trailing slots so months align -->
                            <div
                                v-for="fill in 7 - col.length"
                                :key="'f' + fill"
                                class="h-3.5 w-3.5"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ LEGEND ═══ -->
        <div
            class="mt-5 flex items-center justify-center border-t border-marketplace-border/40 pt-4 text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
        >
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <div
                        class="h-3 w-3 rounded-full border border-marketplace-border bg-marketplace-card/60"
                    ></div>
                    <span>Dormant</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div
                        class="h-3 w-3 rounded-full border border-rose-500/60 bg-rose-500/30"
                    ></div>
                    <span>Net Loss</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div
                        class="h-3 w-3 rounded-full border border-marketplace-green/60 bg-marketplace-green/30"
                    ></div>
                    <span>Net Profit</span>
                </div>
            </div>
        </div>

        <!-- ═══ TOOLTIP ═══ -->
        <Teleport to="body">
            <div
                v-if="tooltip.show"
                :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
                class="pointer-events-none fixed z-[9999] -translate-x-1/2 -translate-y-full overflow-hidden rounded-lg border border-marketplace-border bg-marketplace-card shadow-2xl"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-marketplace-border/60 bg-marketplace-card/80 px-3 py-1.5"
                >
                    <span
                        class="text-[10px] font-black tracking-wider text-white uppercase"
                    >
                        {{ tooltip.date }}
                    </span>
                    <span
                        class="text-[9px] font-bold tracking-wider text-marketplace-muted uppercase"
                    >
                        {{ tooltip.dayName }}
                    </span>
                </div>
                <div class="px-3 py-2">
                    <div
                        class="font-mono text-sm font-black leading-none"
                        :class="
                            tooltip.value > 0
                                ? 'text-marketplace-green'
                                : tooltip.value < 0
                                  ? 'text-rose-400'
                                  : 'text-marketplace-muted'
                        "
                    >
                        {{ tooltip.value > 0 ? '+' : ''
                        }}{{ tooltip.value.toLocaleString() }}
                        <span class="text-[9px] font-bold">KES</span>
                    </div>
                    <div
                        v-if="tooltip.summary"
                        class="mt-1.5 flex items-center gap-2 border-t border-marketplace-border/40 pt-1.5 font-mono text-[10px]"
                    >
                        <span class="text-marketplace-muted uppercase"
                            >{{ tooltip.summary.total_bets }} bets</span
                        >
                        <span class="text-marketplace-green"
                            >{{ tooltip.summary.total_won }}W</span
                        >
                        <span class="text-rose-400"
                            >{{ tooltip.summary.total_lost }}L</span
                        >
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    roi: { type: String, default: '0.0%' },
});

const tooltip = ref({
    show: false,
    x: 0,
    y: 0,
    date: '',
    dayName: '',
    value: 0,
    summary: null,
});

// ─── Group into months with proper start-of-week padding ───
const structuredMonths = computed(() => {
    if (!props.data.length) return [];

    const months = [];
    let currentMonthData = [];
    let lastMonth = new Date(props.data[0].date).getMonth();

    props.data.forEach((day) => {
        const month = new Date(day.date).getMonth();
        if (month !== lastMonth) {
            months.push(currentMonthData);
            currentMonthData = [];
            lastMonth = month;
        }
        currentMonthData.push(day);
    });
    months.push(currentMonthData);

    return months.map((monthDays) => {
        const columns = [];
        let currentColumn = [];

        const firstDay = new Date(monthDays[0].date);
        // 0 = Sun, 1 = Mon ... 6 = Sat. We want Mon-first columns.
        const startDay = (firstDay.getDay() + 6) % 7;

        for (let i = 0; i < startDay; i++) {
            currentColumn.push({ isEmpty: true });
        }

        monthDays.forEach((day) => {
            currentColumn.push({ ...day, isEmpty: false });
            if (currentColumn.length === 7) {
                columns.push(currentColumn);
                currentColumn = [];
            }
        });

        if (currentColumn.length > 0) columns.push(currentColumn);

        return {
            name: firstDay.toLocaleString('default', { month: 'short' }),
            columns,
        };
    });
});

// ─── Extremes for intensity mapping ───
const extremes = computed(() => {
    let maxWin = 0.01;
    let maxLoss = 0.01;
    props.data.forEach((d) => {
        if (d.value > maxWin) maxWin = d.value;
        if (d.value < 0 && Math.abs(d.value) > maxLoss)
            maxLoss = Math.abs(d.value);
    });
    return { maxWin, maxLoss };
});

// ─── Class: base border color per day ───
const getDayClass = (day) => {
    if (day.isEmpty) return 'opacity-0';
    if (day.value > 0) return 'border-marketplace-green/70';
    if (day.value < 0) return 'border-rose-500/70';
    return 'border-marketplace-border/40';
};

// ─── Style: fill + glow intensity ───
const getDayStyle = (day) => {
    if (day.isEmpty) return { visibility: 'hidden' };

    if (day.value === 0) {
        return { backgroundColor: 'rgba(255, 255, 255, 0.03)' };
    }

    if (day.value > 0) {
        const ratio = day.value / extremes.value.maxWin;
        const fillAlpha = 0.15 + 0.85 * ratio;
        const glowSize = 2 + 8 * ratio;
        const glowAlpha = 0.15 + 0.35 * ratio;

        return {
            backgroundColor: `rgba(16, 185, 129, ${fillAlpha})`,
            boxShadow: `0 0 ${glowSize}px rgba(16, 185, 129, ${glowAlpha})`,
        };
    }

    // Loss
    const ratio = Math.abs(day.value) / extremes.value.maxLoss;
    const fillAlpha = 0.15 + 0.85 * ratio;
    const glowSize = 2 + 8 * ratio;
    const glowAlpha = 0.15 + 0.35 * ratio;

    return {
        backgroundColor: `rgba(239, 68, 68, ${fillAlpha})`,
        boxShadow: `0 0 ${glowSize}px rgba(239, 68, 68, ${glowAlpha})`,
    };
};

// ─── Hover ───
const handleHover = (e, day) => {
    if (day.isEmpty) return;
    const dateObj = new Date(day.date);
    tooltip.value = {
        show: true,
        x: e.clientX,
        y: e.clientY - 12,
        date: dateObj.toLocaleString('default', {
            month: 'short',
            day: 'numeric',
        }),
        dayName: dateObj.toLocaleString('default', { weekday: 'short' }),
        value: day.value,
        summary: day.summary ?? null,
    };
};
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>