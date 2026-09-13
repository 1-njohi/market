<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const lastUpdated = 'September 13, 2026';
</script>

<template>
    <Head title="Privacy Policy" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to home
            </Link>

            <div class="mt-6 mb-10 border-b border-[#232d42] pb-6">
                <h1 class="text-2xl font-black tracking-wider text-white uppercase md:text-3xl">
                    Privacy Policy
                </h1>
                <p class="mt-2 text-xs font-semibold tracking-widest text-slate-500 uppercase">
                    Last updated: {{ lastUpdated }}
                </p>
            </div>

            <p class="mb-8 text-sm leading-relaxed text-slate-400">
                This Privacy Policy explains what data Betslip Pirates
                collects, why we collect it, how we protect it, and the rights
                you have over it. We comply with the
                <strong class="text-slate-200">Kenya Data Protection Act, 2019</strong>.
            </p>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    1. What We Collect
                </h2>
                <ul class="list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-400">
                    <li><strong class="text-slate-200">Account data</strong> — name, email, phone number, country code, password hash.</li>
                    <li><strong class="text-slate-200">Wallet data</strong> — balances, transactions, deposits, withdrawals, and references to Paystack and M-Pesa records.</li>
                    <li><strong class="text-slate-200">Activity data</strong> — betslips you create or purchase, follow relationships, and in-app notifications.</li>
                    <li><strong class="text-slate-200">Technical data</strong> — IP address, browser type, device, and session metadata.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    2. Why We Collect It
                </h2>
                <ul class="list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-400">
                    <li>To operate your wallet and process deposits, withdrawals, and refunds.</li>
                    <li>To verify your identity where required by anti-fraud rules.</li>
                    <li>To show verified seller performance and prevent abuse.</li>
                    <li>To send you transaction confirmations and account notifications.</li>
                    <li>To improve the product (aggregate, anonymized analytics only).</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    3. Who We Share It With
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    We do not sell your data. We share it only with:
                </p>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-400">
                    <li><strong class="text-slate-200">Paystack</strong> — to process deposits and card payments.</li>
                    <li><strong class="text-slate-200">Safaricom</strong> — to process M-Pesa deposits and withdrawals.</li>
                    <li><strong class="text-slate-200">Infrastructure providers</strong> — hosting and email delivery, under data processing agreements.</li>
                    <li><strong class="text-slate-200">Law enforcement</strong> — only where legally required, and only what is strictly necessary.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    4. How Long We Keep It
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Wallet and transaction records are retained for
                    <strong class="text-slate-200">7 years</strong> to comply
                    with financial record-keeping requirements. Other account
                    data is retained while your account is active, and for up
                    to 12 months after closure for dispute resolution. You can
                    request deletion of non-retained data at any time.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    5. Security
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Passwords are hashed using industry-standard algorithms.
                    All communication uses HTTPS. Wallet balances are
                    reconciled daily. Access to production systems is limited
                    to authorized personnel and logged.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    6. Your Rights
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Under the Data Protection Act you have the right to:
                </p>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-400">
                    <li>Access the personal data we hold about you.</li>
                    <li>Correct inaccurate data.</li>
                    <li>Request deletion of data we are not legally required to retain.</li>
                    <li>Object to certain processing.</li>
                    <li>Lodge a complaint with the Office of the Data Protection Commissioner.</li>
                </ul>
                <p class="mt-3 text-sm leading-relaxed text-slate-400">
                    To exercise these rights, email
                    <a href="mailto:privacy@betslip-pirates.com" class="text-sky-400 hover:underline">
                        privacy@betslip-pirates.com
                    </a>.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    7. Cookies
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    We use essential cookies for session management and
                    authentication. We do not use tracking cookies for
                    advertising.
                </p>
            </section>

            <section>
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    8. Contact
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Data controller: Betslip Pirates, Nairobi, Kenya. Email
                    <a href="mailto:privacy@betslip-pirates.com" class="text-sky-400 hover:underline">
                        privacy@betslip-pirates.com
                    </a>.
                </p>
            </section>
        </div>
    </div>
</template>