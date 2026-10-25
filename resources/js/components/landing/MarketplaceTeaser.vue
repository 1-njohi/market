<template>
    <SectionWrapper variant="darker">
        <div class="relative grid grid-cols-1 items-center gap-16 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20">
            <!-- Copy -->
            <div>
                <SectionHeading align="left" eyebrow="Marketplace" title="The marketplace unlocks when you're good." />

                <p class="mt-6 max-w-lg text-base leading-relaxed text-slate-400 sm:text-lg">
                    Every week, our sharpest pickers publish their calls. You can browse today: see who's selling, what they're picking, and what their record
                    says about them. But to sell, you need to earn it.
                </p>

                <!-- The two gates, then the payoff -->
                <div class="mt-9 flex items-stretch gap-3 sm:gap-4">
                    <div class="flex-1 rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-4">
                        <p class="text-4xl font-black tracking-tight text-white tabular-nums sm:text-5xl">20</p>
                        <p class="mt-1 text-sm text-slate-400">verified picks</p>
                    </div>
                    <div class="flex-1 rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-4">
                        <p class="text-4xl font-black tracking-tight text-white tabular-nums sm:text-5xl">55<span class="text-2xl text-slate-500 sm:text-3xl">%</span></p>
                        <p class="mt-1 text-sm text-slate-400">accuracy</p>
                    </div>
                    <div class="flex flex-1 items-center rounded-2xl border border-amber-300/30 bg-amber-300/10 px-5 py-4">
                        <p class="text-lg leading-tight font-black text-amber-200 sm:text-xl">Then it's yours.</p>
                    </div>
                </div>

                <div class="mt-10">
                    <Link
                        href="/marketplace"
                        class="group inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-7 py-4 text-sm font-bold tracking-wide text-slate-200 backdrop-blur transition-all duration-200 hover:border-sky-400/40 hover:bg-white/[0.08] hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none active:scale-[0.98]"
                    >
                        Browse the marketplace
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5 transition-transform duration-200 group-hover:translate-x-1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- A listing as buyers see it: record open, picks locked -->
            <div class="relative mx-auto w-full max-w-md lg:max-w-lg">
                <div class="pointer-events-none absolute -inset-8 rounded-[2.5rem] bg-gradient-to-br from-amber-400/20 via-transparent to-sky-500/15 blur-3xl" aria-hidden="true"></div>

                <article class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#0e1422]/90 shadow-[0_40px_90px_-30px_rgba(0,0,0,0.9)] backdrop-blur-xl">
                    <!-- Seller -->
                    <header class="flex items-center justify-between gap-4 p-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="relative shrink-0">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-amber-300/25 to-amber-500/10 text-sm font-black text-amber-300 ring-1 ring-amber-300/30">DK</div>
                                <span class="absolute -right-0.5 -bottom-0.5 h-3 w-3 rounded-full border-2 border-[#0e1422] bg-emerald-400"></span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-white">@denis <span class="ml-1 rounded-md bg-amber-300/15 px-1.5 py-0.5 text-xs font-bold text-amber-300">2395</span></p>
                                <p class="mt-0.5 text-sm text-slate-400">
                                    ROI <span class="font-semibold text-emerald-400">+32.4%</span>
                                    <span class="mx-1 text-slate-600">/</span>
                                    Win rate <span class="font-semibold text-white">64%</span>
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="flex justify-end gap-1" role="img" aria-label="Recent form: won, won, lost, won, won">
                                <span
                                    v-for="(r, i) in form"
                                    :key="i"
                                    class="h-3.5 w-3.5 rounded-[4px]"
                                    :class="r === 'W' ? 'bg-emerald-400/80 shadow-[0_0_8px_rgba(52,211,153,0.5)]' : 'bg-red-400/80 shadow-[0_0_8px_rgba(248,113,113,0.4)]'"
                                ></span>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500">Recent form</p>
                        </div>
                    </header>

                    <!-- Slip summary -->
                    <div class="flex items-center justify-between border-y border-white/[0.06] bg-black/20 px-5 py-3">
                        <p class="text-sm font-semibold text-slate-300">Multibet <span class="text-slate-500">(3 legs)</span></p>
                        <p class="text-sm text-slate-400">
                            Total odds <span class="ml-1 text-xl font-black text-amber-300 tabular-nums">5.20</span>
                        </p>
                    </div>

                    <!-- Legs: fixtures visible, selections locked -->
                    <ul class="divide-y divide-white/[0.05]">
                        <li v-for="leg in previewLegs" :key="leg.home" class="flex items-center gap-4 px-5 py-4">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-slate-500">{{ leg.league }} · {{ leg.time }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">
                                    {{ leg.home }} <span class="font-normal text-slate-500">v</span> {{ leg.away }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ leg.market }}</p>
                            </div>
                            <button
                                type="button"
                                @click="scrollToMarketplace"
                                class="group/lock relative flex h-10 w-24 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-dashed border-white/15 bg-white/[0.03] transition-colors hover:border-amber-300/50 hover:bg-amber-300/10 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
                            >
                                <span class="text-sm font-bold text-slate-200 blur-[5px] select-none" aria-hidden="true">{{ leg.selection }}</span>
                                <svg viewBox="0 0 24 24" fill="currentColor" class="absolute h-4 w-4 text-slate-300 transition-transform group-hover/lock:scale-110 group-hover/lock:text-amber-300" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                                </svg>
                                <span class="sr-only">Locked selection. Unlock to see it.</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Seller's insight -->
                    <blockquote class="mx-5 my-5 rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4">
                        <p class="text-sm leading-relaxed text-slate-200">
                            Arsenal are due a statement win and Spurs are leaking goals. BTTS is the banker here — everything else rides on it.
                        </p>
                        <footer class="mt-2 text-xs font-medium text-amber-300/80">Seller's insight</footer>
                    </blockquote>

                    <!-- Price + CTA -->
                    <footer class="flex items-center justify-between gap-4 border-t border-white/[0.06] bg-black/20 p-5">
                        <div>
                            <p class="text-2xl font-black tracking-tight text-white">KSH 120.00</p>
                            <p class="mt-0.5 text-xs text-slate-500">Break-even: KSH 28.57</p>
                        </div>
                        <button
                            type="button"
                            @click="scrollToMarketplace"
                            class="group/btn relative inline-flex cursor-pointer items-center gap-2 overflow-hidden rounded-xl bg-amber-400 px-7 py-3.5 text-sm font-black text-black shadow-[0_10px_30px_-10px_rgba(251,191,36,0.8)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]"
                        >
                            <span class="relative z-10">Unlock</span>
                            <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/50 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full motion-reduce:hidden"></span>
                        </button>
                    </footer>
                </article>
            </div>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';

const form = ['W', 'W', 'L', 'W', 'W'];

const previewLegs = [
    { league: 'EPL', time: '20:00 UTC', home: 'Arsenal', away: 'Tottenham', market: 'BTTS', selection: 'Yes' },
    { league: 'EPL', time: '17:30 UTC', home: 'Man City', away: 'Brighton', market: 'Over 2.5', selection: 'Over' },
    { league: 'UCL', time: '19:00 UTC', home: 'Real Madrid', away: 'Dortmund', market: '1X2', selection: 'Home' },
];

const scrollToMarketplace = () => {
    document
        .getElementById('marketplace-section')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>