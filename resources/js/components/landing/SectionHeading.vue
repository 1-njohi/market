<template>
    <div :class="align === 'center' ? 'text-center' : 'text-left'">
        <p
            v-if="eyebrow"
            class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase"
        >
            {{ eyebrow }}
        </p>
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