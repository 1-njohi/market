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

    <div class="min-h-screen bg-[#070b14] px-4 pt-8 pb-16 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-5xl space-y-10">
            <!-- Heading -->
            <header>
                <p class="text-sm font-semibold text-purple-300">Public · Collaborative</p>
                <h1 class="mt-1 text-4xl font-black tracking-tight text-white md:text-5xl">Jackpots</h1>
                <p class="mt-3 max-w-2xl text-base leading-relaxed text-slate-400">
                    Match the bookmakers' most popular multi-leg tickets. Pick your outcomes, then see how the crowd voted.
                </p>
            </header>

            <!-- Open now -->
            <section v-if="jackpots.open.length">
                <h2 class="mb-4 text-lg font-bold text-white">Open now</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <Link
                        v-for="j in jackpots.open"
                        :key="j.uuid"
                        :href="`/jackpots/${j.uuid}`"
                        class="group relative block overflow-hidden rounded-3xl border border-purple-400/25 bg-[#0b1220] p-6 transition-all duration-300 hover:-translate-y-1 hover:border-purple-300/60 hover:shadow-[0_24px_60px_-28px_rgba(192,132,252,0.5)] focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-[0.99]"
                    >
                        <div class="pointer-events-none absolute -top-16 -right-12 h-44 w-44 rounded-full bg-purple-400/15 blur-3xl" aria-hidden="true"></div>

                        <div class="relative flex items-center justify-between gap-3">
                            <span class="rounded-full bg-purple-400/10 px-2.5 py-1 text-xs font-bold text-purple-300">Jackpot</span>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-bold"
                                :class="j.is_locked ? 'bg-rose-400/10 text-rose-300' : 'bg-emerald-400/10 text-emerald-300'"
                            >
                                {{ j.is_locked ? 'Locked' : 'Open' }}
                            </span>
                        </div>

                        <h3 class="relative mt-4 text-2xl leading-snug font-black tracking-tight text-white">{{ j.name }}</h3>

                        <dl class="relative mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-black/25 p-3.5">
                                <dt class="text-xs text-slate-500">Legs</dt>
                                <dd class="mt-0.5 text-2xl font-black text-white tabular-nums">{{ j.legs_count }}</dd>
                            </div>
                            <div class="rounded-2xl bg-black/25 p-3.5">
                                <dt class="text-xs text-slate-500">Entered</dt>
                                <dd class="mt-0.5 text-2xl font-black text-white tabular-nums">{{ j.participant_count }}</dd>
                            </div>
                        </dl>

                        <div class="relative mt-5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-slate-500">Locks</p>
                                <p class="text-sm font-bold text-white tabular-nums">{{ formatDate(j.locks_at) }}</p>
                            </div>
                            <span
                                class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-5 text-sm font-black transition-all duration-200 group-hover:gap-3"
                                :class="j.is_locked ? 'border border-white/15 bg-white/[0.04] text-slate-200' : 'bg-amber-400 text-[#070b14] shadow-[0_10px_30px_-12px_rgba(251,191,36,0.8)]'"
                            >
                                {{ j.is_locked ? 'View' : 'Enter' }}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </span>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Recently settled -->
            <section v-if="jackpots.recent.length">
                <h2 class="mb-4 text-lg font-bold text-slate-300">Recently settled</h2>
                <ul class="divide-y divide-white/[0.06] overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]">
                    <li v-for="j in jackpots.recent" :key="j.uuid">
                        <Link
                            :href="`/jackpots/${j.uuid}`"
                            class="group flex min-h-16 items-center justify-between gap-3 px-5 py-3.5 transition-colors hover:bg-white/[0.03] focus-visible:bg-white/[0.04] focus-visible:outline-none"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-white">{{ j.name }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    {{ j.legs_count }} legs · {{ j.participant_count }} entered · settled {{ formatDate(j.settled_at) }}
                                </p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 shrink-0 text-slate-600 transition-all duration-200 group-hover:translate-x-1 group-hover:text-purple-300" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- Empty -->
            <div v-if="!jackpots.open.length && !jackpots.recent.length" class="flex flex-col items-center gap-3 rounded-3xl border border-white/[0.07] bg-[#0b1220] px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-400/10 text-purple-300 ring-1 ring-purple-400/25">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-7 w-7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                    </svg>
                </span>
                <p class="text-lg font-bold text-slate-100">No jackpots yet</p>
                <p class="max-w-md text-sm leading-relaxed text-slate-500">Admins publish new jackpots ahead of the weekend's fixtures. Check back soon.</p>
            </div>
        </div>
    </div>
</template>