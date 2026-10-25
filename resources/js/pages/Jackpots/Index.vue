<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    jackpots: { type: Object, required: true },
});

const formatDate = (iso?: string | null) =>
    iso ? new Date(iso).toLocaleString('en-KE', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false }) : '—';
</script>

<template>
    <Head title="Jackpots" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-5xl space-y-8">
            <header class="border-b border-[#232d42] pb-6">
                <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Public · Collaborative</p>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">Jackpots</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-400">
                    Match the bookmakers' most popular multi-leg tickets. Pick your outcomes, then see how the crowd voted.
                </p>
            </header>

            <section v-if="jackpots.open.length">
                <h2 class="mb-3 text-xs font-black tracking-widest text-white uppercase">Open now</h2>
                <div class="space-y-2">
                    <Link v-for="j in jackpots.open" :key="j.uuid" :href="`/jackpots/${j.uuid}`"
                        class="block rounded-lg border border-amber-500/30 bg-gradient-to-br from-amber-500/10 to-transparent p-5 transition-colors hover:border-amber-500/60">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                                    {{ j.is_locked ? 'Locked' : 'Open' }} · {{ j.legs_count }} legs
                                </p>
                                <h3 class="mt-1 font-mono text-lg font-black text-white">{{ j.name }}</h3>
                                <p class="mt-1 text-[11px] text-slate-400">
                                    {{ j.participant_count }} entered · Locks {{ formatDate(j.locks_at) }}
                                </p>
                            </div>
                            <span class="rounded bg-amber-500 px-4 py-2 text-[10px] font-black tracking-widest text-black uppercase">
                                {{ j.is_locked ? 'View →' : 'Enter →' }}
                            </span>
                        </div>
                    </Link>
                </div>
            </section>

            <section v-if="jackpots.recent.length">
                <h2 class="mb-3 text-xs font-black tracking-widest text-slate-500 uppercase">Recently settled</h2>
                <div class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                    <ul class="divide-y divide-[#232d42]/40">
                        <li v-for="j in jackpots.recent" :key="j.uuid">
                            <Link :href="`/jackpots/${j.uuid}`" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-[#111a30]/40">
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-sm font-bold text-white">{{ j.name }}</p>
                                    <p class="text-[11px] text-slate-500">
                                        {{ j.legs_count }} legs · {{ j.participant_count }} entered · settled {{ formatDate(j.settled_at) }}
                                    </p>
                                </div>
                                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">View →</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <div v-if="!jackpots.open.length && !jackpots.recent.length" class="rounded-lg border border-[#232d42] bg-[#161c2a] px-6 py-12 text-center">
                <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">No jackpots yet</p>
                <p class="mx-auto mt-2 max-w-md text-[11px] text-slate-500">
                    Admins publish new jackpots ahead of the weekend's fixtures. Check back soon.
                </p>
            </div>
        </div>
    </div>
</template>