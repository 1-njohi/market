<template>
    <section class="w-full overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220] font-sans text-slate-200">
        <div
            v-if="title || $slots.actions || $slots.header"
            class="flex flex-col gap-2 border-b border-white/[0.06] px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <slot name="header">
                <div class="flex items-center gap-2.5">
                    <span :class="['h-2 w-2 shrink-0 rounded-full', palette.dot]" aria-hidden="true"></span>
                    <h2 :class="['text-base font-bold', palette.text]">{{ displayTitle }}</h2>
                    <slot name="title-suffix" />
                </div>
            </slot>

            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 sm:justify-end">
                <slot name="actions" />
            </div>
        </div>

        <slot />
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    /**
     * Accent colour for the title and its marker dot.
     * Matches the dashboard's existing palette.
     */
    accent: {
        type: String,
        default: 'slate',
        validator: (v) =>
            ['sky', 'emerald', 'amber', 'rose', 'purple', 'slate'].includes(v),
    },
});

const accents = {
    sky: { text: 'text-sky-300', dot: 'bg-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.7)]' },
    emerald: { text: 'text-emerald-300', dot: 'bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.7)]' },
    amber: { text: 'text-amber-300', dot: 'bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.7)]' },
    rose: { text: 'text-rose-300', dot: 'bg-rose-400 shadow-[0_0_10px_rgba(251,113,133,0.7)]' },
    purple: { text: 'text-purple-300', dot: 'bg-purple-400 shadow-[0_0_10px_rgba(192,132,252,0.7)]' },
    slate: { text: 'text-slate-200', dot: 'bg-slate-400' },
};

const palette = computed(() => accents[props.accent] ?? accents.slate);

// Titles passed in ALL CAPS (e.g. "WATCH RECORD") read as "Watch record".
// Mixed-case titles (e.g. "Active Purchases [3]") are left exactly as given.
const displayTitle = computed(() => {
    const t = props.title;
    if (/[A-Z]/.test(t) && !/[a-z]/.test(t)) {
        const lower = t.toLowerCase();
        return lower.charAt(0).toUpperCase() + lower.slice(1);
    }
    return t;
});
</script>