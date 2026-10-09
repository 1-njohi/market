<template>
    <div
        class="min-h-screen bg-[#070b14] font-sans text-slate-200 selection:bg-sky-500/30 selection:text-white"
    >
        <div class="mx-auto w-full max-w-5xl px-4 py-8 md:px-6 md:py-12 lg:px-8">
            <!-- HEADER -->
            <div class="mb-6 border-b border-gray-800/60 pb-6">
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="backHref"
                        class="font-mono text-[9px] font-black tracking-widest text-slate-500 uppercase transition-colors hover:text-slate-300"
                    >
                        ← {{ backLabel }}
                    </Link>
                    <span class="text-slate-700">·</span>
                    <span
                        class="font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        {{ label }}
                    </span>
                </div>

                <h1
                    class="mt-3 truncate font-mono text-2xl font-black tracking-tight text-white uppercase md:text-3xl"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="subtitle"
                    class="mt-2 max-w-2xl text-xs leading-relaxed text-slate-400"
                >
                    {{ subtitle }}
                </p>
            </div>

            <!-- FILTERS -->
            <div v-if="$slots.filters" class="mb-5">
                <slot name="filters" />
            </div>

            <!-- CONTENT -->
            <slot />

            <!-- PAGINATION -->
            <div v-if="$slots.pagination" class="mt-6">
                <slot name="pagination" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    label: { type: String, required: true },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    backHref: { type: String, default: '/dashboard' },
    backLabel: { type: String, default: 'Dashboard' },
});
</script>