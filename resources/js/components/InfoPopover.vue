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

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <div ref="container" class="inline-flex">
        <!-- Trigger -->
        <button
            type="button"
            @click.stop="open"
            :title="label"
            :aria-label="label"
            :class="[
                'flex cursor-pointer items-center justify-center rounded-full border border-[#232d42] bg-[#111622] text-slate-500 transition-all hover:border-sky-500/50 hover:bg-sky-500/10 hover:text-sky-400',
                size === 'sm' ? 'h-5 w-5' : 'h-7 w-7',
            ]"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                :stroke-width="size === 'sm' ? 2.5 : 2"
                stroke="currentColor"
                :class="size === 'sm' ? 'h-3 w-3' : 'h-4 w-4'"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"
                />
            </svg>
        </button>

        <Teleport to="body">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="close"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                ></div>

                <!-- Modal -->
                <div
                    ref="modalElement"
                    class="animate-fade-in relative w-full max-w-md rounded border border-gray-800 bg-[#0f1422] p-4 shadow-[0_20px_50px_rgba(7,11,20,0.9),0_0_25px_rgba(14,165,233,0.15)] sm:w-96"
                >
                    <!-- Header -->
                    <div
                        class="mb-3 flex items-center justify-between border-b border-gray-800 pb-2"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="flex h-5 w-5 items-center justify-center rounded-full border border-sky-500/40 bg-sky-500/10 text-sky-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-3 w-3"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"
                                    />
                                </svg>
                            </span>
                            <span
                                class="text-[9px] font-black tracking-widest text-sky-400 uppercase"
                            >
                                {{ title }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="close"
                            class="cursor-pointer font-mono text-xs text-slate-500 hover:text-slate-300"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Body — caller-provided -->
                    <div
                        class="space-y-2.5 font-sans text-xs leading-relaxed text-slate-300"
                    >
                        <slot />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.animate-fade-in {
    animation: fadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
