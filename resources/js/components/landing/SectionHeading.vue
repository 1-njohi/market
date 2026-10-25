<template>
    <div :class="align === 'center' ? 'text-center' : 'text-left'">
        <div
            v-if="eyebrow"
            :class="[
                'mb-4 inline-flex items-center gap-2 rounded-full border border-sky-500/20 bg-sky-500/[0.07] px-3 py-1',
                align === 'center' ? 'mx-auto' : '',
            ]"
        >
            <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span>
            <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                {{ eyebrow }}
            </span>
        </div>
        <h2
            class="text-3xl font-black tracking-tight text-white uppercase sm:text-4xl lg:text-5xl"
        >
            <slot name="title">{{ title }}</slot>
        </h2>
        <p
            v-if="subtitle || $slots.subtitle"
            class="mt-4 text-base leading-relaxed text-slate-400 sm:text-lg"
            :class="align === 'center' ? 'mx-auto max-w-2xl' : 'max-w-2xl'"
        >
            <slot name="subtitle">{{ subtitle }}</slot>
        </p>
    </div>
</template>

<script setup>
defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, default: '' },
    subtitle: { type: String, default: null },
    align: {
        type: String,
        default: 'center',
        validator: (v) => ['center', 'left'].includes(v),
    },
});
</script>