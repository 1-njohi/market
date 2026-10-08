<template>
    <div class="select-none">
        <!-- ═══ INFO STRIP ═══ -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-baseline gap-2">
                    <span
                        class="font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase"
                    >
                        {{ data.length }} days
                    </span>
                </div>

                <div class="flex items-baseline gap-2">
                    <span
                        class="font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase"
                    >
                        {{ resolvedValueLabel }}
                    </span>
                    <span
                        class="font-mono text-[13px] font-black tracking-tight"
                        :class="totalClass"
                    >
                        <template v-if="valueType === 'currency'">
                            <Money
                                :value="total"
                                :currency="currency"
                                :signed="true"
                            />
                        </template>
                        <template v-else>
                            {{ signedNumber(total) }}
                        </template>
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="flex items-center gap-1.5 rounded border border-emerald-500/20 bg-emerald-500/5 px-1.5 py-0.5 font-mono text-[9px] font-black tracking-widest text-emerald-400 uppercase"
                    :title="`${positiveDays} winning days`"
                >
                    <span class="text-[8px]">▲</span>
                    {{ positiveDays }}
                </span>
                <span
                    class="flex items-center gap-1.5 rounded border border-rose-500/20 bg-rose-500/5 px-1.5 py-0.5 font-mono text-[9px] font-black tracking-widest text-rose-400 uppercase"
                    :title="`${negativeDays} losing days`"
                >
                    <span class="text-[8px]">▼</span>
                    {{ negativeDays }}
                </span>
                <span
                    v-if="roi"
                    class="rounded border px-1.5 py-0.5 font-mono text-[9px] font-black tracking-widest uppercase"
                    :class="roiChipClass"
                >
                    {{ roi }} ROI
                </span>
            </div>
        </div>

        <!-- ═══ EMPTY ═══ -->
        <EmptyState
            v-if="!data || data.length === 0"
            :title="resolvedEmptyTitle"
            :body="resolvedEmptyBody"
            :accent="emptyAccent"
        />

        <!-- ═══ GRID ═══ -->
        <div v-else class="hide-scrollbar overflow-x-auto pb-1">
            <div class="inline-flex gap-3">
                <!-- Weekday labels -->
                <div class="flex flex-col gap-1 pt-[18px]">
                    <div
                        v-for="(d, i) in ['M', '', 'W', '', 'F', '', '']"
                        :key="i"
                        class="flex h-3.5 items-center font-mono text-[8px] font-bold tracking-wider text-slate-600 uppercase"
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
                    <div class="mb-1 h-3">
                        <span
                            class="font-mono text-[9px] font-bold tracking-wider text-slate-500 uppercase"
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
                                class="h-3.5 w-3.5 cursor-crosshair rounded-[3px] border transition-transform duration-150 hover:z-50 hover:scale-[1.7]"
                                :class="getDayClass(day)"
                                :style="getDayStyle(day)"
                                @mouseenter="handleHover($event, day)"
                                @mousemove="handleHover($event, day)"
                                @mouseleave="tooltip.show = false"
                            ></div>
                            <!-- Pad trailing slots -->
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
            v-if="data && data.length > 0"
            class="mt-5 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 border-t border-[#232d42]/60 pt-3 font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase"
        >
            <div class="flex items-center gap-1.5">
                <div
                    class="h-3 w-3 rounded-[3px] border border-[#232d42] bg-[#0a101f]"
                ></div>
                <span>Dormant</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div
                    class="h-3 w-3 rounded-[3px] border border-rose-500/60 bg-rose-500/30"
                ></div>
                <span>{{ negativeLegendLabel }}</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div
                    class="h-3 w-3 rounded-[3px] border border-emerald-500/60 bg-emerald-500/30"
                ></div>
                <span>{{ positiveLegendLabel }}</span>
            </div>
        </div>

        <!-- ═══ TOOLTIP ═══ -->
        <Teleport to="body">
            <div
                v-if="tooltip.show"
                :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
                class="pointer-events-none fixed z-[9999] -translate-x-1/2 -translate-y-full overflow-hidden rounded border border-[#232d42] bg-[#111622] shadow-2xl"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-[#232d42]/60 bg-[#111a30] px-3 py-1.5"
                >
                    <span
                        class="font-mono text-[10px] font-black tracking-widest text-white uppercase"
                    >
                        {{ tooltip.date }}
                    </span>
                    <span
                        class="font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase"
                    >
                        {{ tooltip.dayName }}
                    </span>
                </div>
                <div class="px-3 py-2">
                    <div
                        class="font-mono text-sm leading-none font-black"
                        :class="tooltipValueClass"
                    >
                        <template v-if="valueType === 'currency'">
                            <Money
                                :value="tooltip.value"
                                :currency="currency"
                                :signed="true"
                            />
                        </template>
                        <template v-else>
                            {{ signedNumber(tooltip.value) }}
                            <span
                                class="ml-0.5 text-[9px] font-bold text-slate-500"
                            >
                                tips
                            </span>
                        </template>
                    </div>
                    <div
                        v-if="tooltip.summary && showSummary"
                        class="mt-1.5 flex items-center gap-2 border-t border-[#232d42]/40 pt-1.5 font-mono text-[10px]"
                    >
                        <span class="text-slate-500 uppercase"
                            >{{ tooltip.summary.count }} settled</span
                        >
                        <span class="text-emerald-400"
                            >{{ tooltip.summary.won }}W</span
                        >
                        <span class="text-rose-400"
                            >{{ tooltip.summary.lost }}L</span
                        >
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Money from '@/components/Money.vue';
import EmptyState from '@/components/EmptyState.vue';

const props = defineProps({
    /** Array of { date, value, summary? } — oldest first. */
    data: { type: Array, default: () => [] },

    /**
     * 'profit' — signed values, positive = good (green).
     * 'spend'  — positive values = spend, colour by intensity (red).
     */
    mode: {
        type: String,
        default: 'profit',
        validator: (v) => ['profit', 'spend'].includes(v),
    },

    /** 'currency' renders via Money; 'count' renders as a plain number. */
    valueType: {
        type: String,
        default: 'currency',
        validator: (v) => ['currency', 'count'].includes(v),
    },

    currency: { type: String, default: 'KES' },

    /** Optional. Overrides the default label ("Net P/L" / "Net spend"). */
    valueLabel: { type: String, default: '' },

    /** Optional ROI chip, e.g. "+2.4%". */
    roi: { type: String, default: '' },

    /** Optional. Overrides the default empty title. */
    emptyTitle: { type: String, default: '' },

    /** Optional. Overrides the default empty body. */
    emptyBody: { type: String, default: '' },

    /** Show the { count, won, lost } summary row in the tooltip. */
    showSummary: { type: Boolean, default: true },
});

// ─── Resolved defaults ───
const resolvedValueLabel = computed(() => {
    if (props.valueLabel) return props.valueLabel;
    return props.mode === 'spend' ? 'Net spend' : 'Net P/L';
});

const resolvedEmptyTitle = computed(() => {
    if (props.emptyTitle) return props.emptyTitle;
    return props.valueType === 'count'
        ? 'No settled tips yet'
        : 'No activity yet';
});

const resolvedEmptyBody = computed(() => {
    if (props.emptyBody) return props.emptyBody;
    return props.valueType === 'count'
        ? 'Once your purchased tips settle, their daily outcomes will appear here.'
        : 'Settlements will appear here once your betslips reach a terminal state.';
});

const emptyAccent = computed(() => (props.mode === 'spend' ? 'rose' : 'emerald'));

const positiveLegendLabel = computed(() =>
    props.valueType === 'count' ? 'Net wins' : 'Net profit',
);
const negativeLegendLabel = computed(() =>
    props.valueType === 'count' ? 'Net losses' : 'Net loss',
);

// ─── Tooltip state ───
const tooltip = ref({
    show: false,
    x: 0,
    y: 0,
    date: '',
    dayName: '',
    value: 0,
    summary: null,
});

// ─── Structured months ───
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
        // Mon-first grid
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
        const v = Number(d.value) || 0;
        if (v > maxWin) maxWin = v;
        if (v < 0 && Math.abs(v) > maxLoss) maxLoss = Math.abs(v);
    });
    return { maxWin, maxLoss };
});

