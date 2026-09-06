<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    roi: { type: String, default: '0.0%' },
});

const tooltip = ref({ show: false, text: '', dayName: '', x: 0, y: 0 });

// 1. Logic: Group days into months, and pad the START of every month's first week
const structuredMonths = computed(() => {
    if (!props.data.length) return [];

    const months = [];
    let currentMonthData = [];
    let lastMonth = new Date(props.data[0].date).getMonth();

    // Grouping into months
    props.data.forEach((day) => {
        const date = new Date(day.date);
        const month = date.getMonth();
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

        // ALIGNMENT FIX: Look at the first day of THIS month
        const firstDayOfMonth = new Date(monthDays[0].date);
        const startDayOfWeek = firstDayOfMonth.getDay(); // 0 = Sun, 1 = Mon...

        // Fill the first column with empty slots until we reach the start day
        for (let i = 0; i < startDayOfWeek; i++) {
            currentColumn.push({ isEmpty: true });
        }

        monthDays.forEach((day) => {
            currentColumn.push({ ...day, isEmpty: false });
            // When column hits 7 days, push it and start a new one
            if (currentColumn.length === 7) {
                columns.push(currentColumn);
                currentColumn = [];
            }
        });

        // Push the final partial column of the month
        if (currentColumn.length > 0) {
            columns.push(currentColumn);
        }

        return {
            name: firstDayOfMonth.toLocaleString('default', { month: 'short' }),
            columns,
        };
    });
});

const extremes = computed(() => {
    let maxWin = 0.01;
    let maxLoss = 0.01;
    props.data.forEach((d) => {
        if (d.value > maxWin) maxWin = d.value;
        if (Math.abs(d.value) > maxLoss && d.value < 0)
            maxLoss = Math.abs(d.value);
    });
    return { maxWin, maxLoss };
});

const getDayStyle = (day) => {
    if (day.isEmpty) return { visibility: 'hidden' };
    if (day.value === 0) return { backgroundColor: '#1c1c1c' };

    if (day.value > 0) {
        const intensity = 0.2 + 0.8 * (day.value / extremes.value.maxWin);
        return 'W'
        return { backgroundColor: `rgba(16, 185, 129, ${intensity})` };
    } else {
        const intensity =
            0.2 + 0.8 * (Math.abs(day.value) / extremes.value.maxLoss);
            return 'L'
        return { backgroundColor: `rgba(239, 68, 68, ${intensity})` };
    }
};

const handleHover = (e, day) => {
    if (day.isEmpty) return;
    const dateObj = new Date(day.date);
    tooltip.value = {
        show: true,
        text: `${day.label}`,
        dayName: dateObj.toLocaleDateString('default', { weekday: 'long' }),
        x: e.clientX,
        y: e.clientY - 85,
    };
};
</script>

<template>
    <div
        class="rounded-2xl border border-white/5 bg-[#09090b] p-6 text-zinc-100 shadow-2xl select-none"
    >
        <div class="mb-8 flex items-center justify-between">
            <h3
                class="text-[10px] font-black tracking-[0.3em] text-zinc-200 uppercase"
            >
                Bet History Audit
            </h3>
            <span class="font-mono text-xs font-bold text-emerald-500"
                >{{ roi }} ROI</span
            >
        </div>

        <div class="hide-scrollbar overflow-x-auto">
            <div class="inline-flex items-end gap-4 pb-2">
                <div
                    v-for="(month, mIndex) in structuredMonths"
                    :key="mIndex"
                    class="flex flex-col"
                >
                    <div class="mb-2 h-4">
                        <span
                            class="flex justify-center text-[9px] font-bold text-zinc-600 uppercase"
                        >
                            {{ month.name }}
                        </span>
                    </div>

                    <div class="flex gap-1">
                        <div
                            v-for="(col, cIndex) in month.columns"
                            :key="cIndex"
                            class="flex shrink-0 flex-col gap-1"
                        >
                            <div
                                v-for="(day, dIndex) in col"
                                :key="dIndex"
                                class="h-3 w-3 cursor-crosshair rounded-[1px] transition-all duration-75 hover:z-50 hover:scale-150"
                                :class="[
                                    'h-3 w-3 rounded-sm border-[0.5px]',
                                    getDayStyle(day) === 'W'
                                        ? 'border-green-500 bg-green-500/20 shadow-sm shadow-green-500/10'
                                        : getDayStyle(day) == 'L'
                                          ? 'border-red-500 bg-red-500/20'
                                          : 'border-gray-500 bg-gray-500/20',
                                ]"
                                :title="
                                    getDayStyle(day) === 'W'
                                        ? 'Won'
                                        : getDayStyle(day) == 'L'
                                          ? 'Lost'
                                          : 'No Bet'
                                "
                                @mousemove="handleHover($event, day)"
                                @mouseleave="tooltip.show = false"
                            ></div>
                            <div
                                v-for="fill in 7 - col.length"
                                :key="'f' + fill"
                                class="h-3 w-3"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="mt-6 flex items-center justify-center border-t border-white/5 pt-6 text-[10px] font-bold text-zinc-500 uppercase"
        >
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <div class="h-2 w-2 rounded-sm bg-zinc-800"></div>
                    <span>Dormant</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="h-2 w-2 rounded-sm bg-red-600"></div>
                    <span>Net Loss</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div
                        class="h-2 w-2 rounded-sm bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.4)]"
                    ></div>
                    <span>Net Profit</span>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="tooltip.show"
                :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
                class="pointer-events-none fixed z-[9999] -translate-x-1/2 overflow-hidden rounded border border-white/10 bg-[#18181b] text-white shadow-2xl"
            >
                <div class="border-b border-white/5 bg-zinc-800/50 px-3 py-1">
                    <span
                        class="text-[9px] font-black text-zinc-400 uppercase"
                        >{{ tooltip.dayName }}</span
                    >
                </div>
                <div class="px-3 py-2 text-[10px] leading-tight font-bold">
                    {{ tooltip.text }}
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
