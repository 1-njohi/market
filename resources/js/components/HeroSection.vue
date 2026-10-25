<template>
    <SectionWrapper variant="hero">
        <!-- Layered ambient glows -->
        <div class="pointer-events-none absolute top-0 left-1/4 h-[28rem] w-[28rem] rounded-full bg-sky-600/15 blur-[140px]"></div>
        <div class="pointer-events-none absolute right-1/4 bottom-0 h-[28rem] w-[28rem] rounded-full bg-amber-500/10 blur-[160px]"></div>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(14,165,233,0.08),transparent_60%)]"></div>

        <div class="relative z-10 mx-auto w-full max-w-[1200px] px-2 py-20 text-center md:px-6 lg:px-8 lg:py-28">
            <!-- Pill -->
            <div class="mb-6 inline-flex items-center space-x-2 rounded-full border border-sky-500/30 bg-sky-500/10 px-3.5 py-1.5 shadow-[0_0_24px_-8px_rgba(14,165,233,0.5)]">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-400"></span>
                </span>
                <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Free Matchday Contests. No Stake.
                </span>
            </div>

            <!-- Headline -->
            <h1 class="mx-auto max-w-4xl text-4xl leading-[1.1] font-black tracking-tight text-white uppercase sm:text-5xl lg:text-6xl">
                Pick the winners.<br />
                Beat your friends.<br />
                <span class="bg-gradient-to-r from-sky-400 via-indigo-400 to-amber-400 bg-clip-text text-transparent drop-shadow-[0_0_30px_rgba(251,191,36,0.15)]">
                    Get approved. Then sell.
                </span>
            </h1>

            <!-- Subhead -->
            <p class="mx-auto mt-7 max-w-2xl text-sm leading-relaxed text-slate-400 sm:text-base">
                Every matchday, make your final call on every fixture with your crew. Free contests, live leaderboards, real bragging rights. Keep your accuracy above the threshold and you unlock the marketplace — where proven pickers publish their picks and buyers can follow them. Every result is
                <span class="font-semibold text-slate-200">verified after full time</span>, so nobody can fake a record.
            </p>

            <!-- CTAs -->
            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <Link
                    href="/matchdays"
                    class="group relative w-full overflow-hidden rounded-xl bg-amber-500 px-8 py-4 text-xs font-black tracking-widest text-black uppercase shadow-[0_8px_32px_-8px_rgba(251,191,36,0.6)] transition-all duration-200 hover:bg-amber-400 hover:shadow-[0_12px_40px_-8px_rgba(251,191,36,0.7)] active:scale-[0.98] sm:w-auto"
                >
                    <span class="relative z-10">Start Playing Free →</span>
                    <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-700 group-hover:translate-x-full"></span>
                </Link>
                <button
                    type="button"
                    @click="scrollToHowItWorks"
                    class="w-full cursor-pointer rounded-xl border border-[#232d42] bg-[#111622]/80 px-8 py-4 text-center text-xs font-black tracking-widest text-slate-300 uppercase backdrop-blur-sm transition-all duration-200 hover:border-sky-500/40 hover:bg-[#111622] hover:text-white active:scale-[0.98] sm:w-auto"
                >
                    See how it works →
                </button>
            </div>

            <!-- Trust line -->
            <p class="mt-7 text-[10px] font-bold tracking-widest text-slate-600 uppercase">
                No stake. No entry fee. Just your record.
            </p>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import SectionWrapper from './landing/SectionWrapper.vue';

const scrollToHowItWorks = () => {
    document
        .getElementById('how-it-works')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>