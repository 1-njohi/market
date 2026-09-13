<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const categories = [
    {
        icon: '🚀',
        title: 'Getting Started',
        description: 'Create an account, fund your wallet, and make your first transaction.',
        articles: [
            'How to register on Betslip Pirates',
            'Funding your wallet with M-Pesa',
            'Understanding your dashboard',
            'Verifying your email address',
        ],
    },
    {
        icon: '🛒',
        title: 'Buying Betslips',
        description: 'Browse, unlock, and use other people\'s picks with confidence.',
        articles: [
            'How to evaluate a seller\'s track record',
            'What happens when a betslip wins',
            'How the automatic refund works',
            'Reading a seller\'s recent form',
        ],
    },
    {
        icon: '💰',
        title: 'Selling Betslips',
        description: 'Create listings, price them, and get paid on wins.',
        articles: [
            'Creating your first betslip',
            'How to price a betslip for maximum sales',
            'Understanding pending vs available balance',
            'Fee tiers explained',
        ],
    },
    {
        icon: '💳',
        title: 'Wallet & Payments',
        description: 'Deposits, withdrawals, fees, and balance questions.',
        articles: [
            'Minimum deposit and withdrawal amounts',
            'Why my withdrawal is stuck at processing',
            'M-Pesa transaction costs explained',
            'Refunds and reversals',
        ],
    },
    {
        icon: '🔒',
        title: 'Account & Security',
        description: 'Protecting your account and managing your data.',
        articles: [
            'Changing your password',
            'Enabling two-factor authentication',
            'Closing your account',
            'Reporting suspicious activity',
        ],
    },
    {
        icon: '⚖️',
        title: 'Rules & Policies',
        description: 'Fair-use rules, prohibited conduct, and platform policies.',
        articles: [
            'Why accounts get suspended',
            'Multi-account policy',
            'Betslip validity and settlement rules',
            'Dispute resolution process',
        ],
    },
];

const popularArticles = [
    {
        title: 'What happens if a betslip loses?',
        excerpt: 'The buyer gets refunded automatically. Here\'s exactly when and how.',
        href: '/faq',
    },
    {
        title: 'How do I withdraw to M-Pesa?',
        excerpt: 'Step-by-step guide to moving your available balance to your phone.',
        href: '/faq',
    },
    {
        title: 'Understanding the escrow mechanic',
        excerpt: 'Why sellers don\'t get paid immediately on a sale — and why that protects everyone.',
        href: '/how-it-works',
    },
];
</script>

<template>
    <Head title="Help Center" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-4xl">
            <Link
                href="/"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to home
            </Link>

            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <h1 class="text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    Help Center
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-400">
                    Answers to the questions we get most. Can't find what you
                    need? <Link href="/contact" class="text-sky-400 hover:underline">Contact support</Link>.
                </p>
            </div>

            <!-- Popular articles -->
            <section class="mb-12">
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Popular Articles
                </h2>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <Link
                        v-for="article in popularArticles"
                        :key="article.title"
                        :href="article.href"
                        class="group rounded-lg border border-[#232d42] bg-[#161c2a] p-4 transition-colors hover:border-sky-500/40"
                    >
                        <h3 class="text-sm font-bold text-slate-200 transition-colors group-hover:text-sky-400">
                            {{ article.title }}
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">
                            {{ article.excerpt }}
                        </p>
                    </Link>
                </div>
            </section>

            <!-- Categories -->
            <section>
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Browse by Category
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="cat in categories"
                        :key="cat.title"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#232d42] bg-[#0f1422] text-base">
                                {{ cat.icon }}
                            </div>
                            <h3 class="text-sm font-black tracking-widest text-white uppercase">
                                {{ cat.title }}
                            </h3>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-slate-500">
                            {{ cat.description }}
                        </p>
                        <ul class="mt-4 space-y-2">
                            <li
                                v-for="article in cat.articles"
                                :key="article"
                                class="flex items-start gap-2 text-xs text-slate-400"
                            >
                                <span class="mt-1.5 h-1 w-1 flex-shrink-0 rounded-full bg-sky-500/60"></span>
                                <span>{{ article }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Still need help -->
            <section class="mt-12 rounded-xl border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#111a30] p-6 text-center">
                <h2 class="text-base font-black tracking-widest text-white uppercase">
                    Still stuck?
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-400">
                    Our support team responds within 24 hours. Reach out with
                    as much detail as you can.
                </p>
                <div class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link
                        href="/contact"
                        class="inline-block rounded-lg bg-[#ff8c00] px-6 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02]"
                    >
                        Contact support
                    </Link>
                    <Link
                        href="/faq"
                        class="inline-block rounded-lg border border-[#232d42] px-6 py-3 text-xs font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/50 hover:text-sky-400"
                    >
                        Read the FAQ
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>