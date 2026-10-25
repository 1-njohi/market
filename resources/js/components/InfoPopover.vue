<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';

defineProps({
    title: {
        type: String,
        default: 'How this works',
    },
    // Optional aria/title text on the trigger button.
    label: {
        type: String,
        default: 'What is this?',
    },
    // Trigger size — 'sm' matches inline headings, 'md' matches buttons.
    size: {
        type: String,
        default: 'sm',
        validator: (v) => ['sm', 'md'].includes(v),
    },
});

const isOpen = ref(false);
const container = ref(null);
const modalElement = ref(null);

const open = () => (isOpen.value = true);
const close = () => (isOpen.value = false);

watch(isOpen, (v) => {
    document.body.classList.toggle('overflow-hidden', v);
});

const handleClickOutside = (e) => {
    if (
        isOpen.value &&
        modalElement.value &&
        !modalElement.value.contains(e.target) &&
        container.value &&
        !container.value.contains(e.target)
    ) {
        close();
    }
};

const handleKeydown = (e) => {
    if (e.key === 'Escape' && isOpen.value) close();
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});
onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <div ref="container" class="inline-flex">
        <!-- Trigger: small to look at, generous to tap -->
        <button
            type="button"
            @click.stop="open"
            :title="label"
            :aria-label="label"
            aria-haspopup="dialog"
            :class="[
                'relative flex cursor-pointer items-center justify-center rounded-full border border-white/10 bg-white/[0.05] text-slate-400 transition-all before:absolute before:-inset-2 before:content-[\'\'] hover:border-sky-400/50 hover:bg-sky-400/10 hover:text-sky-300 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none',
                size === 'sm' ? 'h-6 w-6' : 'h-8 w-8',
            ]"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" :stroke-width="size === 'sm' ? 2.5 : 2" stroke="currentColor" :class="size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4'" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
            </svg>
        </button>

        <Teleport to="body">
            <!-- Bottom sheet on phones, centered dialog on larger screens -->
            <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-end justify-center sm:items-center sm:p-4" @click.self="close">
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="close"></div>

                <div
                    ref="modalElement"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title"
                    class="animate-sheet relative flex max-h-[88svh] w-full flex-col overflow-hidden rounded-t-3xl border border-white/10 bg-[#0d1424] shadow-[0_-20px_60px_rgba(0,0,0,0.6)] sm:max-h-[85vh] sm:max-w-md sm:rounded-3xl"
                >
                    <!-- Header -->
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/[0.07] px-5 py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-sky-400/10 text-sky-300">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                                </svg>
                            </span>
                            <h2 class="text-base font-bold text-white">{{ title }}</h2>
                        </div>
                        <button
                            type="button"
                            @click="close"
                            aria-label="Close"
                            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Body: caller-provided, scrolls when long -->
                    <div class="space-y-3 overflow-y-auto px-5 py-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] font-sans text-sm leading-relaxed text-slate-300">
                        <slot />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes sheetIn {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-sheet {
    animation: sheetIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@media (prefers-reduced-motion: reduce) {
    .animate-sheet {
        animation: none;
    }
}
</style>