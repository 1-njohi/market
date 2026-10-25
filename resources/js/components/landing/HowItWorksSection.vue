<template>
    <SectionWrapper id="how-it-works">
        <SectionHeading
            eyebrow="How it works"
            title="From playing free to getting paid."
        />

        <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-3">
            <div v-for="(step, i) in steps" :key="i" class="text-center">
                <div
                    class="font-mono text-6xl font-black text-sky-500/40"
                >
                    {{ i + 1 }}
                </div>
                <h3 class="mt-4 text-xl font-bold text-white">
                    {{ step.title }}
                </h3>
                <p class="mt-2 text-sm text-slate-400">
                    {{ step.body }}
                </p>
                <Link
                    :href="step.href"
                    class="mt-4 inline-block text-xs font-bold tracking-wider text-amber-400 uppercase transition-colors hover:text-amber-300"
                >
                    {{ step.cta }} →
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