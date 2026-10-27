<template>
    <SectionWrapper>
        <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
            <SectionHeading
                align="left"
                eyebrow="Build your record"
                title="Create your betslip."
                subtitle="Pick a market on any fixture. Every selection counts toward your record, listed or not."
            />

            <div class="w-full shrink-0 md:max-w-xs">
                <FixtureSearch v-model="search" />
            </div>
        </div>

        <div class="mt-10 select-none">
            <FixtureList :leagues="leagues" :search="search" />
        </div>
    </SectionWrapper>
</template>

<script setup>
import { ref } from 'vue';
import SectionWrapper from './landing/SectionWrapper.vue';
import SectionHeading from './landing/SectionHeading.vue';
import FixtureList from '@/components/FixtureList.vue';
import FixtureSearch from '@/components/FixtureSearch.vue';

defineProps({
    leagues: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const search = ref('');
</script>