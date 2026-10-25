<template>
    <SectionWrapper variant="final">
        <!-- Ambient glow -->
        <div
            class="pointer-events-none absolute top-1/2 left-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-sky-600/10 blur-[120px]"
        ></div>

        <div class="relative z-10 text-center">
            <h2
                class="text-3xl font-black tracking-tight text-white uppercase sm:text-4xl lg:text-5xl"
            >
                Make your final call.
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-sm text-slate-400 sm:text-base">
                Free contests. No stake. A record that means something.
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Link
                    href="/fixtures"
                    class="w-full rounded-xl bg-amber-500 px-8 py-4 text-xs font-bold tracking-wider text-black uppercase shadow-xl shadow-amber-500/20 transition-all hover:bg-amber-400 active:scale-[0.98] sm:w-auto"
                >
                    Start playing free →
                </Link>
            </div>

            <Link
                href="/marketplace"
                class="mt-4 inline-block text-[10px] font-bold tracking-widest text-slate-500 uppercase transition-colors hover:text-sky-400"
            >
                Browse the marketplace →
            </Link>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import SectionWrapper from './SectionWrapper.vue';
</script>