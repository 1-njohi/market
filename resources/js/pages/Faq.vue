<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const openIndex = ref<number | null>(0);

const faqs = [
    {
        category: 'Getting Started',
        items: [
            {
                q: 'What exactly is Betslip Pirates?',
                a: 'A marketplace where people buy and sell betslips. Sellers list their selections at a price; buyers unlock those selections and use them however they want. We are not a bookmaker and we do not accept bets.',
            },
            {
                q: 'Do I need an account to buy?',
                a: 'Yes. You need an account to unlock a betslip, hold a wallet balance, and receive refunds. Registration takes about a minute.',
            },
            {
                q: 'How old do I need to be?',
                a: 'You must be at least 18 years old. By registering you confirm you are of legal age to participate in betting-related activity in your jurisdiction.',
            },
        ],
    },
    {
        category: 'Buying',
        items: [
            {
                q: 'What happens if the betslip loses?',
                a: 'You get a full refund of the purchase price to your wallet. Automatically. No claim needed, no support ticket. The refund happens the moment the betslip settles as a loss.',
            },
            {
                q: 'What happens if the betslip wins?',
                a: 'The purchase price is released to the seller. You keep whatever you won by placing your own bet on an external bookmaker using the revealed selections — we don\'t touch your winnings.',
            },
            {
                q: 'Can I see the selections before I pay?',
                a: 'No. Selections are revealed the moment you unlock. What you see before purchase is: the seller\'s win rate, ROI, recent form, the markets involved, the total odds, and the price.',
            },
            {
                q: 'Can I buy a betslip and not use it?',
                a: 'Yes. But the money stays committed to that betslip. If it loses you get refunded; if it wins you don\'t get anything back, because the seller earned it.',
            },
        ],
    },
    {
        category: 'Selling',
        items: [
            {
                q: 'How do I get paid as a seller?',
                a: 'When someone buys your betslip, the price moves into your pending balance. Once the betslip settles as a win, the pending balance becomes available. Then you can withdraw to M-Pesa.',
            },
            {
                q: 'What if my betslip loses?',
                a: 'The buyer gets refunded, and the pending balance is removed. You don\'t earn anything on a losing sale. There is no penalty or fee.',
            },
            {
                q: 'How do I price my betslip?',
                a: 'You set the price. Higher odds and higher win rates justify higher prices. Look at comparable listings in the marketplace to see what the market is paying.',
            },
            {
                q: 'Is there a limit to how many betslips I can list?',
                a: 'No. List as many as you want. The marketplace rewards volume with lower fee tiers on your winning sales.',
            },
        ],
    },
    {
        category: 'Wallet and Payments',
        items: [
            {
                q: 'How do I deposit?',
                a: 'Via Paystack — M-Pesa or card. Deposits credit your available balance instantly. Minimum deposit is KES 100.',
            },
            {
                q: 'How do I withdraw?',
                a: 'Withdraw to M-Pesa at any time. Minimum withdrawal is KES 10. Larger withdrawals are cheaper per shilling because of how Safaricom\'s fee tiers work.',
            },
            {
                q: 'What\'s the difference between available and pending balance?',
                a: 'Available is yours to spend or withdraw right now. Pending is money you\'ve earned on sales that haven\'t settled yet — it becomes available once the betslip wins.',
            },
            {
                q: 'Are there fees?',
                a: 'Sellers pay a platform fee on winning sales (25% down to 10% depending on tier). Paystack and M-Pesa charge small third-party fees on deposits and withdrawals. All fees are shown before you confirm.',
            },
        ],
    },
    {
        category: 'Trust and Safety',
        items: [
            {
                q: 'How do you prevent fake sellers?',
                a: 'Every seller\'s win/loss history is verified against settled fixtures. You can see exactly how many bets they\'ve won, lost, and their ROI over time. Suspicious accounts are suspended.',
            },
            {
                q: 'What if I suspect fraud?',
                a: 'Email support@betslip-pirates.com with the betslip code and your concern. We investigate every report and freeze suspicious accounts within 24 hours.',
            },
            {
                q: 'Is my personal data safe?',
                a: 'Yes. See our Privacy Policy for details. We only collect what\'s needed to run the marketplace and never sell your data.',
            },
        ],
    },
];

const toggle = (index: number) => {
    openIndex.value = openIndex.value === index ? null : index;
};

// Flatten for index lookup
let flatIndex = -1;
const flatFaqs = faqs.flatMap((cat) =>
    cat.items.map((item) => ({ ...item, category: cat.category, flatIdx: ++flatIndex })),
);
</script>

<template>
    <Head title="FAQ" />

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
                    Frequently Asked Questions
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">
                    Answers to the questions we get most. If yours isn't here,
                    email support@betslip-pirates.com.
                </p>
            </div>

            <!-- FAQ list grouped by category -->
            <div class="space-y-10">
                <div v-for="cat in faqs" :key="cat.category">
                    <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                        {{ cat.category }}
                    </h2>

                    <div class="space-y-2">
                        <div
                            v-for="item in cat.items"
                            :key="item.q"
                            class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
                        >
                            <button
                                type="button"
                                @click="toggle(flatFaqs.find((f) => f.q === item.q)?.flatIdx ?? null)"
                                class="flex w-full cursor-pointer items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-[#1a2233]"
                            >
                                <span class="text-sm font-bold text-slate-200">
                                    {{ item.q }}
                                </span>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-4 w-4 flex-shrink-0 text-slate-500 transition-transform"
                                    :class="
                                        openIndex ===
                                        (flatFaqs.find((f) => f.q === item.q)?.flatIdx ?? -1)
                                            ? 'rotate-180'
                                            : ''
                                    "
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m19.5 8.25-7.5 7.5-7.5-7.5"
                                    />
                                </svg>
                            </button>

                            <div
                                v-if="
                                    openIndex ===
                                    (flatFaqs.find((f) => f.q === item.q)?.flatIdx ?? -1)
                                "
                                class="border-t border-[#232d42] bg-[#111a30] px-5 py-4"
                            >
                                <p class="text-sm leading-relaxed text-slate-400">
                                    {{ item.a }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact prompt -->
            <section class="mt-12 rounded-xl border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#111a30] p-6 text-center">
                <h2 class="text-base font-black tracking-widest text-white uppercase">
                    Didn't find your answer?
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm text-slate-400">
                    Reach out — we respond within 24 hours.
                </p>
                <Link
                    href="/contact"
                    class="mt-5 inline-block rounded-lg bg-[#ff8c00] px-6 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02]"
                >
                    Contact us
                </Link>
            </section>
        </div>
    </div>
</template>