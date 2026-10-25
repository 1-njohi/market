<template>
    <section :id="id" :class="wrapperClass">
        <div class="mx-auto w-full max-w-[1200px] px-4 md:px-6 lg:px-8">
            <slot />
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: { type: String, default: null },
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'darker', 'hero', 'final'].includes(v),
    },
});

const wrapperClass = computed(() => {
    switch (props.variant) {
        case 'darker':
            return 'relative w-full bg-[#070f1c] py-20 lg:py-28';
        case 'hero':
            return 'relative w-full bg-[#0a1628] overflow-hidden';
        case 'final':
            return 'relative w-full bg-[#0a1628] overflow-hidden py-16 lg:py-20';
        default:
            return 'relative w-full bg-[#0a1628] py-20 lg:py-28';
    }
});
</script>