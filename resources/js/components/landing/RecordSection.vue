<template>
    <SectionWrapper v-if="shouldRender">
        <SectionHeading
            eyebrow="Real records"
            title="Real records from real players."
            subtitle="Every record below is verified after full time. No fakes, no edits."
        />

        <div class="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="record in records"
                :key="record.id"
                class="group relative overflow-hidden rounded-2xl border border-[#232d42] bg-gradient-to-b from-[#141b2d] to-[#0f1626] p-5 transition-all duration-300 hover:-translate-y-0.5 hover:border-sky-500/30 hover:shadow-[0_16px_40px_-16px_rgba(14,165,233,0.3)]"
            >
                <!-- Hover glow -->
                <div class="pointer-events-none absolute -top-16 -right-16 h-32 w-32 rounded-full bg-sky-500/5 opacity-0 blur-2xl transition-opacity duration-300 group-hover:opacity-100"></div>

                <!-- Header -->
                <div class="relative flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full border border-sky-500/30 bg-sky-500/10 font-mono text-xs font-black text-sky-400 ring-1 ring-transparent transition-all duration-300 group-hover:ring-sky-500/30">
                        {{ record.initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black tracking-wider text-white uppercase">
                            {{ record.name }}
                        </p>
                        <p class="mt-0.5 inline-block rounded border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 text-[9px] font-black tracking-widest text-amber-400 uppercase">
                            {{ record.tier }}
                        </p>
                    </div>
                </div>

                <!-- Accuracy -->
                <div class="relative mt-5 flex items-baseline justify-between">
                    <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">Accuracy</span>
                    <span class="font-mono text-2xl font-black text-white">{{ record.accuracy }}<span class="text-sm text-slate-500">%</span></span>
                </div>

                <!-- Progress bar -->
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-800">
                    <div class="h-full rounded-full bg-gradient-to-r from-sky-500 to-amber-400 shadow-[0_0_8px_rgba(56,189,248,0.5)]" :style="{ width: record.accuracy + '%' }"></div>
                </div>

                <!-- Notable -->
                <p class="relative mt-4 text-xs leading-relaxed text-slate-400">
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