<template>
    <SectionWrapper variant="hero">
        <div class="pointer-events-none absolute top-1/3 left-1/2 h-[26rem] w-[26rem] -translate-x-1/2 rounded-full bg-emerald-400/20 blur-[130px]"
            aria-hidden="true"></div>

        <div
            class="relative z-10 mx-auto flex w-full max-w-[1200px] flex-col gap-8 pt-24 pb-[max(2rem,env(safe-area-inset-bottom))] md:gap-10 md:px-6 md:pt-32 md:pb-24 lg:px-8">
            <!-- Copy -->
            <div class="order-1 max-w-3xl">
                <p
                    class="mb-5 inline-flex items-center gap-2 rounded-full bg-emerald-300/10 px-3.5 py-1.5 text-xs font-semibold text-amber-200 ring-1 ring-amber-300/25">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                    No stake. No entry fee.
                </p>

                <h1
                    class="text-[3.25rem] leading-[0.95] font-black tracking-[-0.05em] text-white sm:text-7xl lg:text-8xl">
                    Call it.<br />
                    Prove it.<br />
                    <span class="text-amber-500">Sell it.</span>
                </h1>

                <p class="mt-6 max-w-xl text-base leading-7 text-slate-300 md:text-lg md:leading-8">
                    Every pick is timestamped and verified after full time. Free to start. As your record builds,
                    patterns emerge: your best leagues, your blind spots, the calls you keep nailing. That's your proof.
                    Cross the threshold and sell it. Publish your picks, build a following, get paid.
                </p>
            </div>

            <!-- The tactics board: four ways to build a record, then the verified record, then the goal -->
            <div class="order-2 md:order-3">
                <p class="mb-3 text-sm font-medium text-slate-400">Four ways to build your record. One way to sell it.
                </p>

                <div
                    class="relative h-[46rem] overflow-hidden rounded-3xl border-2 border-white/30 bg-gradient-to-b from-[#0f5c38] to-[#093d27] shadow-[0_30px_80px_-30px_rgba(16,185,129,0.5)] md:h-[26rem]">
                    <!-- Mown stripes -->
                    <div class="absolute inset-0 bg-[repeating-linear-gradient(180deg,rgba(255,255,255,0.05)_0_52px,transparent_52px_104px)] md:bg-[repeating-linear-gradient(90deg,rgba(255,255,255,0.05)_0_72px,transparent_72px_144px)]"
                        aria-hidden="true"></div>

                    <!-- Chalk markings (portrait on phones, landscape on larger screens) -->
                    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                        <div
                            class="absolute top-1/2 right-0 left-0 h-px bg-white/30 md:top-0 md:right-auto md:bottom-0 md:left-1/2 md:h-auto md:w-px">
                        </div>
                        <div
                            class="absolute top-1/2 left-1/2 aspect-square w-28 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/30 md:w-40">
                        </div>
                        <div
                            class="absolute right-[22%] bottom-0 left-[22%] h-[20%] border border-b-0 border-white/30 md:top-[22%] md:right-0 md:bottom-auto md:left-auto md:h-[56%] md:w-[14%] md:border-r-0 md:border-b">
                        </div>
                        <div
                            class="absolute right-[36%] bottom-0 left-[36%] h-[8%] border border-b-0 border-white/30 md:top-[38%] md:right-0 md:bottom-auto md:left-auto md:h-[24%] md:w-[6%] md:border-r-0 md:border-b">
                        </div>
                    </div>

                    <!-- Play lines: portrait -->
                    <svg class="pointer-events-none absolute inset-0 h-full w-full text-white md:hidden"
                        viewBox="0 0 100 100" preserveAspectRatio="none" fill="none" aria-hidden="true">
                        <path v-for="(s, i) in segments" :key="'m' + i" :d="s.m" stroke="currentColor"
                            stroke-width="2.5" stroke-dasharray="7 9" stroke-linecap="round"
                            vector-effect="non-scaling-stroke"
                            class="transition-opacity duration-700 motion-reduce:transition-none"
                            :class="step >= i + 2 ? 'opacity-70' : 'opacity-0'" />
                    </svg>
                    <!-- Play lines: landscape -->
                    <svg class="pointer-events-none absolute inset-0 hidden h-full w-full text-white md:block"
                        viewBox="0 0 100 100" preserveAspectRatio="none" fill="none" aria-hidden="true">
                        <path v-for="(s, i) in segments" :key="'d' + i" :d="s.d" stroke="currentColor"
                            stroke-width="2.5" stroke-dasharray="7 9" stroke-linecap="round"
                            vector-effect="non-scaling-stroke"
                            class="transition-opacity duration-700 motion-reduce:transition-none"
                            :class="step >= i + 2 ? 'opacity-70' : 'opacity-0'" />
                    </svg>

                    <!-- The touches -->
                    <ol>
                        <li v-for="(n, i) in nodes" :key="n.title"
                            class="absolute left-[var(--mx)] top-[var(--my)] -translate-x-1/2 -translate-y-1/2 transition-all duration-500 md:left-[var(--dx)] md:top-[var(--dy)] motion-reduce:transition-none"
                            :class="step > i ? 'scale-100 opacity-100' : 'scale-75 opacity-0'" :style="n.vars">
                            <span class="block h-5 w-5 rounded-full border-2" :class="n.goal
                                ? 'border-amber-200 bg-amber-500 shadow-[0_0_0_6px_rgba(190,242,100,0.25),0_0_30px_rgba(190,242,100,0.7)]'
                                : n.verify
                                    ? 'border-amber-300 bg-[#0b4a2e] shadow-[0_0_0_5px_rgba(190,242,100,0.2)]'
                                    : 'border-white bg-[#0b4a2e] shadow-[0_0_0_5px_rgba(255,255,255,0.12)]'
                                "></span>
                            <div class="absolute w-[10.5rem] rounded-xl bg-black/40 px-3 py-2 backdrop-blur-sm md:w-52"
                                :class="n.label">
                                <p class="text-sm leading-snug font-bold"
                                    :class="n.goal ? 'text-amber-500' : n.verify ? 'text-amber-300' : 'text-white'">{{
                                        n.title }}</p>
                                <p class="mt-0.5 text-xs leading-snug text-white/70">{{ n.body }}</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>

            <!-- CTAs: last on phones, in the thumb zone -->
            <div class="order-3 md:order-2">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <Link href="/matchdays"
                        class="w-full rounded-xl bg-amber-500 px-8 py-4 text-xs font-bold text-center tracking-wider text-black uppercase shadow-xl shadow-amber-500/20 transition-all hover:bg-amber-400 active:scale-[0.98] sm:w-auto">
                        Start playing free →
                    </Link>
                    <button type="button" @click="scrollToHowItWorks"
                        class="inline-flex min-h-14 cursor-pointer items-center justify-center rounded-2xl border border-white/15 px-8 text-base font-bold text-slate-200 transition-all duration-200 hover:border-amber-300/50 hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-[0.98]">
                        See how it works
                    </button>
                </div>
                <p class="mt-4 text-center text-sm text-slate-500 sm:text-left">Just your record.</p>
            </div>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import SectionWrapper from './landing/SectionWrapper.vue';

