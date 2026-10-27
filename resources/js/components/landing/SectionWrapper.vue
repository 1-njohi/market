<template>
    <section :id="id" :class="wrapperClass">
        <!-- Ambient background layers -->
        <div v-if="variant !== 'default'" class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-32 left-1/4 h-96 w-96 rounded-full bg-sky-600/8 blur-[140px]"></div>
            <div class="absolute -bottom-32 right-1/4 h-96 w-96 rounded-full bg-amber-500/6 blur-[160px]"></div>
        </div>

        <div class="relative mx-auto w-full max-w-[1200px] px-4 md:px-6 lg:px-8">
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
            return 'relative w-full overflow-hidden bg-[#070f1c] py-2 lg:py-28';
        case 'hero':
            return 'relative w-full overflow-hidden bg-[#0a1628]';
        case 'final':
            return 'relative w-full overflow-hidden bg-[#0a1628] py-4 lg:py-20';
        default:
            return 'relative w-full bg-[#0a1628] py-2 lg:py-28';
    }
});
</script>