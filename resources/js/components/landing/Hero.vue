<template>
    <SectionWrapper variant="hero">
        <!-- Ambient Glows -->
        <div
            class="pointer-events-none absolute top-0 left-1/4 h-96 w-96 rounded-full bg-sky-600/10 blur-[120px]"
        ></div>
        <div
            class="pointer-events-none absolute right-1/4 bottom-0 h-96 w-96 rounded-full bg-amber-500/5 blur-[140px]"
        ></div>

        <div
            class="relative z-10 mx-auto w-full max-w-[1200px] px-2 py-16 text-center md:px-6 lg:px-8 lg:py-24"
        >
            <!-- Pill -->
            <div
                class="mb-6 inline-flex items-center space-x-2 rounded-full border border-sky-500/20 bg-sky-500/10 px-3 py-1"
            >
                <span
                    class="h-1.5 w-1.5 animate-pulse rounded-full bg-sky-400"
                ></span>
                <span
                    class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                >
                    Free Matchday Contests. No Stake.
                </span>
            </div>

            <!-- Headline -->
            <h1
                class="mx-auto max-w-4xl text-4xl leading-[1.15] font-black tracking-tight text-white uppercase sm:text-5xl lg:text-6xl"
            >
                Pick the winners.<br />
                Beat your friends.<br />
                <span
                    class="bg-gradient-to-r from-sky-500 via-indigo-400 to-amber-400 bg-clip-text text-transparent"
                >
                    Get approved. Then sell.
                </span>
            </h1>

            <!-- Subhead -->
            <p
                class="mx-auto mt-6 max-w-2xl text-sm leading-relaxed text-slate-400 sm:text-base"
            >
                Every matchday, make your final call on every fixture with
                your crew. Free contests, live leaderboards, real bragging
                rights. Keep your accuracy above the threshold and you
                unlock the marketplace — where proven pickers publish their
                picks and buyers can follow them. Every result is
                <span class="font-semibold text-slate-200"
                    >verified after full time</span
                >, so nobody can fake a record.
            </p>

            <!-- CTAs -->
            <div
                class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row"
            >
                <Link
                    href="/matchdays"
                    class="w-full rounded-xl bg-amber-500 px-8 py-4 text-xs font-bold tracking-wider text-black uppercase shadow-xl shadow-amber-500/20 transition-all hover:bg-amber-400 active:scale-[0.98] sm:w-auto"
                >
                    Start Playing Free →
                </Link>
                <button
                    type="button"
                    @click="scrollToHowItWorks"
                    class="w-full cursor-pointer rounded-xl border border-[#232d42] bg-[#111622] px-8 py-4 text-center text-xs font-bold tracking-wider text-slate-300 uppercase transition-all hover:border-sky-500/40 hover:text-white sm:w-auto"
                >
                    See how it works →
                </button>
            </div>

            <!-- Trust line -->
            <p
                class="mt-6 text-[10px] font-bold tracking-widest text-slate-600 uppercase"
            >
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