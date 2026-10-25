<template>
    <SectionWrapper variant="darker">
        <div class="text-center">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-sky-500/20 bg-sky-500/[0.07] px-3 py-1">
                <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">And the rest</span>
            </div>
            <h2 class="mt-3 text-2xl font-black tracking-tight text-white uppercase sm:text-3xl">
                Built for the ones who take it seriously.
            </h2>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div
                v-for="feature in features"
                :key="feature.title"
                class="group relative overflow-hidden rounded-xl border border-[#232d42] bg-gradient-to-b from-[#141b2d] to-[#0f1626] p-5 transition-all duration-300 hover:-translate-y-0.5 hover:border-sky-500/30 hover:shadow-[0_12px_32px_-12px_rgba(14,165,233,0.25)]"
            >
                <div class="pointer-events-none absolute -top-10 -right-10 h-20 w-20 rounded-full bg-sky-500/5 opacity-0 blur-2xl transition-opacity duration-300 group-hover:opacity-100"></div>

                <div class="relative mb-4 inline-flex h-10 w-10 items-center justify-center rounded-lg border border-sky-500/20 bg-sky-500/[0.07] text-sky-400 transition-all duration-300 group-hover:border-sky-500/40 group-hover:shadow-[0_0_16px_-4px_rgba(56,189,248,0.5)]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="feature.icon" />
                    </svg>
                </div>
                <p class="relative text-sm font-black tracking-tight text-white">{{ feature.title }}</p>
                <p class="relative mt-1.5 text-xs leading-relaxed text-slate-400">{{ feature.body }}</p>
            </div>
        </div>
    </SectionWrapper>
</template>

<script setup>
import SectionWrapper from './SectionWrapper.vue';

const features = [
    {
        title: 'Verified after full time',
        body: 'Every result checked against the live API. No edits, ever.',
        icon: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
    {
        title: 'Personal debrief',
        body: 'After each matchday, see your wins, misses, and patterns.',
        icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
    },
    {
        title: 'Sub-leagues with friends',
        body: 'Private leaderboards. Invite-only.',
        icon: 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225.012.45.036.671M18 18.72a9.05 9.05 0 0 1-6 0m6 0v-.014a9.05 9.05 0 0 0-6 .014m0 0a9.09 9.09 0 0 1-3.741-.479 3 3 0 0 1 4.682-2.72m.94 3.198v.031c0 .225-.012.45-.036.671M6 18.72v-.014A9.05 9.05 0 0 1 12 18.72m0 0v-.014a9.05 9.05 0 0 0 6 .014M15.75 9.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
    {
        title: 'Your record page',
        body: "A public URL that proves you're good.",
        icon: 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244',
    },
    {
        title: 'Follow the sharpest',
        body: 'Follow a top picker and get notified when they publish.',
        icon: 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
    },
];
</script>