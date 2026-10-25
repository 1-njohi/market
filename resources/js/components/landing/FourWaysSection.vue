<template>
    <SectionWrapper>
        <SectionHeading
            eyebrow="How you build it"
            title="Four ways to prove you know football."
            subtitle="Every pick counts. Choose your path."
        />

        <div class="mt-14 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="card in cards"
                :key="card.title"
                :href="card.href"
                class="group relative flex flex-col overflow-hidden rounded-2xl border border-[#232d42] bg-gradient-to-b from-[#141b2d] to-[#0f1626] p-6 transition-all duration-300 hover:-translate-y-1 hover:border-sky-500/40 hover:shadow-[0_20px_48px_-16px_rgba(14,165,233,0.35)]"
            >
                <!-- Corner glow -->
                <div class="pointer-events-none absolute -top-12 -right-12 h-24 w-24 rounded-full bg-sky-500/10 opacity-0 blur-2xl transition-opacity duration-300 group-hover:opacity-100"></div>

                <!-- Icon -->
                <div class="relative mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl border border-sky-500/20 bg-sky-500/[0.07] text-sky-400 transition-all duration-300 group-hover:border-sky-500/40 group-hover:bg-sky-500/10 group-hover:shadow-[0_0_20px_-4px_rgba(56,189,248,0.5)]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-7 w-7">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                    </svg>
                </div>

                <h3 class="relative text-lg font-black tracking-tight text-white">
                    {{ card.title }}
                </h3>
                <p class="relative mt-2 flex-1 text-sm leading-relaxed text-slate-400">
                    {{ card.body }}
                </p>
                <span class="relative mt-5 inline-flex items-center gap-1.5 text-xs font-black tracking-widest text-amber-400 uppercase transition-all duration-200 group-hover:gap-2.5 group-hover:text-amber-300">
                    {{ card.cta }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3 w-3">
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
        icon: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
    },
    {
        title: 'Log your bets',
        body: 'Track the bets you place elsewhere. We auto-score them against real results.',
        cta: 'Log a bet',
        href: '/tracker/log',
        icon: 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
    },
    {
        title: 'One-off challenges',
        body: 'Challenge a friend. Any match, any market, tracked to your record.',
        cta: 'Start a challenge',
        href: '/challenges/create',
        icon: 'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-6.75c-.621 0-1.125.504-1.125 1.125v3.375m9 0h-9M9 3.75h6M9 3.75a3 3 0 0 0-3 3v2.25c0 .621.503 1.125 1.125 1.125h6.75A1.125 1.125 0 0 0 15 9.375V6.75a3 3 0 0 0-3-3ZM9 3.75h6m-6 0c0-.621.503-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125',
    },
    {
        title: 'Jackpots',
        body: 'Enter the public crowd. Pick 7–20 legs with confidence, commit before the reveal, and see how the crowd read the fixtures.',
        cta: 'Browse jackpots',
        href: '/jackpots',
        icon: 'M16.5 6.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 19.5a4.5 4.5 0 0 1-4.5 4.5h-6a4.5 4.5 0 0 1-4.5-4.5v-.75a6 6 0 0 1 6-6h3a6 6 0 0 1 6 6v.75Z',
    },
];
</script>