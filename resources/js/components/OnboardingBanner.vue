<template>
    <div class="relative overflow-hidden rounded border border-sky-500/30 bg-gradient-to-br from-sky-500/10 via-sky-500/5 to-transparent">
        <button
            type="button"
            @click="$emit('dismiss')"
            class="absolute top-3 right-3 z-10 cursor-pointer rounded border border-[#232d42] bg-[#070b14]/60 p-1 text-slate-500 transition-colors hover:border-slate-600 hover:text-slate-300"
            aria-label="Dismiss onboarding"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3 w-3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="p-5 md:p-6">
            <div class="flex flex-col gap-1.5">
                <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Start here
                </span>
                <h2 class="font-mono text-lg font-black tracking-tight text-white uppercase md:text-xl">
                    {{ headline }}
                </h2>
                <p class="max-w-2xl text-[11px] leading-relaxed text-slate-400">
                    {{ body }}
                </p>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="(step, i) in steps"
                    :key="i"
                    :href="step.href"
                    class="group flex items-start gap-3 rounded border border-[#232d42] bg-[#111622]/50 p-3 transition-all hover:border-sky-500/40 hover:bg-[#111622]"
                >
                    <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border border-sky-500/30 bg-sky-500/10 font-mono text-[10px] font-black text-sky-400">
                        {{ i + 1 }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-black tracking-wide text-white uppercase">
                            {{ step.title }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-slate-500">
                            {{ step.body }}
                        </p>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    headline: { type: String, required: true },
    body: { type: String, required: true },
    steps: { type: Array, required: true },
});

defineEmits(['dismiss']);
</script>