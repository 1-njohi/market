<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Responsible Gaming" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to home
            </Link>

            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <h1 class="text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    Responsible Gaming
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-400">
                    Betting carries real risk. Betslip Pirates is a marketplace
                    for adults who choose to engage with betting — we take that
                    responsibility seriously.
                </p>
            </div>

            <!-- Age notice -->
            <div class="mb-8 rounded-lg border border-sky-500/30 bg-sky-500/5 p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border border-sky-500/40 bg-sky-500/10 text-sm font-black text-sky-400">
                        18+
                    </div>
                    <div>
                        <h3 class="text-sm font-black tracking-widest text-sky-400 uppercase">
                            Strictly for adults
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-400">
                            You must be at least 18 years old to use Betslip
                            Pirates. We verify age at registration and may
                            suspend accounts where age cannot be confirmed.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Warning signs -->
            <section class="mb-10">
                <h2 class="mb-4 text-sm font-black tracking-widest text-white uppercase">
                    Warning Signs
                </h2>
                <p class="mb-4 text-sm leading-relaxed text-slate-400">
                    Betting stops being entertainment when it starts becoming
                    a source of stress. Watch for these signs in yourself or
                    someone you know:
                </p>
                <ul class="space-y-2 text-sm leading-relaxed text-slate-400">
                    <li class="flex items-start gap-2">
                        <span class="mt-1.5 h-1 w-1 flex-shrink-0 rounded-full bg-rose-500"></span>
                        Chasing losses — placing bigger bets to win back what you've lost.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1.5 h-1 w-1 flex-shrink-0 rounded-full bg-rose-500"></span>
                        Betting with money meant for rent, food, or family.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1.5 h-1 w-1 flex-shrink-0 rounded-full bg-rose-500"></span>
                        Lying to family or friends about how much you bet.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1.5 h-1 w-1 flex-shrink-0 rounded-full bg-rose-500"></span>
                        Feeling anxious, irritable, or depressed around betting.
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1.5 h-1 w-1 flex-shrink-0 rounded-full bg-rose-500"></span>
                        Neglecting work, studies, or relationships because of betting.
                    </li>
                </ul>
            </section>

            <!-- Tools -->
            <section class="mb-10">
                <h2 class="mb-4 text-sm font-black tracking-widest text-white uppercase">
                    Tools Available to You
                </h2>
                <div class="space-y-3">
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <h3 class="text-sm font-bold text-slate-200">Self-exclusion</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">
                            Email us at responsible@betslip-pirates.com and we
                            will permanently close your account and prevent
                            reactivation. Funds in your wallet will be returned
                            to your registered M-Pesa number.
                        </p>
                    </div>
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <h3 class="text-sm font-bold text-slate-200">Account closure</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">
                            You can close your account at any time from
                            settings. Any available balance will be paid out to
                            you.
                        </p>
                    </div>
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <h3 class="text-sm font-bold text-slate-200">Cooling-off period</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">
                            Request a temporary freeze on deposits for 30 days.
                            Sales on existing betslips still settle normally.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Support -->
            <section class="mb-10">
                <h2 class="mb-4 text-sm font-black tracking-widest text-white uppercase">
                    Where to Get Help
                </h2>
                <div class="space-y-3">
                    <a
                        href="https://www.begambleaware.org"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block rounded-lg border border-[#232d42] bg-[#161c2a] p-4 transition-colors hover:border-sky-500/40"
                    >
                        <span class="text-sm font-bold text-slate-200">BeGambleAware</span>
                        <p class="mt-1 text-xs text-slate-500">
                            Free advice, self-assessment, and confidential support. International.
                        </p>
                    </a>
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <span class="text-sm font-bold text-slate-200">Kenya Red Cross — Counselling</span>
                        <p class="mt-1 text-xs text-slate-500">
                            Free psychosocial support across Kenya. Call 1199 or visit redcross.or.ke
                        </p>
                    </div>
                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <span class="text-sm font-bold text-slate-200">Emergency</span>
                        <p class="mt-1 text-xs text-slate-500">
                            If you or someone you know is in crisis, call the Befrienders Kenya at +254 722 178 177.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Commitment -->
            <section class="rounded-xl border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#111a30] p-6 text-center">
                <h2 class="text-base font-black tracking-widest text-white uppercase">
                    Our commitment
                </h2>
                <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-slate-400">
                    Betslip Pirates is a marketplace, not a promoter. We will
                    never send marketing that pushes you to bet more, we will
                    never reward you for volume, and we will never make you
                    feel bad for stopping.
                </p>
            </section>
        </div>
    </div>
</template>