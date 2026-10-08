<template>
    <div class="flex flex-col items-center justify-center gap-3 px-6 py-10 text-center">
        <div
            :class="[
                'flex h-12 w-12 items-center justify-center rounded-full border',
                iconClass,
            ]"
        >
            <slot name="icon">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.8"
                    stroke="currentColor"
                    class="h-6 w-6"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"
                    />
                </svg>
            </slot>
        </div>

        <div class="space-y-1.5">
            <p class="text-xs font-black tracking-widest text-slate-300 uppercase">
                {{ title }}
            </p>
            <p v-if="body" class="max-w-sm text-[11px] leading-relaxed text-slate-500">
                {{ body }}
            </p>
        </div>

        <Link
            v-if="ctaHref && ctaLabel"
            :href="ctaHref"
            :class="[
                'mt-1 inline-flex items-center gap-2 rounded border px-3.5 py-2 font-mono text-[10px] font-black tracking-widest uppercase transition-all',
                ctaClass,
            ]"
        >
            {{ ctaLabel }}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="3"
                stroke="currentColor"
                class="h-3 w-3"
            >
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
    sky: 'border-sky-500/30 bg-sky-500/10 text-sky-400',
    emerald: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
    amber: 'border-amber-500/30 bg-amber-500/10 text-amber-400',
    rose: 'border-rose-500/30 bg-rose-500/10 text-rose-400',
    purple: 'border-purple-500/30 bg-purple-500/10 text-purple-400',
    slate: 'border-slate-500/30 bg-slate-500/10 text-slate-400',
};

const ctaPalette = {
    sky: 'border-sky-500/40 text-sky-400 hover:border-sky-400 hover:bg-sky-500/10',
    emerald: 'border-emerald-500/40 text-emerald-400 hover:border-emerald-400 hover:bg-emerald-500/10',
    amber: 'border-amber-500/40 text-amber-400 hover:border-amber-400 hover:bg-amber-500/10',
    rose: 'border-rose-500/40 text-rose-400 hover:border-rose-400 hover:bg-rose-500/10',
    purple: 'border-purple-500/40 text-purple-400 hover:border-purple-400 hover:bg-purple-500/10',
    slate: 'border-slate-500/40 text-slate-300 hover:border-slate-400 hover:bg-slate-500/10',
};

const iconClass = computed(() => palette[props.accent] ?? palette.slate);
const ctaClass = computed(() => ctaPalette[props.accent] ?? ctaPalette.slate);
</script>