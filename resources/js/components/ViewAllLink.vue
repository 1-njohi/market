<template>
    <Link
        v-if="count > 0"
        :href="href"
        class="font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
    >
        {{ label }}
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, required: true },
    count: { type: Number, default: 0 },
    /** Cap applied on the dashboard. When count > cap, the label includes the count. */
    cap: { type: Number, default: 0 },
});

const label = computed(() =>
    props.cap > 0 && props.count > props.cap
        ? `View all ${props.count} →`
        : 'View all →',
);
</script>