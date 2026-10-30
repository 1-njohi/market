<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AnalystRecordStrip from '@/components/AnalystRecordStrip.vue';

defineProps<{
    analysts: any[];
    combined_record: any;
    meta: any;
}>();
</script>

<template>
    <Head>
        <title>{{ meta.og_title }}</title>
        <meta name="description" :content="meta.og_description" />
        <link rel="canonical" :href="meta.canonical" />
    </Head>

    <div class="min-h-screen bg-[#0a1628] px-4 py-10 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-4xl">
            <Link href="/" class="text-[10px] font-black uppercase tracking-widest text-sky-400 hover:underline">← Home</Link>

            <header class="mt-6 border-b border-[#232d42] pb-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-400">Editorial</p>
                <h1 class="mt-1 text-3xl font-black uppercase tracking-wider text-white md:text-4xl">Our analysts</h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400">
                    Every analyst on Fainali, ranked by honest ROI at flat 1-unit stakes on settled picks only.
                </p>
                <div class="mt-4">
                    <AnalystRecordStrip :record="combined_record" />
                </div>
            </header>

            <div class="mt-8 space-y-3">
                <Link
                    v-for="(a, i) in analysts"
                    :key="a.id"
                    :href="`/analysts/${a.slug}`"
                    class="group flex items-center gap-4 rounded-xl border border-[#232d42] bg-[#111622] p-4 transition-colors hover:border-sky-500/50"
                >
                    <span class="w-8 shrink-0 text-center font-mono text-sm font-black text-slate-500">{{ i + 1 }}</span>
                    <img v-if="a.avatar_url" :src="a.avatar_url" :alt="a.name"
                        class="h-12 w-12 shrink-0 rounded-full border border-[#232d42] object-cover" />
                    <div v-else class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[#232d42] bg-[#0a1628] text-lg font-black text-amber-400">
                        {{ a.name.charAt(0) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-white group-hover:text-amber-200">{{ a.name }}</p>
                        <p class="mt-0.5 truncate text-[11px] text-slate-500">
                            {{ a.record.won }}–{{ a.record.lost }}
                            <span v-if="a.record.roi !== null">
                                · {{ a.record.roi > 0 ? '+' : '' }}{{ a.record.roi }}% ROI
                            </span>
                            <span v-else>· {{ a.record.settled_count }} settled</span>
                        </p>
                    </div>
                    <span class="shrink-0 text-[10px] font-black uppercase tracking-widest text-slate-500 group-hover:text-sky-400">View →</span>
                </Link>
            </div>

            <p v-if="analysts.length === 0" class="mt-8 text-center text-sm text-slate-500">
                No analysts yet.
            </p>
        </div>
    </div>
</template>