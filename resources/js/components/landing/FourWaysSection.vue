<template>
    <SectionWrapper>
        <SectionHeading
            eyebrow="How you build it"
            title="Four ways to prove you know football."
            subtitle="Every pick counts. Choose your path."
        />

        <!-- Bento: one lead path, three supporting ones, not four identical tiles -->
        <div class="mt-14 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 lg:grid-rows-[auto_auto] lg:gap-5">
            <Link
                v-for="card in cards"
                :key="card.title"
                :href="card.href"
                class="group relative flex flex-col overflow-hidden rounded-3xl border border-white/[0.08] bg-[#0f1626] p-7 transition-all duration-300 hover:border-sky-400/40 hover:shadow-[0_24px_60px_-24px_rgba(14,165,233,0.45)] focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
                :class="card.layout"
            >
                <!-- Lead card: half-pitch backdrop that echoes the hero -->
                <svg
                    v-if="card.lead"
                    class="pointer-events-none absolute -right-24 -bottom-16 h-[115%] w-auto text-white/[0.06] transition-colors duration-500 group-hover:text-sky-300/[0.12]"
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
                <div
                    v-if="card.lead"
                    class="pointer-events-none absolute -top-20 -left-20 h-64 w-64 rounded-full bg-sky-500/15 blur-[90px]"
                    aria-hidden="true"
                ></div>

                <div class="relative flex h-11 w-11 items-center justify-center rounded-xl bg-white/[0.05] text-sky-300 ring-1 ring-white/10 transition-colors duration-300 group-hover:bg-sky-400/15 group-hover:text-sky-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                    </svg>
                </div>

                <h3
                    class="relative mt-6 font-black tracking-tight text-white"
                    :class="card.lead ? 'text-3xl sm:text-4xl lg:mt-auto lg:pt-32' : 'text-xl'"
                >
                    {{ card.title }}
                </h3>
                <p class="relative mt-3 max-w-md text-sm leading-relaxed text-slate-400" :class="card.lead && 'sm:text-base'">
                    {{ card.body }}
                </p>

                <!-- Jackpots: 7 to 20 legs, drawn from the copy itself -->
                <div v-if="card.legs" class="relative mt-6 flex flex-wrap gap-1.5" aria-hidden="true">
                    <span
                        v-for="n in 20"
                        :key="n"
                        class="h-2.5 w-2.5 rounded-full transition-colors duration-300"
                        :class="n <= 7 ? 'bg-amber-300 shadow-[0_0_10px_rgba(252,211,77,0.6)]' : 'bg-white/15 group-hover:bg-amber-300/40'"
                    ></span>
                </div>

                <span class="relative mt-6 inline-flex items-center gap-2 text-sm font-bold text-amber-300 transition-all duration-200 group-hover:gap-3 group-hover:text-amber-200">
                    {{ card.cta }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </span>
            </Link>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';

const cards = [
    {
        title: 'Matchday contests',
        body: 'Free weekly contests with your crew. Pick every fixture. Climb the leaderboard.',
        cta: 'Join a sub-league',
        href: '/matchdays',
        lead: true,
        layout: 'lg:row-span-2 min-h-[22rem]',
        icon: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
    },
    {
        title: 'Log your bets',
        body: 'Track the bets you place elsewhere. We auto-score them against real results.',
        cta: 'Log a bet',
        href: '/tracker/log',
        layout: '',
        icon: 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
    },
    {
        title: 'One-off challenges',
        body: 'Challenge a friend. Any match, any market, tracked to your record.',
        cta: 'Start a challenge',
        href: '/challenges/create',
        layout: '',
        icon: 'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-6.75c-.621 0-1.125.504-1.125 1.125v3.375m9 0h-9M9 3.75h6M9 3.75a3 3 0 0 0-3 3v2.25c0 .621.503 1.125 1.125 1.125h6.75A1.125 1.125 0 0 0 15 9.375V6.75a3 3 0 0 0-3-3ZM9 3.75h6m-6 0c0-.621.503-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125',
    },
    {
        title: 'Jackpots',
        body: 'Enter the public crowd. Pick 7–20 legs with confidence, commit before the reveal, and see how the crowd read the fixtures.',
        cta: 'Browse jackpots',
        href: '/jackpots',
        legs: true,
        layout: 'md:col-span-2',
        icon: 'M16.5 6.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 19.5a4.5 4.5 0 0 1-4.5 4.5h-6a4.5 4.5 0 0 1-4.5-4.5v-.75a6 6 0 0 1 6-6h3a6 6 0 0 1 6 6v.75Z',
    },
];
</script>