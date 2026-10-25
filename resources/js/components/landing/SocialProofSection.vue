<template>
    <SectionWrapper v-if="shouldRender">
        <SectionHeading
            eyebrow="Real records"
            title="Real records from real players."
            subtitle="Every record below is verified after full time. No fakes, no edits."
        />

        <div
            class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
        >
            <div
                v-for="record in records"
                :key="record.id"
                class="rounded-xl border border-[#232d42] bg-[#111622] p-5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-sky-500/30 bg-sky-500/10 font-mono text-xs font-black text-sky-400"
                    >
                        {{ record.initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-black tracking-wider text-white uppercase">
                            {{ record.name }}
                        </p>
                        <p
                            class="mt-0.5 text-[9px] font-black tracking-widest text-amber-400 uppercase"
                        >
                            {{ record.tier }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline justify-between">
                    <span
                        class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                    >
                        Accuracy
                    </span>
                    <span class="font-mono text-xl font-black text-white">
                        {{ record.accuracy }}%
                    </span>
                </div>
                <p class="mt-3 text-xs text-slate-400">
                    {{ record.notable }}
                </p>
            </div>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';

const page = usePage();

const props = defineProps({
    records: { type: Array, default: () => [] },
});

const shouldRender = computed(
    () =>
        page.props.features?.landing_social_proof === true &&
        props.records.length >= 5,
);
</script>