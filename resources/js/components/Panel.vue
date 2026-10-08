<template>
    <div
        :class="[
            'w-full overflow-hidden rounded border font-sans text-slate-200',
            borderClass,
        ]"
    >
        <div
            v-if="title || $slots.actions || $slots.header"
            :class="[
                'flex flex-col gap-2 border-b p-4 sm:flex-row sm:items-center sm:justify-between',
                headerClass,
            ]"
        >
            <slot name="header">
                <div class="flex items-center gap-2">
                    <span
                        :class="[
                            'text-[10px] font-black tracking-widest uppercase',
                            accentTextClass,
                        ]"
                    >
                        {{ title }}
                    </span>
                    <slot name="title-suffix" />
                </div>
            </slot>

            <div
                v-if="$slots.actions"
                class="flex flex-wrap items-center gap-2 sm:justify-end"
            >
                <slot name="actions" />
            </div>
        </div>

        <slot />
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    /**
     * Accent color for the title text and (optionally) the border.
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
    sky: {
        text: 'text-sky-400',
        border: 'border-gray-800/60',
        header: 'border-gray-800/60 bg-[#111a30]',
    },
    emerald: {
        text: 'text-emerald-400',
        border: 'border-gray-800/60',
        header: 'border-gray-800/60 bg-[#111a30]',
    },
    amber: {
        text: 'text-amber-400',
        border: 'border-gray-800/60',
        header: 'border-gray-800/60 bg-[#111a30]',
    },
    rose: {
        text: 'text-rose-400',
        border: 'border-gray-800/60',
        header: 'border-gray-800/60 bg-[#111a30]',
    },
    purple: {
        text: 'text-purple-400',
        border: 'border-gray-800/60',
        header: 'border-gray-800/60 bg-[#111a30]',
    },
    slate: {
        text: 'text-slate-300',
        border: 'border-gray-800/60',
        header: 'border-gray-800/60 bg-[#111a30]',
    },
};

const palette = computed(() => accents[props.accent] ?? accents.slate);
const accentTextClass = computed(() => palette.value.text);
const borderClass = computed(
    () => `${palette.value.border} bg-[#111622]/40`,
);
const headerClass = computed(() => palette.value.header);
</script>