<template>

    <Head title="Buyer dashboard" />

    <div class="flex min-h-screen flex-col bg-[#0a1628] px-4 pt-6 pb-28 font-sans text-slate-200 lg:px-8 lg:py-10">
        <div class="mx-auto flex w-full max-w-2xl flex-col items-center justify-center gap-6 py-16 text-center">
            <div
                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-400/10 text-sky-300 ring-1 ring-sky-400/25">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="h-8 w-8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-black tracking-tight text-white">Buyer dashboard</h1>
                <p class="mx-auto max-w-md text-sm leading-relaxed text-slate-400">
                    You've unlocked the buyer view. Head over to the full buyer dashboard to see live tracking,
                    settlement history, and analytics.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <Link href="/buyer/dashboard"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-sky-400 px-5 text-sm font-bold text-[#0a1628] transition-all hover:bg-sky-300 focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:outline-none active:scale-[0.98]">
                    Open buyer dashboard
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3"
                        stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </Link>
                <Link href="/dashboard"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/15 bg-white/[0.04] px-5 text-sm font-bold text-slate-200 transition-all hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:outline-none active:scale-[0.98]">
                    Back to Play Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>