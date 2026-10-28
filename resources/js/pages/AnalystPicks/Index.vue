<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AnalystPickCard from '@/components/AnalystPickCard.vue';
import AnalystRecordStrip from '@/components/AnalystRecordStrip.vue';

const props = defineProps<{
    pending: any[];
    settled: any[];
    pagination: { current_page: number; last_page: number; per_page: number; total: number; prev_url: string | null; next_url: string | null };
    record: any;
    meta: { og_title: string; og_description: string; og_image: string; canonical: string };
}>();
</script>

<template>
    <Head>
        <title>{{ meta.og_title }}</title>
        <meta name="description" :content="meta.og_description" />
        <link rel="canonical" :href="meta.canonical" />
        <meta property="og:title" :content="meta.og_title" />
        <meta property="og:description" :content="meta.og_description" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="meta.canonical" />
        <meta property="og:image" :content="meta.og_image" />
    </Head>

    <div class="min-h-screen bg-[#0a1628] px-4 py-10 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-5xl">
            <Link href="/" class="text-[10px] font-black uppercase tracking-widest text-sky-400 hover:underline">
                ← Home
            </Link>

            <header class="mt-6 border-b border-[#232d42] pb-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-400">Editorial</p>
                <h1 class="mt-1 text-3xl font-black uppercase tracking-wider text-white md:text-4xl">
                    Analyst picks
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400">
                    Free editorial picks from Fainali's in-house analyst. Every pick shown, wins and losses.
                    ROI is calculated at flat 1-unit stakes on settled picks only.
                </p>
                <div class="mt-4">
                    <AnalystRecordStrip :record="record" />
                </div>
            </header>

            <p class="mt-6 rounded-lg border border-amber-500/30 bg-amber-500/5 p-4 text-xs leading-relaxed text-amber-200">
                <strong class="font-black uppercase tracking-widest text-amber-400">Editorial, not advice.</strong>
                Every pick published here is shown — wins and losses — and the ROI is calculated from settled
                picks only. Past performance does not guarantee future results. 18+.
            </p>

            <!-- pending -->
            <section v-if="pending.length > 0" class="mt-10">
                <h2 class="text-[10px] font-black uppercase tracking-widest text-sky-400">
                    Pending ({{ pending.length }})
                </h2>
                <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <AnalystPickCard v-for="pick in pending" :key="pick.id" :pick="pick" />
                </div>
            </section>

            <!-- settled -->
            <section class="mt-10">
                <h2 class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                    Settled picks
                </h2>

                <div v-if="settled.length === 0" class="mt-3 text-sm text-slate-500">
                    No settled picks yet.
                </div>

                <div v-else class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <AnalystPickCard v-for="pick in settled" :key="pick.id" :pick="pick" />
                </div>

                <nav
                    v-if="pagination.last_page > 1"
                    class="mt-8 flex items-center justify-between gap-3"
                >
                    <Link
                        v-if="pagination.prev_url"
                        :href="pagination.prev_url"
                        preserve-scroll
                        class="rounded-lg border border-[#232d42] px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-400 hover:border-sky-500/50 hover:text-slate-200"
                    >
                        ← Previous
                    </Link>
                    <span v-else></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </span>
                    <Link
                        v-if="pagination.next_url"
                        :href="pagination.next_url"
                        preserve-scroll
                        class="rounded-lg border border-[#232d42] px-4 py-2 text-xs font-black uppercase tracking-widest text-slate-400 hover:border-sky-500/50 hover:text-slate-200"
                    >
                        Next →
                    </Link>
                    <span v-else></span>
                </nav>
            </section>
        </div>
    </div>
</template>