<template>
    <Head title="Seller dashboard" />

    <div class="flex min-h-screen flex-col bg-[#0a1628] px-4 pt-6 pb-28 font-sans text-slate-200 lg:px-8 lg:py-10">
        <div class="mx-auto flex w-full max-w-2xl flex-col items-center justify-center gap-6 py-16 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-400/10 text-amber-300 ring-1 ring-amber-400/25">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-8 w-8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                </svg>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-black tracking-tight text-white">Seller dashboard</h1>
                <p class="mx-auto max-w-md text-sm leading-relaxed text-slate-400">
                    You've qualified to sell. Head over to the full seller dashboard to manage listings, settlements, and followers.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <Link
                    href="/seller/dashboard"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-amber-400 px-5 text-sm font-bold text-[#0a1628] transition-all hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-[0.98]"
                >
                    Open seller dashboard
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </Link>
                <Link
                    href="/dashboard"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/15 bg-white/[0.04] px-5 text-sm font-bold text-slate-200 transition-all hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:outline-none active:scale-[0.98]"
                >
                    Back to Play
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>