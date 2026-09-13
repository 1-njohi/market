<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="How It Works" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to home
            </Link>

            <div class="mt-6 mb-12 border-b border-[#232d42] pb-10">
                <h1 class="text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    How It Works
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-relaxed font-medium text-slate-400">
                    Betslip Pirates is a marketplace for betslips — not a
                    bookmaker. Here's exactly what that means, step by step.
                </p>
            </div>

            <!-- Buyer flow -->
            <section class="mb-12">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full border border-sky-500/30 bg-sky-500/10 text-sky-400">
                        🛒
                    </div>
                    <h2 class="text-sm font-black tracking-widest text-white uppercase">
                        For Buyers
                    </h2>
                </div>

                <ol class="space-y-6">
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">Step 1</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Fund your wallet</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            Deposit via Paystack using M-Pesa or a card. Funds
                            land in your available balance immediately.
                        </p>
                    </li>
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">Step 2</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Browse and compare</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            Every seller has an open win rate, ROI, and recent
                            form. Sort by price, odds, or performance. Read the
                            seller's short note explaining their reasoning.
                        </p>
                    </li>
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">Step 3</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Unlock the betslip</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            Pay the seller's price. The selections are revealed
                            to you immediately. The money is held — not paid to
                            the seller yet.
                        </p>
                    </li>
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">Step 4</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Place your own bet</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            Use the selections on any bookmaker you like — no
                            lock-in to Betslip Pirates. Copy the picks, stake
                            however much you want, keep 100% of the winnings.
                        </p>
                    </li>
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">Step 5</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Get refunded if it loses</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            If the betslip loses, your purchase price is
                            automatically refunded to your wallet. If it wins,
                            the seller gets paid and you keep whatever you won
                            on the external bookmaker.
                        </p>
                    </li>
                </ol>
            </section>

            <!-- Seller flow -->
            <section class="mb-12">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full border border-amber-500/30 bg-amber-500/10 text-amber-400">
                        💰
                    </div>
                    <h2 class="text-sm font-black tracking-widest text-white uppercase">
                        For Sellers
                    </h2>
                </div>

                <ol class="space-y-6">
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Step 1</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Build a betslip</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            Pick your selections on the marketplace — same as
                            you would on any betting site. 2 to 6 legs is the
                            sweet spot for buyers.
                        </p>
                    </li>
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Step 2</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Set your price and list it</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            You choose the price. Higher odds = higher justified
                            price, but also fewer buyers. Check what similar
                            betslips are selling for.
                        </p>
                    </li>
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Step 3</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Get discovered</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            Your betslip appears in the marketplace feed and to
                            followers. Share it directly on WhatsApp, X, or
                            Telegram for faster sales.
                        </p>
                    </li>
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Step 4</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Money held in escrow</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            When someone buys, the price moves into your
                            <strong class="text-slate-200">pending balance</strong>.
                            It's not spendable yet — it's tied to the outcome of
                            your betslip.
                        </p>
                    </li>
                    <li class="border-l-2 border-[#232d42] pl-5">
                        <span class="text-[10px] font-black tracking-widest text-amber-400 uppercase">Step 5</span>
                        <h3 class="mt-1 text-base font-bold text-slate-200">Settle and withdraw</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            If your betslip wins, the pending balance becomes
                            available. You keep 75–90% depending on your tier.
                            Withdraw to M-Pesa at any time.
                        </p>
                    </li>
                </ol>
            </section>

            <!-- Fee tiers -->
            <section class="mb-12 rounded-lg border border-[#232d42] bg-[#161c2a] p-6">
                <h2 class="mb-4 text-sm font-black tracking-widest text-sky-400 uppercase">
                    Seller Fee Tiers
                </h2>
                <p class="mb-5 text-sm leading-relaxed text-slate-400">
                    The more you sell, the less you pay. Tiers are based on
                    lifetime sales volume and never reset.
                </p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded border border-[#232d42] bg-[#0f1422] p-4">
                        <span class="text-[10px] font-black tracking-widest text-amber-500 uppercase">Bronze</span>
                        <p class="mt-1 text-2xl font-black text-white">25%</p>
                        <p class="mt-1 text-xs text-slate-500">0 – 9 sales</p>
                    </div>
                    <div class="rounded border border-[#232d42] bg-[#0f1422] p-4">
                        <span class="text-[10px] font-black tracking-widest text-slate-300 uppercase">Silver</span>
                        <p class="mt-1 text-2xl font-black text-white">20%</p>
                        <p class="mt-1 text-xs text-slate-500">10 – 49 sales</p>
                    </div>
                    <div class="rounded border border-[#232d42] bg-[#0f1422] p-4">
                        <span class="text-[10px] font-black tracking-widest text-yellow-400 uppercase">Gold</span>
                        <p class="mt-1 text-2xl font-black text-white">15%</p>
                        <p class="mt-1 text-xs text-slate-500">50 – 99 sales</p>
                    </div>
                    <div class="rounded border border-[#232d42] bg-[#0f1422] p-4">
                        <span class="text-[10px] font-black tracking-widest text-emerald-400 uppercase">Platinum</span>
                        <p class="mt-1 text-2xl font-black text-white">10%</p>
                        <p class="mt-1 text-xs text-slate-500">100+ sales</p>
                    </div>
                </div>
            </section>

            <!-- FAQ teaser -->
            <section class="rounded-xl border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#111a30] p-6 text-center">
                <h2 class="text-lg font-black tracking-widest text-white uppercase">
                    Still have questions?
                </h2>
                <div class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link
                        href="/faq"
                        class="inline-block rounded-lg bg-[#ff8c00] px-6 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02]"
                    >
                        Read the FAQ
                    </Link>
                    <Link
                        href="/register"
                        class="inline-block rounded-lg border border-[#232d42] px-6 py-3 text-xs font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/50 hover:text-sky-400"
                    >
                        Get started
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>