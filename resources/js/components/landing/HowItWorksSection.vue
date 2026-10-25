<template>
    <SectionWrapper id="how-it-works">
        <SectionHeading
            eyebrow="How it works"
            title="From playing free to getting paid."
        />

        <div class="mt-14 grid grid-cols-1 gap-10 md:grid-cols-3">
            <div
                v-for="(step, i) in steps"
                :key="i"
                class="group relative text-center"
            >
                <!-- Connector line (desktop) -->
                <div
                    v-if="i < steps.length - 1"
                    class="pointer-events-none absolute top-10 left-[60%] hidden h-px w-[80%] bg-gradient-to-r from-sky-500/30 to-transparent md:block"
                ></div>

                <!-- Number -->
                <div class="relative inline-flex items-center justify-center">
                    <div class="absolute h-20 w-20 rounded-full bg-sky-500/10 blur-2xl transition-opacity duration-300 group-hover:opacity-100 opacity-60"></div>
                    <span class="relative font-mono text-6xl font-black text-transparent bg-gradient-to-b from-sky-400 to-sky-600/40 bg-clip-text transition-transform duration-300 group-hover:scale-110">
                        {{ String(i + 1).padStart(2, '0') }}
                    </span>
                </div>

                <h3 class="mt-4 text-xl font-black tracking-tight text-white">
                    {{ step.title }}
                </h3>
                <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-slate-400">
                    {{ step.body }}
                </p>
                <Link
                    :href="step.href"
                    class="mt-4 inline-flex items-center gap-1.5 text-xs font-black tracking-wider text-amber-400 uppercase transition-all duration-200 hover:gap-2.5 hover:text-amber-300"
                >
                    {{ step.cta }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3 w-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </Link>
            </div>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';

const page = usePage();

const recordHref = computed(() => {
    const user = page.props.auth?.user;
    return user ? `/u/${user.username ?? user.code ?? user.id}` : '/register';
});

const steps = computed(() => [
    {
        title: 'Play free',
        body: 'Pick every matchday with your crew. Contests, leaderboards, zero stake.',
        cta: 'Start playing free',
        href: '/matchdays',
    },
    {
        title: 'Build your record',
        body: 'Every pick counts. Verified after full time. Yours forever.',
        cta: 'See your record',
        href: recordHref.value,
    },
    {
        title: 'Get approved. Sell.',
        body: 'Cross the threshold. Publish your picks. Get paid when buyers follow.',
        cta: 'How approval works',
        href: '/how-approval-works',
    },
]);
</script>