<template>
    <SectionWrapper variant="darker">
        <div class="grid grid-cols-1 items-center gap-14 lg:grid-cols-2">
            <!-- Left: preview card (mirrors real BetSlipSummaryCard) -->
            <div class="relative mx-auto w-full max-w-md">
                <!-- Glow behind card -->
                <div
                    class="absolute inset-0 rounded-2xl bg-gradient-to-br from-amber-500/15 via-transparent to-sky-500/10 blur-2xl">
                </div>

                <div
                    class="group/card relative overflow-hidden rounded-2xl border border-[#232d42] bg-gradient-to-b from-[#141b2d] to-[#0f1626] font-sans shadow-[0_24px_60px_-20px_rgba(0,0,0,0.8)] transition-all duration-300 hover:-translate-y-0.5 hover:border-amber-500/30 hover:shadow-[0_28px_70px_-24px_rgba(251,191,36,0.2)]">
                    <!-- Ambient glow -->
                    <div
                        class="pointer-events-none absolute -top-24 -right-24 h-48 w-48 rounded-full bg-amber-500/5 blur-3xl opacity-60 transition-opacity duration-500 group-hover/card:opacity-100">
                    </div>

                    <!-- 1. HEADER: Seller Branding & Mini-Heatmap -->
                    <div
                        class="relative flex items-center justify-between border-b border-[#232d42]/60 bg-gradient-to-b from-[#141b2d]/90 to-[#141b2d]/40 p-4">
                        <!-- Seller block -->
                        <div class="flex items-center space-x-3">
                            <!-- Avatar -->
                            <div class="relative flex-shrink-0">
                                <div
                                    class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-[#232d42] bg-amber-500/10 font-mono text-[10px] font-black text-amber-400 ring-1 ring-transparent transition-all duration-200 group-hover/card:ring-amber-500/40">
                                    DK
                                </div>
                                <span
                                    class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full border-2 border-[#141b2d] bg-emerald-400"></span>
                            </div>
                            <!-- Name + stats -->
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <h4 class="truncate text-xs font-bold tracking-wider text-white uppercase">
                                        @denis
                                    </h4>
                                    <span
                                        class="inline-flex items-center gap-1 rounded border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 text-[8px] font-black tracking-widest text-amber-400 uppercase">
                                        2395
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="3" stroke="currentColor"
                                        class="h-3 w-3 flex-shrink-0 text-slate-500 transition-all duration-200 group-hover/card:translate-x-0.5 group-hover/card:text-amber-400">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </div>
                                <div
                                    class="mt-1 flex items-center space-x-1 text-[10px] font-semibold tracking-wide text-slate-500 uppercase">
                                    <span class="inline-flex items-center gap-1">
                                        ROI:
                                        <span class="font-bold text-emerald-400">
                                            +32.4%
                                        </span>
                                    </span>
                                    <span class="text-[#232d42]">•</span>
                                    <span class="inline-flex items-center gap-1">
                                        Win Rate:
                                        <span class="font-bold text-white">
                                            64%
                                        </span>
                                    </span>
                                </div>
                                <span
                                    class="mt-1.5 inline-flex items-center gap-1 text-[9px] font-black tracking-widest text-slate-500 uppercase transition-colors duration-200 group-hover/card:text-amber-400">
                                    View profile
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="3" stroke="currentColor"
                                        class="h-2.5 w-2.5 transition-transform duration-200 group-hover/card:translate-x-0.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <!-- Mini-Heatmap -->
                        <div class="flex flex-shrink-0 flex-col items-end">
                            <div class="flex space-x-1">
                                <span v-for="(status, index) in flipRecentForm([
                                    'W',
                                    'W',
                                    'L',
                                    'W',
                                    'W',
                                ])" :key="index" :class="[
                                        'h-3 w-3 rounded-[3px] border transition-transform duration-200 hover:scale-110',
                                        status === 'W'
                                            ? 'border-emerald-500/60 bg-emerald-500/20 shadow-[0_0_6px_-1px_rgba(34,197,94,0.5)]'
                                            : 'border-red-500/60 bg-red-500/20 shadow-[0_0_6px_-1px_rgba(239,68,68,0.4)]',
                                    ]" :title="status === 'W' ? 'Won' : 'Lost'"></span>
                            </div>
                            <span class="mt-1.5 text-[9px] font-bold tracking-wider text-slate-500 uppercase">
                                Recent Form
                            </span>
                        </div>
                    </div>

                    <!-- 2. Bet Summary: Multibet + Total Odds -->
                    <div class="relative flex flex-col border-b border-[#232d42]/40 bg-[#141b2d]/30 select-none">
                        <div
                            class="flex items-center justify-between px-4 pt-3 pb-1.5 text-xs font-bold tracking-wider uppercase">
                            <div class="flex items-center space-x-2 text-slate-400">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-md border border-[#232d42]/60 bg-[#141b2d]/60 px-2 py-1 text-[10px] font-black tracking-widest">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18m-3-12v.75m0 3v.75m0 3v.75m0 3V18" />
                                    </svg>
                                    Multibet (3)
                                </span>
                            </div>
                            <div
                                class="inline-flex items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-500/5 px-2.5 py-1 text-white">
                                <span class="text-[10px] font-black tracking-widest text-amber-400/70 uppercase">
                                    Total Odds
                                </span>
                                <span class="text-base font-black tracking-tight text-amber-400">
                                    5.20
                                </span>
                            </div>
                        </div>

                        <!-- Tracking Code & Share -->
                        <div class="flex items-center justify-between border-t border-[#232d42]/20 px-4 pt-2 pb-3">
                            <div
                                class="flex items-center gap-1.5 text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                <span>Code:</span>
                                <span
                                    class="rounded-md border border-[#232d42] bg-[#0f1626]/80 px-2 py-0.5 font-mono text-xs font-black tracking-normal text-white shadow-inner">
                                    MKT-4821
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-[#232d42] bg-[#141b2d]/60 px-2.5 py-1.5 text-[10px] font-black tracking-wider text-slate-300 uppercase transition-all duration-150 hover:border-sky-500/50 hover:bg-sky-500/10 hover:text-sky-400 active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="h-3 w-3">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                                    </svg>
                                    Copy
                                </button>
                                <button type="button"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-[#232d42] bg-[#141b2d]/60 px-2.5 py-1.5 text-[10px] font-black tracking-wider text-slate-300 uppercase transition-all duration-150 hover:border-amber-500/50 hover:bg-amber-500/10 hover:text-amber-400 active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="h-3 w-3">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                                    </svg>
                                    Share
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. LEGS -->
                    <div class="divide-y divide-[#232d42]/40">
                        <div v-for="(leg, idx) in previewLegs" :key="idx"
                            class="group/leg relative p-4 transition-colors duration-150 hover:bg-[#141b2d]/60">
                            <!-- Accent bar on hover -->
                            <div
                                class="absolute top-0 bottom-0 left-0 w-[2px] bg-amber-500/0 transition-colors duration-200 group-hover/leg:bg-amber-500/60">
                            </div>

                            <!-- Leg Meta -->
                            <div
                                class="mb-2 flex items-center justify-between text-[10px] font-bold tracking-wider text-slate-500 uppercase">
                                <span class="inline-flex items-center gap-1.5">
                                    <span
                                        class="flex h-4 w-4 items-center justify-center rounded-full border border-[#232d42]/60 bg-[#141b2d]/60 text-[8px] font-black text-slate-500">
                                        {{ idx + 1 }}
                                    </span>
                                    {{ leg.league }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="h-3 w-3 text-slate-500/60">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    {{ leg.time }}
                                </span>
                            </div>

                            <!-- Fixture Grid -->
                            <div class="grid grid-cols-12 items-center gap-2">
                                <!-- Teams -->
                                <div class="col-span-7 flex flex-col space-y-0.5">
                                    <span class="truncate text-xs font-bold tracking-wide text-white uppercase">
                                        {{ leg.home }}
                                    </span>
                                    <span class="truncate text-xs font-bold tracking-wide text-slate-500 uppercase">
                                        {{ leg.away }}
                                    </span>
                                </div>

                                <!-- Market -->
                                <div class="col-span-3 text-right">
                                    <span class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase">
                                        Market
                                    </span>
                                    <span class="text-xs font-semibold tracking-wide text-slate-200">
                                        {{ leg.market }}
                                    </span>
                                </div>

                                <!-- Selection / Lock Icon -->
                                <div class="col-span-2 text-right">
                                    <div class="group/lock flex w-full cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-[#232d42] bg-[#141b2d]/50 py-1.5 text-slate-500 transition-colors select-none hover:border-amber-500/40 hover:bg-amber-500/5 hover:text-amber-400"
                                        @click="scrollToMarketplace">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="h-3.5 w-3.5 transition-transform duration-150 group-hover/lock:scale-110">
                                            <path fill-rule="evenodd"
                                                d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. SELLER'S CAPTION -->
                    <div
                        class="group/caption relative mx-3 mt-3 mb-3 overflow-hidden rounded-xl border border-[#232d42]/40 bg-gradient-to-br from-[#141b2d]/90 to-[#0f1626] p-4 shadow-inner">
                        <div
                            class="absolute -top-3 -left-1 text-8xl font-black text-amber-500/5 select-none transition-colors duration-300 group-hover/caption:text-amber-500/10">
                            "
                        </div>
                        <div class="relative flex items-start space-x-3">
                            <div class="flex-1">
                                <p class="text-sm leading-relaxed font-medium text-white/90">
                                    Arsenal are due a statement win and Spurs
                                    are leaking goals. BTTS is the banker
                                    here — everything else rides on it.
                                </p>
                                <span
                                    class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="currentColor" class="h-3 w-3 text-amber-500/60">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                    </svg>
                                    Seller's insight
                                </span>
                            </div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 h-16 w-16 rounded-full bg-amber-500/5 blur-2xl"></div>
                    </div>

                    <!-- 5. CTA / PRICE -->
                    <div
                        class="relative flex items-center justify-between border-t border-[#232d42]/80 bg-gradient-to-r from-[#141b2d]/95 to-[#0f1626]/90 p-4">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                Price
                            </span>
                            <span class="text-lg font-black tracking-wide text-white">
                                KSH 120.00
                            </span>
                            <span
                                class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold tracking-wide text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2.5" stroke="currentColor" class="h-3 w-3 text-slate-500/70">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Break-even: KSH 28.57
                            </span>
                        </div>
                        <button type="button" @click="scrollToMarketplace"
                            class="group/btn relative cursor-pointer overflow-hidden rounded-xl bg-amber-500 px-6 py-3 text-xs font-black tracking-widest text-black uppercase shadow-[0_4px_16px_-4px_rgba(251,191,36,0.5)] transition-all duration-200 hover:bg-amber-400 hover:shadow-[0_6px_24px_-4px_rgba(251,191,36,0.6)] active:scale-[0.98]">
                            <span class="relative z-10">Unlock</span>
                            <span
                                class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/40 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: copy -->
            <div>
                <SectionHeading align="left" eyebrow="Marketplace" title="The marketplace unlocks when you're good." />
                <p class="mt-6 text-base leading-relaxed text-slate-400">
                    Every week, our sharpest pickers publish their calls. You
                    can browse today — see who's selling, what they're
                    picking, and what their record says about them. But to
                    sell, you need to earn it. 20 verified picks. 55%
                    accuracy. Then it's yours.
                </p>
                <div class="mt-9">
                    <Link href="/marketplace"
                        class="group inline-flex items-center gap-2 rounded-xl border border-[#232d42] bg-[#111622] px-6 py-3.5 text-xs font-black tracking-widest text-slate-300 uppercase transition-all duration-200 hover:border-sky-500/40 hover:bg-[#141b2d] hover:text-white active:scale-[0.98]">
                        Browse the marketplace
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor"
                            class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-1">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';

const previewLegs = [
    {
        league: 'EPL',
        time: '20:00 UTC',
        home: 'Arsenal',
        away: 'Tottenham',
        market: 'BTTS',
        selection: 'Yes',
    },
    {
        league: 'EPL',
        time: '17:30 UTC',
        home: 'Man City',
        away: 'Brighton',
        market: 'Over 2.5',
        selection: 'Over',
    },
    {
        league: 'UCL',
        time: '19:00 UTC',
        home: 'Real Madrid',
        away: 'Dortmund',
        market: '1X2',
        selection: 'Home',
    },
];

const flipRecentForm = (form) => [...form].reverse();

const scrollToMarketplace = () => {
    document
        .getElementById('marketplace-section')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>