<template>
    <svg
        :viewBox="`0 0 ${width} ${height}`"
        :width="width"
        :height="height"
        preserveAspectRatio="none"
        class="overflow-visible"
        :aria-label="`Trend: ${values.length} points`"
        role="img"
    >
        <!-- Baseline -->
        <line
            v-if="showBaseline"
            :x1="0"
            :y1="height / 2"
            :x2="width"
            :y2="height / 2"
            :stroke="baselineColor"
            stroke-width="0.5"
            stroke-dasharray="2 2"
        />

        <!-- Line -->
        <polyline
            v-if="points.length > 0"
            :points="points"
            fill="none"
            :stroke="strokeColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
            vector-effect="non-scaling-stroke"
        />

        <!-- End dot -->
        <circle
            v-if="lastPoint"
            :cx="lastPoint.x"
            :cy="lastPoint.y"
            r="1.8"
            :fill="strokeColor"
        />
    </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    values: { type: Array, default: () => [] },
    width: { type: Number, default: 72 },
    height: { type: Number, default: 20 },
    accent: {
        type: String,
        default: 'sky',
        validator: (v) =>
            ['sky', 'emerald', 'amber', 'rose', 'purple', 'slate'].includes(v),
    },
    showBaseline: { type: Boolean, default: false },
});

const colors = {
    sky: '#38bdf8',
    emerald: '#34d399',
    amber: '#fbbf24',
    rose: '#fb7185',
    purple: '#a78bfa',
    slate: '#94a3b8',
};

const strokeColor = computed(() => colors[props.accent] ?? colors.sky);
const baselineColor = 'rgba(148, 163, 184, 0.2)';

const points = computed(() => {
    const v = props.values.filter((n) => typeof n === 'number' && !Number.isNaN(n));
    if (v.length < 2) return [];

    const min = Math.min(...v);
    const max = Math.max(...v);
    const range = max - min || 1;
    const stepX = props.width / (v.length - 1);

    return v.map((val, i) => {
        const x = i * stepX;
        const y = props.height - ((val - min) / range) * (props.height - 4) - 2;
        return `${x.toFixed(2)},${y.toFixed(2)}`;
    });
});

const lastPoint = computed(() => {
    const v = props.values.filter((n) => typeof n === 'number' && !Number.isNaN(n));
    if (v.length < 2) return null;

    const min = Math.min(...v);
    const max = Math.max(...v);
    const range = max - min || 1;
    const stepX = props.width / (v.length - 1);

    const val = v[v.length - 1];
    return {
        x: (v.length - 1) * stepX,
        y: props.height - ((val - min) / range) * (props.height - 4) - 2,
    };
});
</script>