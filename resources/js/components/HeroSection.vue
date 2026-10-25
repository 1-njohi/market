<template>
    <SectionWrapper variant="hero">
        <!-- Half-pitch arc: a quiet nod to the game, no imagery needed -->
        <svg
            class="pointer-events-none absolute -right-40 bottom-0 h-[130%] w-auto text-white/[0.05]"
            viewBox="0 0 600 800"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <circle cx="600" cy="400" r="260" />
            <circle cx="600" cy="400" r="6" fill="currentColor" />
            <rect x="380" y="230" width="240" height="340" />
        </svg>

        <div class="pointer-events-none absolute -top-32 left-[5%] h-[32rem] w-[32rem] rounded-full bg-sky-600/20 blur-[160px]"></div>
        <div class="pointer-events-none absolute right-[8%] -bottom-32 h-[28rem] w-[28rem] rounded-full bg-amber-500/15 blur-[170px]"></div>

        <div class="relative z-10 mx-auto w-full max-w-[1200px] px-4 py-20 md:px-6 lg:px-8 lg:py-28 pt-24">
            <!-- Pill -->
            <div class="mb-8 inline-flex items-center gap-2.5 rounded-full border border-sky-400/25 bg-sky-400/10 py-1.5 pr-4 pl-3 backdrop-blur">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-70 motion-reduce:animate-none"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-400"></span>
                </span>
                <span class="text-xs font-semibold tracking-wide text-sky-300">Free matchday contests. No stake.</span>
            </div>

            <h1 class="text-[2.85rem] leading-[0.99] font-black tracking-[-0.065em] text-white sm:text-6xl lg:text-[4.6rem] xl:text-[5rem]">
                        EVERY MATCH.
                        <br />
                        EVERY PICK.
                        <br />
                        <span class="bg-gradient-to-r from-sky-400 via-blue-400 to-indigo-400 bg-clip-text text-transparent">
                            PROVE YOUR
                        </span>
                        <br />
                        <span class="text-amber-400">GAME.</span>
                    </h1>

                    <!-- Supporting copy -->
                    <p class="mt-7 max-w-xl text-[15px] leading-7 text-slate-400 sm:text-base sm:leading-8">
                        Call every fixture. Outscore the crowd. Build a verified
                        record of your football knowledge. Hit the accuracy
                        threshold and unlock the marketplace, where proven
                        pickers can publish their picks for buyers to follow.
                    </p>

            <div class="mt-10 grid items-end gap-10 lg:grid-cols-[1fr_auto]">
               <!-- CTAs -->
                <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <Link
                        href="/matchdays"
                        class="group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl bg-amber-400 px-8 py-4 text-sm font-black tracking-wide text-black shadow-[0_10px_40px_-10px_rgba(251,191,36,0.75)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-amber-300 hover:shadow-[0_16px_48px_-10px_rgba(251,191,36,0.85)] focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b0f19] focus-visible:outline-none active:scale-[0.98]"
                    >
                        <span class="relative z-10">Start playing free</span>
                        <span class="relative z-10 transition-transform duration-200 group-hover:translate-x-1">→</span>
                        <span
                            class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/50 to-transparent transition-transform duration-700 group-hover:translate-x-full motion-reduce:hidden"
                        ></span>
                    </Link>
                    <button
                        type="button"
                        @click="scrollToHowItWorks"
                        class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] px-8 py-4 text-sm font-bold tracking-wide text-slate-200 backdrop-blur transition-all duration-200 hover:border-sky-400/40 hover:bg-white/[0.08] hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none active:scale-[0.98]"
                    >
                        See how it works
                    </button>
                </div>
            </div>

            <!-- The path: free contests -> verified record -> marketplace. Draws once on load. -->
            <ol class="relative mt-20 grid gap-10 md:grid-cols-3 md:gap-8">
                <!-- vertical line (mobile) -->
                <div class="absolute top-3 bottom-3 left-[7px] w-px md:hidden" aria-hidden="true">
                    <div
                        class="h-full origin-top bg-gradient-to-b from-sky-400 via-indigo-400 to-amber-300 transition-transform duration-[1600ms] ease-out motion-reduce:transition-none"
                        :class="ready ? 'scale-y-100' : 'scale-y-0'"
                    ></div>
                </div>
                <!-- horizontal line (desktop) -->
                <div class="absolute top-[7px] right-0 left-0 hidden h-px md:block" aria-hidden="true">
                    <div
                        class="h-full origin-left bg-gradient-to-r from-sky-400 via-indigo-400 to-amber-300 transition-transform duration-[1600ms] ease-out motion-reduce:transition-none"
                        :class="ready ? 'scale-x-100' : 'scale-x-0'"
                    ></div>
                </div>

                <li v-for="(s, i) in stages" :key="s.title" class="relative pl-8 md:pt-8 md:pl-0">
                    <span
                        class="absolute top-0 left-0 h-[15px] w-[15px] rounded-full border-2 bg-[#0b0f19] transition-all duration-500 motion-reduce:transition-none"
                        :class="[s.dot, ready ? 'scale-100 opacity-100' : 'scale-50 opacity-0']"
                        :style="{ transitionDelay: ready ? i * 650 + 'ms' : '0ms' }"
                    ></span>
                    <h2 class="text-base font-bold text-white">{{ s.title }}</h2>
                    <p class="mt-1.5 max-w-xs text-sm leading-relaxed text-slate-400">{{ s.body }}</p>
                </li>
            </ol>

            <p class="mt-14 text-sm font-medium text-slate-500">No stake. No entry fee. Just your record.</p>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import SectionWrapper from './landing/SectionWrapper.vue';

const scrollToHowItWorks = () => {
    document
        .getElementById('how-it-works')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const stages = [
    {
        title: 'Free contests',
        body: 'Call every fixture, every matchday, with your crew. Live leaderboards, real bragging rights.',
        dot: 'border-sky-400 shadow-[0_0_14px_rgba(56,189,248,0.7)]',
    },
    {
        title: 'Verified record',
        body: 'Results are checked after full time. Keep your accuracy above the threshold to get approved.',
        dot: 'border-indigo-400 shadow-[0_0_14px_rgba(129,140,248,0.7)]',
    },
    {
        title: 'Marketplace',
        body: 'Proven pickers publish their picks. Buyers can follow them.',
        dot: 'border-amber-300 shadow-[0_0_18px_rgba(252,211,77,0.8)]',
    },
];

const ready = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        ready.value = true;
    });
});
</script>