<script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    code: { type: String, required: true },
    watching: { type: Boolean, default: false },
});

const isWatching = computed(() => props.watching);

const toggle = () => {
    if (isWatching.value) {
        router.delete(`/betslip/${props.code}/watch`, {
            preserveScroll: true,
        });
    } else {
        router.post(
            `/betslip/${props.code}/watch`,
            {},
            { preserveScroll: true },
        );
    }
};
</script>

<template>
    <button
        type="button"
        @click="toggle"
        :class="[
            'inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-[10px] font-black tracking-wider uppercase transition-all active:scale-95',
            isWatching
                ? 'border-amber-500/50 bg-amber-500/10 text-amber-400 hover:bg-amber-500/20'
                : 'border-[#232d42] bg-[#111622]/60 text-slate-400 hover:border-amber-500/40 hover:text-amber-400',
        ]"
        :title="isWatching ? 'Stop watching' : 'Watch this betslip'"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            :fill="isWatching ? 'currentColor' : 'none'"
            viewBox="0 0 24 24"
            stroke-width="2.5"
            stroke="currentColor"
            class="h-3 w-3"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"
            />
        </svg>
        {{ isWatching ? 'Watching' : 'Watch' }}
    </button>
</template>
