<template>
    <div class="flex flex-col items-center justify-center gap-4 px-6 py-12 text-center">
        <div :class="['flex h-14 w-14 items-center justify-center rounded-2xl ring-1', iconClass]">
            <slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-7 w-7" aria-hidden="true">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"
                    />
                </svg>
            </slot>
        </div>

        <div class="space-y-1.5">
            <p class="text-base font-bold text-slate-100">{{ title }}</p>
            <p v-if="body" class="mx-auto max-w-sm text-sm leading-relaxed text-slate-500">{{ body }}</p>
        </div>

        <Link
            v-if="ctaHref && ctaLabel"
            :href="ctaHref"
            :class="[
                'mt-1 inline-flex min-h-11 items-center gap-2 rounded-xl border px-5 text-sm font-bold transition-all duration-200 focus-visible:ring-2 focus-visible:outline-none active:scale-[0.98]',
                ctaClass,
            ]"
        >
            {{ ctaLabel }}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </Link>

        <slot />
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    body: { type: String, default: '' },
    ctaLabel: { type: String, default: '' },
    ctaHref: { type: String, default: '' },
    accent: { type: String, default: 'slate' },
});

const palette = {
    sky: 'bg-sky-400/10 text-sky-300 ring-sky-400/25',
    emerald: 'bg-emerald-400/10 text-emerald-300 ring-emerald-400/25',
    amber: 'bg-amber-400/10 text-amber-300 ring-amber-400/25',
    rose: 'bg-rose-400/10 text-rose-300 ring-rose-400/25',
    purple: 'bg-purple-400/10 text-purple-300 ring-purple-400/25',
    slate: 'bg-white/[0.05] text-slate-400 ring-white/10',
};

const ctaPalette = {
    sky: 'border-sky-400/40 bg-sky-400/10 text-sky-200 hover:bg-sky-400 hover:text-[#070b14] focus-visible:ring-sky-300',
    emerald: 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200 hover:bg-emerald-400 hover:text-[#070b14] focus-visible:ring-emerald-300',
    amber: 'border-amber-400/40 bg-amber-400/10 text-amber-200 hover:bg-amber-400 hover:text-[#070b14] focus-visible:ring-amber-300',
    rose: 'border-rose-400/40 bg-rose-400/10 text-rose-200 hover:bg-rose-400 hover:text-[#070b14] focus-visible:ring-rose-300',
    purple: 'border-purple-400/40 bg-purple-400/10 text-purple-200 hover:bg-purple-400 hover:text-[#070b14] focus-visible:ring-purple-300',
    slate: 'border-white/15 bg-white/[0.05] text-slate-200 hover:bg-white/15 hover:text-white focus-visible:ring-slate-300',
};

const iconClass = computed(() => palette[props.accent] ?? palette.slate);
const ctaClass = computed(() => ctaPalette[props.accent] ?? ctaPalette.slate);
</script>