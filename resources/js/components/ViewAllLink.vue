<template>
    <Link
        :href="href"
        class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-bold text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
    >
        {{ label }}
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, required: true },
    /**
     * Optional. When provided with `cap` and count > cap, the label
     * includes the total ("View all 47 →"). Otherwise it's just "View all →".
     */
    count: { type: Number, default: 0 },
    cap: { type: Number, default: 0 },
});

const label = computed(() =>
    props.cap > 0 && props.count > props.cap
        ? `View all ${props.count} →`
        : 'View all →',
);
</script>