<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import ContestPicksCard from './ContestPicksCard.vue';

const props = defineProps({
    legs: { type: Array, required: true },
    canSubmit: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false },
    minLegs: { type: Number, default: 5 },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['review-picks', 'remove-leg']);

const isOpen = ref(false);

const open = () => {
    isOpen.value = true;
};

onMounted(() => {
    window.addEventListener('contestPicksOpened', open);
});

onBeforeUnmount(() => {
    window.removeEventListener('contestPicksOpened', open);
});

// If the sheet is open and the user removes every leg, close it.
// Prevents an empty overlay from lingering.
watch(
    () => props.legs.length,
    (n) => {
        if (n === 0) isOpen.value = false;
    },
);
</script>

<template>
    <div>
        <!-- Floating trigger (mobile + desktop) -->
        <button
            v-if="!isOpen && legs.length > 0"
            type="button"
            @click="isOpen = true"
            class="fixed right-4 bottom-6 z-40 flex cursor-pointer items-center gap-2 rounded-full bg-[#ff8c00] px-5 py-3 text-xs font-black tracking-wider text-black uppercase shadow-2xl transition-transform hover:scale-105 active:scale-95"
        >
            <span>⚡ Picks</span>
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-black text-[10px] font-black text-[#ff8c00]">
                {{ legs.length }}
            </span>
        </button>

        <!-- Bottom sheet -->
        <section
            v-if="isOpen"
            class="fixed inset-x-0 bottom-0 z-50 mx-auto w-full max-w-2xl overflow-hidden rounded-t-xl border border-[#232d42] bg-[#0d1527] shadow-2xl md:bottom-4 md:rounded-lg"
        >
            <button
                type="button"
                @click="isOpen = false"
                class="absolute top-3 right-3 z-10 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-[#232d42] bg-[#161c2a] text-slate-400 transition-colors hover:border-sky-500/50 hover:text-slate-200"
                aria-label="Close"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>

            <ContestPicksCard
                :legs="legs"
                :can-submit="canSubmit"
                :submitting="submitting"
                :min-legs="minLegs"
                :errors="errors"
                @review-picks="emit('review-picks')"
                @remove-leg="emit('remove-leg', $event)"
            />
        </section>
    </div>
</template>