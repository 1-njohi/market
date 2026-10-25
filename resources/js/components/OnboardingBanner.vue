<template>
    <section class="relative overflow-hidden rounded-3xl border border-sky-400/25 bg-gradient-to-br from-sky-500/15 via-[#0b1220] to-[#0b1220]">
        <div class="pointer-events-none absolute -top-20 -left-16 h-52 w-52 rounded-full bg-sky-400/15 blur-3xl" aria-hidden="true"></div>

        <button
            type="button"
            @click="$emit('dismiss')"
            class="absolute top-2 right-2 z-10 flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
            aria-label="Dismiss onboarding"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="relative p-5 md:p-6">
            <div class="flex flex-col gap-1.5 pr-10">
                <span class="text-sm font-semibold text-sky-300">Start here</span>
                <h2 class="text-xl font-black tracking-tight text-white md:text-2xl">{{ headline }}</h2>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-400">{{ body }}</p>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="(step, i) in steps"
                    :key="i"
                    :href="step.href"
                    class="group flex min-h-16 items-start gap-3 rounded-2xl border border-white/[0.08] bg-white/[0.04] p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-sky-400/40 hover:bg-white/[0.07] focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none active:scale-[0.98]"
                >
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-400 text-sm font-black text-[#070b14] tabular-nums">
                        {{ i + 1 }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white">{{ step.title }}</p>
                        <p class="mt-0.5 text-sm leading-snug text-slate-400">{{ step.body }}</p>
                    </div>
                </Link>
            </div>
        </div>
    </section>
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