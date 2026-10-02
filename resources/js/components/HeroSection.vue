<template>
    <div class="relative w-full overflow-hidden bg-[#0a1628]">
        <!-- TEST MODE BANNER — remove before production -->
        <div
            class="relative z-20 border-b border-amber-500/30 bg-amber-500/10 py-2 text-center backdrop-blur-sm"
        >
            <p
                class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
            >
                ⚠ Development Preview — No Real Transactions Processed
            </p>
        </div>

        <!-- Hero content -->
        <div
            class="relative mx-auto w-full max-w-[1200px] px-2 py-16 md:px-6 lg:px-8 lg:py-24"
        >
            <!-- Ambient Glows -->
            <div
                class="pointer-events-none absolute top-0 left-1/4 h-96 w-96 rounded-full bg-sky-600/10 blur-[120px]"
            ></div>
            <div
                class="pointer-events-none absolute right-1/4 bottom-0 h-96 w-96 rounded-full bg-amber-500/5 blur-[140px]"
            ></div>

            <div class="relative z-10 text-center">
                <!-- Brand Tagline -->
                <div
                    class="mb-6 inline-flex items-center space-x-2 rounded-full border border-sky-500/20 bg-sky-500/10 px-3 py-1"
                >
                    <span
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-sky-400"
                    ></span>
                    <span
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        A Marketplace for Verified Betslips
                    </span>
                </div>

                <!-- Headline -->
                <h1
                    class="mx-auto max-w-4xl text-4xl leading-[1.1] font-black tracking-tight text-white uppercase sm:text-5xl lg:text-6xl"
                >
                    Stop Buying Fake Betslips.<br />
                    <span
                        class="bg-gradient-to-r from-sky-500 via-indigo-400 to-amber-400 bg-clip-text text-transparent"
                    >
                        Real Picks. Real Results. Real Money.
                    </span>
                </h1>

                <!-- Subheadline -->
                <p
                    class="mx-auto mt-6 max-w-2xl text-sm leading-relaxed text-slate-400 sm:text-base"
                >
                    A marketplace where tipsters sell their betslips and
                    buyers pay to unlock the picks. Every result is
                    <span class="font-semibold text-slate-200"
                        >verified after the match</span
                    >, so sellers can't edit or fake their history. If a slip
                    loses, you get your money back.
                </p>

                <!-- CTAs -->
                <div
                    class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Link
                        href="/marketplace"
                        class="w-full rounded-xl bg-amber-500 px-8 py-4 text-xs font-bold tracking-wider text-black uppercase shadow-xl shadow-amber-500/20 transition-all hover:bg-amber-400 active:scale-[0.98] sm:w-auto"
                    >
                        Browse Betslips
                    </Link>

                    <button
                        @click="$emit('become-seller')"
                        class="w-full cursor-pointer rounded-xl border border-[#232d42] bg-[#111622] px-8 py-4 text-xs font-bold tracking-wider text-slate-300 uppercase transition-all hover:border-sky-500/40 hover:text-white sm:w-auto"
                    >
                        Start Selling Your Betslips
                    </button>
                </div>

                <!-- Metrics Bar -->
                <div
                    class="mx-auto mt-16 grid max-w-4xl grid-cols-2 gap-6 border-t border-[#232d42] pt-12 md:grid-cols-4"
                >
                    <div class="text-center">
                        <span
                            class="font-mono text-2xl font-black tracking-wide text-white"
                            >100%</span
                        >
                        <span
                            class="mt-1 block text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                        >
                            Results Verified
                        </span>
                    </div>
                    <div class="text-center">
                        <span
                            class="font-mono text-2xl font-black tracking-wide text-emerald-400"
                            >+24.8%</span
                        >
                        <span
                            class="mt-1 block text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                        >
                            Top Pirate Profit
                        </span>
                    </div>
                    <div class="text-center">
                        <span
                            class="font-mono text-2xl font-black tracking-wide text-sky-400"
                            >0</span
                        >
                        <span
                            class="mt-1 block text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                        >
                            Fake Results
                        </span>
                    </div>
                    <div class="text-center">
                        <span
                            class="font-mono text-2xl font-black tracking-wide text-amber-400"
                            >Instant</span
                        >
                        <span
                            class="mt-1 block text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                        >
                            Safe Payouts
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineEmits(['become-seller']);
</script>