const scrollToHowItWorks = () => {
    document
        .getElementById('how-it-works')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

// Positions are percentages of the pitch.
// --m*: phones, portrait pitch, the play zigzags downward.
// --d*: larger screens, landscape pitch, the play zigzags rightward.
// Label classes: phones alternate right/left of the dot; larger screens alternate above/below.
const rightOfDot = 'top-1/2 left-full ml-3 -translate-y-1/2';
const leftOfDot = 'top-1/2 right-full mr-3 -translate-y-1/2';
const aboveDot = 'md:top-auto md:bottom-full md:mb-4 md:left-1/2 md:right-auto md:mr-0 md:ml-0 md:-translate-x-1/2 md:translate-y-0';
const belowDot = 'md:top-full md:bottom-auto md:mt-4 md:left-1/2 md:right-auto md:mr-0 md:ml-0 md:-translate-x-1/2 md:translate-y-0';

const nodes = [
    {
        title: 'Join a matchday',
        body: 'Call every fixture, free.',
        vars: { '--mx': '22%', '--my': '7%', '--dx': '8%', '--dy': '30%' },
        label: `${rightOfDot} md:top-auto md:bottom-full md:mb-4 md:left-0 md:ml-0 md:translate-y-0`,
    },
    {
        title: 'Create and compete in contests',
        body: 'Start your own or join one.',
        vars: { '--mx': '74%', '--my': '22%', '--dx': '24%', '--dy': '70%' },
        label: `${leftOfDot} ${belowDot}`,
    },
    {
        title: 'Take part in jackpots',
        body: 'Pick 7–20 legs against the crowd.',
        vars: { '--mx': '22%', '--my': '38%', '--dx': '40%', '--dy': '30%' },
        label: `${rightOfDot} ${aboveDot}`,
    },
    {
        title: 'Create betslips',
        body: 'Never listed. Still on your record.',
        vars: { '--mx': '74%', '--my': '54%', '--dx': '56%', '--dy': '70%' },
        label: `${leftOfDot} ${belowDot}`,
    },
    {
        title: 'Verified record',
        body: 'Every result checked after full time.',
        verify: true,
        vars: { '--mx': '22%', '--my': '70%', '--dx': '72%', '--dy': '30%' },
        label: `${rightOfDot} ${aboveDot}`,
    },
    {
        title: 'Sell your picks',
        body: 'Hit the accuracy threshold to unlock.',
        goal: true,
        vars: { '--mx': '38%', '--my': '90%', '--dx': '91%', '--dy': '55%' },
        label: 'bottom-full left-full mb-2 ml-3 md:top-full md:bottom-auto md:right-0 md:left-auto md:mt-4 md:mb-0 md:ml-0',
    },
];

// One curve between each pair of touches (same order as `nodes`).
const segments = [
    { m: 'M22 7 C 22 14.5, 74 14.5, 74 22', d: 'M8 30 C 16 30, 16 70, 24 70' },
    { m: 'M74 22 C 74 30, 22 30, 22 38', d: 'M24 70 C 32 70, 32 30, 40 30' },
    { m: 'M22 38 C 22 46, 74 46, 74 54', d: 'M40 30 C 48 30, 48 70, 56 70' },
    { m: 'M74 54 C 74 62, 22 62, 22 70', d: 'M56 70 C 64 70, 64 30, 72 30' },
    { m: 'M22 70 C 22 80, 38 80, 38 90', d: 'M72 30 C 82 30, 82 55, 91 55' },
];

const step = ref(0);
let timer;

onMounted(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        step.value = nodes.length;
        return;
    }
    // One orchestrated moment: the play unfolds once, touch by touch, ending in the goal.
    timer = setTimeout(() => {
        timer = setInterval(() => {
            step.value++;
            if (step.value >= nodes.length) clearInterval(timer);
        }, 520);
    }, 300);
});

onBeforeUnmount(() => {
    clearTimeout(timer);
    clearInterval(timer);
});
</script>