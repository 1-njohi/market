<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const facts = [
    { label: 'Founded', value: '2026' },
    { label: 'Headquarters', value: 'Nairobi, Kenya' },
    { label: 'Category', value: 'Peer-to-peer betslip marketplace' },
    { label: 'Model', value: 'Marketplace with escrow + refund guarantee' },
];

const pressReleases = [
    {
        date: 'September 2026',
        title: 'Betslip Pirates launches peer-to-peer betslip marketplace in Kenya',
        excerpt: 'A new platform lets tipsters sell betslips to verified buyers, with a full refund guarantee on losing bets.',
    },
    {
        date: 'October 2026',
        title: 'Betslip Pirates hits 1,000 active users in first month',
        excerpt: 'Early traction shows strong appetite for an escrow-backed marketplace in East Africa\'s largest betting market.',
    },
];

const brandAssets = [
    {
        name: 'Primary logo (SVG)',
        description: 'Full-color logo on transparent background.',
    },
    {
        name: 'Wordmark (SVG)',
        description: 'Text-only logotype for use where the icon is not needed.',
    },
    {
        name: 'Brand color palette',
        description: 'Navy #070b14, gold #ff8c00, sky #0ea5e9.',
    },
];
</script>

<template>
    <Head title="Press Kit" />

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
                    Press Kit
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-400">
                    Everything journalists, bloggers, and partners need to
                    write about Betslip Pirates accurately. For interviews,
                    contact
                    <a href="mailto:press@betslip-pirates.com" class="text-sky-400 hover:underline">
                        press@betslip-pirates.com
                    </a>.
                </p>
            </div>

            <!-- Boilerplate -->
            <section class="mb-12">
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Company Boilerplate
                </h2>
                <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                    <p class="text-sm leading-relaxed text-slate-400">
                        Betslip Pirates is a peer-to-peer marketplace for
                        buying and selling betslips, based in Nairobi, Kenya.
                        Founded in 2026, the platform lets verified sellers list
                        their betslips at prices they choose, and lets buyers
                        unlock those selections using an escrow-backed wallet.
                        If a betslip wins, the seller is paid. If it loses, the
                        buyer receives an automatic full refund. The platform
                        is not a bookmaker and does not accept wagers.
                    </p>
                </div>
            </section>

            <!-- Quick facts -->
            <section class="mb-12">
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Quick Facts
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div
                        v-for="fact in facts"
                        :key="fact.label"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">
                            {{ fact.label }}
                        </span>
                        <p class="mt-2 text-sm font-bold text-slate-200">
                            {{ fact.value }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Press releases -->
            <!-- <section class="mb-12">
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Recent Coverage
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="release in pressReleases"
                        :key="release.title"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                    >
                        <span class="text-[9px] font-black tracking-widest text-amber-400 uppercase">
                            {{ release.date }}
                        </span>
                        <h3 class="mt-2 text-sm font-bold text-slate-200">
                            {{ release.title }}
                        </h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">
                            {{ release.excerpt }}
                        </p>
                    </div>
                </div>
            </section> -->

            <!-- Brand assets -->
            <section class="mb-12">
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Brand Assets
                </h2>
                <p class="mb-4 text-xs leading-relaxed text-slate-500">
                    All logo files, brand colors, and typography guidance are
                    available on request. Please follow these rules when using
                    our marks.
                </p>
                <div class="space-y-3">
                    <div
                        v-for="asset in brandAssets"
                        :key="asset.name"
                        class="flex items-center justify-between rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <div>
                            <h3 class="text-sm font-bold text-slate-200">
                                {{ asset.name }}
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ asset.description }}
                            </p>
                        </div>
                        <a
                            href="mailto:press@betslip-pirates.com?subject=Brand assets request"
                            class="rounded border border-sky-500/30 bg-sky-500/5 px-3 py-1.5 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:border-sky-500 hover:bg-sky-500/10"
                        >
                            Request
                        </a>
                    </div>
                </div>

                <div class="mt-5 rounded-lg border border-amber-500/20 bg-amber-500/5 p-4">
                    <h3 class="text-xs font-black tracking-widest text-amber-400 uppercase">
                        Usage Rules
                    </h3>
                    <ul class="mt-3 space-y-1.5 text-xs leading-relaxed text-slate-400">
                        <li>· Don't recolor or distort the logo.</li>
                        <li>· Always include clear space around the mark.</li>
                        <li>· Refer to us as "Betslip Pirates" — one word, capital B, capital P.</li>
                        <li>· Don't imply endorsement or partnership without written permission.</li>
                    </ul>
                </div>
            </section>

            <!-- Contact -->
            <section class="rounded-xl border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#111a30] p-6 text-center">
                <h2 class="text-base font-black tracking-widest text-white uppercase">
                    Media enquiries
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-400">
                    For interviews, quotes, or clarifications, we usually
                    respond within one business day.
                </p>
                <a
                    href="mailto:press@betslip-pirates.com"
                    class="mt-5 inline-block rounded-lg bg-[#ff8c00] px-6 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02]"
                >
                    press@betslip-pirates.com
                </a>
            </section>
        </div>
    </div>
</template>