// ─── Aggregates ───
const total = computed(() =>
    props.data.reduce((sum, d) => sum + (Number(d.value) || 0), 0),
);

const positiveDays = computed(
    () => props.data.filter((d) => Number(d.value) > 0).length,
);

const negativeDays = computed(
    () => props.data.filter((d) => Number(d.value) < 0).length,
);

const totalClass = computed(() => {
    const t = total.value;
    if (t > 0) return 'text-emerald-400';
    if (t < 0) return 'text-rose-400';
    return 'text-slate-400';
});

const roiChipClass = computed(() => {
    const num = parseFloat(props.roi);
    if (Number.isNaN(num) || num === 0)
        return 'border-slate-500/20 bg-slate-500/5 text-slate-400';
    return num > 0
        ? 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400'
        : 'border-rose-500/20 bg-rose-500/5 text-rose-400';
});

// ─── Day cell classes / styles ───
const getDayClass = (day) => {
    if (day.isEmpty) return 'opacity-0';
    const v = Number(day.value) || 0;
    if (v > 0) return 'border-emerald-500/70';
    if (v < 0) return 'border-rose-500/70';
    return 'border-[#232d42]';
};

const getDayStyle = (day) => {
    if (day.isEmpty) return { visibility: 'hidden' };

    const v = Number(day.value) || 0;

    if (v === 0) {
        return { backgroundColor: 'rgba(255, 255, 255, 0.03)' };
    }

    const ratio =
        v > 0
            ? v / extremes.value.maxWin
            : Math.abs(v) / extremes.value.maxLoss;

    const fillAlpha = 0.15 + 0.85 * Math.min(ratio, 1);
    const glowSize = 2 + 8 * Math.min(ratio, 1);
    const glowAlpha = 0.15 + 0.35 * Math.min(ratio, 1);

    if (v > 0) {
        return {
            backgroundColor: `rgba(16, 185, 129, ${fillAlpha})`,
            boxShadow: `0 0 ${glowSize}px rgba(16, 185, 129, ${glowAlpha})`,
        };
    }

    return {
        backgroundColor: `rgba(239, 68, 68, ${fillAlpha})`,
        boxShadow: `0 0 ${glowSize}px rgba(239, 68, 68, ${glowAlpha})`,
    };
};

// ─── Tooltip helpers ───
const tooltipValueClass = computed(() => {
    const v = tooltip.value.value;
    if (v > 0) return 'text-emerald-400';
    if (v < 0) return 'text-rose-400';
    return 'text-slate-400';
});

const signedNumber = (n) => {
    if (n === undefined || n === null) return '0';
    const num = Number(n);
    if (num === 0) return '0';
    const sign = num > 0 ? '+' : '';
    return `${sign}${num.toLocaleString()}`;
};

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
        value: Number(day.value) || 0,
        summary: props.showSummary ? day.summary ?? null : null,
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