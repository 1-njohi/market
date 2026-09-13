<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="About Us" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <!-- Back link -->
            <Link
                href="/"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to home
            </Link>

            <!-- ═══ HERO ═══ -->
            <div class="mt-6 mb-12 border-b border-[#232d42] pb-10">
                <h1
                    class="text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    About Betslip Pirates
                </h1>
                <p
                    class="mt-4 max-w-2xl text-base leading-relaxed font-medium text-slate-400"
                >
                    We built a marketplace where bettors can buy and sell
                    betslips — the actual picks, not the wagers. No bookmaker,
                    no casino, no house. Just people who know the game,
                    trading with people who want a verified shortcut.
                </p>
            </div>

            <!-- ═══ THE PROBLEM ═══ -->
            <section class="mb-12">
                <h2
                    class="mb-4 text-sm font-black tracking-widest text-sky-400 uppercase"
                >
                    The problem we saw
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Kenya has one of the most active betting communities in the
                    world. Millions of people analyse matches, share tips in
                    WhatsApp groups, and argue about form on Twitter. Yet when
                    it comes time to actually put money on the line, they have
                    nothing to go on except a stranger's word.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-slate-400">
                    The best predictors — the ones quietly hitting 70%+ over
                    months — give their picks away for free. The worst ones
                    sell tips with no track record and ghost you when they lose.
                    There's no middle ground, and there's no accountability.
                </p>
            </section>

            <!-- ═══ WHAT WE ARE ═══ -->
            <section class="mb-12">
                <h2
                    class="mb-4 text-sm font-black tracking-widest text-sky-400 uppercase"
                >
                    What we built
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Betslip Pirates is a
                    <strong class="text-slate-200">peer-to-peer marketplace</strong>.
                    Sellers list betslips at a price they choose. Buyers unlock
                    those betslips, see the actual picks, and use them however
                    they want — on any bookmaker, anywhere.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-slate-400">
                    The magic is in the mechanic: if the betslip wins, the
                    seller gets paid. If it loses, the buyer gets a full
                    refund. This aligns everyone's incentives. Sellers are
                    motivated to sell quality, not quantity. Buyers can take
                    real risk without real downside on a bad pick.
                </p>
            </section>

            <!-- ═══ WHAT WE'RE NOT ═══ -->
            <section class="mb-12">
                <h2
                    class="mb-4 text-sm font-black tracking-widest text-sky-400 uppercase"
                >
                    What we're not
                </h2>
                <div class="space-y-3">
                    <div
                        class="flex gap-3 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <div class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-rose-500/15">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3 w-3 text-rose-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-200">We are not a bookmaker.</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
                                We don't accept bets, we don't hold odds, and we don't have a house edge. Every shilling that moves through the platform goes to a user.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex gap-3 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <div class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-rose-500/15">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3 w-3 text-rose-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-200">We are not a tipster site.</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
                                We don't give our own picks or pretend to know the future. We give tipsters a real marketplace and buyers a real way to verify who's worth listening to.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex gap-3 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <div class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-rose-500/15">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3 w-3 text-rose-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-200">We are not a shortcut to easy money.</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
                                Betting has risk. Our refund guarantee protects buyers from bad picks, but it doesn't turn the market into a guaranteed income stream. Sellers must actually know the game.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ HOW IT WORKS ═══ -->
            <section class="mb-12">
                <h2
                    class="mb-4 text-sm font-black tracking-widest text-sky-400 uppercase"
                >
                    How it works
                </h2>

                <!-- Buyer side -->
                <div class="mb-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-sky-500/30 bg-sky-500/10 text-sky-400"
                        >
                            🛒
                        </div>
                        <h3 class="text-sm font-black tracking-widest text-white uppercase">
                            If you're a buyer
                        </h3>
                    </div>
                    <ol class="ml-5 list-decimal space-y-2 text-sm leading-relaxed text-slate-400">
                        <li>Top up your wallet via Paystack (M-Pesa, cards).</li>
                        <li>Browse betslips and check each seller's verified win rate and ROI.</li>
                        <li>Unlock the ones that fit your risk appetite. Funds are held from your balance.</li>
                        <li>Place your own bet on any bookmaker using the revealed selections.</li>
                        <li>If the betslip loses, we refund you automatically. If it wins, the seller gets paid.</li>
                    </ol>
                </div>

                <!-- Seller side -->
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-500/30 bg-amber-500/10 text-amber-400"
                        >
                            💰
                        </div>
                        <h3 class="text-sm font-black tracking-widest text-white uppercase">
                            If you're a seller
                        </h3>
                    </div>
                    <ol class="ml-5 list-decimal space-y-2 text-sm leading-relaxed text-slate-400">
                        <li>Build a betslip exactly like you would on any betting site.</li>
                        <li>List it at a price you choose. Set your own terms.</li>
                        <li>Buyers unlock it. The money sits in your pending balance.</li>
                        <li>If your betslip wins, you get paid. You keep 75–90% depending on your tier.</li>
                        <li>Withdraw your earnings to M-Pesa at any time.</li>
                    </ol>
                </div>
            </section>

            <!-- ═══ WHAT WE BELIEVE ═══ -->
            <section class="mb-12">
                <h2
                    class="mb-4 text-sm font-black tracking-widest text-sky-400 uppercase"
                >
                    What we believe
                </h2>

                <div class="space-y-4">
                    <div class="border-l-2 border-sky-500/40 pl-4">
                        <h3 class="text-sm font-bold text-slate-200">
                            Skill should be rewarded.
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">
                            If you consistently analyse matches better than the
                            average punter, you deserve to earn from that.
                            Right now, the only people cashing in on Kenyan
                            betting knowledge are the bookmakers.
                        </p>
                    </div>

                    <div class="border-l-2 border-sky-500/40 pl-4">
                        <h3 class="text-sm font-bold text-slate-200">
                            Track records should be public.
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">
                            Nobody should have to take a tipster's word for it.
                            Every seller on Betslip Pirates has an open,
                            verifiable win/loss history. Their reputation is
                            earned, not claimed.
                        </p>
                    </div>

                    <div class="border-l-2 border-sky-500/40 pl-4">
                        <h3 class="text-sm font-bold text-slate-200">
                            Risk should be shared fairly.
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">
                            When a buyer takes a chance on someone else's
                            analysis, they shouldn't be the only one who loses
                            if it doesn't work out. The refund mechanic turns
                            a one-sided transaction into a partnership.
                        </p>
                    </div>

                    <div class="border-l-2 border-sky-500/40 pl-4">
                        <h3 class="text-sm font-bold text-slate-200">
                            Local first.
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">
                            We're Kenyan-built for the Kenyan market. M-Pesa
                            in, M-Pesa out. Real Kenyan tipsters, real Kenyan
                            buyers, prices in KES. No foreign payment
                            infrastructure, no dollar conversions, no friction.
                        </p>
                    </div>
                </div>
            </section>

            <!-- ═══ CLOSING / CTA ═══ -->
            <section
                class="mb-12 rounded-xl border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#111a30] p-6 text-center md:p-8"
            >
                <h2
                    class="text-lg font-black tracking-widest text-white uppercase md:text-xl"
                >
                    Ready to get started?
                </h2>
                <p
                    class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-400"
                >
                    Whether you're here to sell your picks or buy someone
                    else's, you're two minutes from your first transaction.
                </p>
                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link
                        href="/register"
                        class="inline-block rounded-lg bg-[#ff8c00] px-6 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95"
                    >
                        Create an account
                    </Link>
                    <Link
                        href="/marketplace"
                        class="inline-block rounded-lg border border-[#232d42] bg-transparent px-6 py-3 text-xs font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/50 hover:text-sky-400"
                    >
                        Browse betslips
                    </Link>
                </div>
            </section>

            <!-- ═══ CONTACT ═══ -->
            <section class="border-t border-[#232d42] pt-8">
                <h2
                    class="mb-3 text-sm font-black tracking-widest text-sky-400 uppercase"
                >
                    Get in touch
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Questions, feedback, or partnership enquiries — we read
                    everything.
                </p>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <a
                        href="mailto:hello@betslip-pirates.com"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-3 transition-colors hover:border-sky-500/40"
                    >
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            General
                        </span>
                        <span class="mt-1 block text-xs font-semibold text-slate-300">
                            hello@betslip-pirates.com
                        </span>
                    </a>
                    <a
                        href="mailto:press@betslip-pirates.com"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-3 transition-colors hover:border-sky-500/40"
                    >
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Press
                        </span>
                        <span class="mt-1 block text-xs font-semibold text-slate-300">
                            press@betslip-pirates.com
                        </span>
                    </a>
                </div>
            </section>
        </div>
    </div>
</template>