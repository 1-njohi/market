<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const lastUpdated = 'September 13, 2026';
</script>

<template>
    <Head title="Terms of Service" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <!-- Header -->
            <div class="mb-10 border-b border-[#232d42] pb-6">
                <Link
                    href="/"
                    class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
                >
                    ← Back to home
                </Link>
                <h1
                    class="mt-3 text-2xl font-black tracking-wider text-white uppercase md:text-3xl"
                >
                    Terms of Service
                </h1>
                <p class="mt-2 text-xs font-semibold tracking-widest text-slate-500 uppercase">
                    Last updated: {{ lastUpdated }}
                </p>
            </div>

            <!-- Intro -->
            <p class="mb-8 text-sm leading-relaxed text-slate-400">
                These Terms of Service ("Terms") govern your access to and use of
                Betslip Pirates (the "Platform"), operated in Nairobi, Kenya. By
                creating an account, you agree to be bound by these Terms. If you
                do not agree, do not use the Platform.
            </p>

            <!-- 1 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    1. What Betslip Pirates Is
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Betslip Pirates is a <strong class="text-slate-200">marketplace</strong>.
                    We are not a bookmaker, we do not accept wagers, and we do not
                    hold a betting licence. Sellers list betslips — collections of
                    selections — for buyers to purchase. Buyers pay sellers directly
                    through the Platform wallet. The seller's betslip is settled by
                    the outcome of the underlying sporting events, and the Platform
                    handles refunds and payouts accordingly.
                </p>
            </section>

            <!-- 2 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    2. Eligibility
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    You must be at least <strong class="text-slate-200">18 years old</strong>
                    and legally permitted to participate in betting-related activity
                    in your jurisdiction. By registering, you confirm that all
                    information you provide is accurate and that you are using the
                    Platform for lawful purposes only.
                </p>
            </section>

            <!-- 3 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    3. Your Account
                </h2>
                <ul class="list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-400">
                    <li>You are responsible for keeping your login credentials confidential.</li>
                    <li>One person, one account. Multiple accounts may be suspended.</li>
                    <li>You may not share, transfer, or sell your account to another person.</li>
                    <li>You must notify us immediately of any unauthorized access.</li>
                </ul>
            </section>

            <!-- 4 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    4. Wallet and Payments
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    The Platform wallet holds two balances: an
                    <strong class="text-slate-200">available balance</strong> you can
                    spend or withdraw, and a
                    <strong class="text-slate-200">pending balance</strong> that
                    represents earnings on betslips that have not yet settled.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-slate-400">
                    Deposits are made via Paystack. Withdrawals are paid out via
                    M-Pesa. Third-party processing fees may apply and are disclosed
                    at the point of transaction. Amounts marked as promotional or
                    referral credits are non-withdrawable and spendable only on the
                    Platform.
                </p>
            </section>

            <!-- 5 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    5. Buying a Betslip
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    When you purchase a betslip, the purchase price is deducted from
                    your available balance and held. If the betslip wins, the funds
                    are released to the seller. If the betslip loses, you receive a
                    <strong class="text-slate-200">full refund</strong> to your
                    available balance, and the seller receives nothing.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-slate-400">
                    Unlocking a betslip reveals the seller's selections. You use
                    those selections to place your own wager on any betting platform
                    of your choice. Betslip Pirates is not responsible for your
                    interaction with any external betting platform.
                </p>
            </section>

            <!-- 6 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    6. Selling a Betslip
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    When you list a betslip, you agree to sell it at the price you
                    set. On each sale you receive the sale price minus the platform
                    fee for your seller tier. Funds are held in your pending balance
                    until the betslip settles.
                </p>
                <p class="mt-3 text-sm leading-relaxed text-slate-400">
                    You may not list a betslip whose selections you have already
                    placed on an external platform with the intent to abandon it. You
                    may not list the same betslip content under multiple listings or
                    multiple accounts. Doing so may result in forfeiture of earnings
                    and suspension.
                </p>
            </section>

            <!-- 7 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    7. Platform Fees
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Sellers pay a platform fee on winning sales. Fees decrease as a
                    seller's lifetime sales volume increases, across four tiers.
                    The current fee schedule is displayed in the seller dashboard.
                    Buyer-side payment processing fees are disclosed at the time of
                    deposit or withdrawal.
                </p>
            </section>

            <!-- 8 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    8. Prohibited Conduct
                </h2>
                <ul class="list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-400">
                    <li>Self-referral, wash trading, or any scheme to inflate activity.</li>
                    <li>Listing betslips with false, misleading, or manipulated selections.</li>
                    <li>Attempting to circumvent the escrow, refund, or fee system.</li>
                    <li>Harassment, impersonation, or abuse of other users.</li>
                    <li>Automated scraping, bot-driven listings, or bulk account creation.</li>
                    <li>Any activity that violates Kenyan law or Safaricom's terms of service.</li>
                </ul>
            </section>

            <!-- 9 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    9. Disclaimers
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Betslip Pirates provides the marketplace on an
                    <strong class="text-slate-200">"as is"</strong> basis. We do not
                    guarantee that any betslip will win, that any seller's track
                    record will continue, or that the Platform will be available
                    without interruption. Sporting outcomes are inherently
                    unpredictable. You bear the risk of the selections you purchase.
                </p>
            </section>

            <!-- 10 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    10. Limitation of Liability
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    To the fullest extent permitted by Kenyan law, Betslip Pirates
                    and its operators shall not be liable for any indirect,
                    incidental, or consequential damages arising from your use of the
                    Platform. Our total liability to you shall not exceed the total
                    amount you have transacted through the Platform in the 3 months
                    preceding a claim.
                </p>
            </section>

            <!-- 11 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    11. Account Suspension and Termination
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    We may suspend or terminate any account, with or without notice,
                    if we reasonably believe it has violated these Terms, engaged in
                    fraud, or poses a risk to other users. Funds held in a terminated
                    account may be frozen pending investigation, and may be released
                    to the user once any disputes are resolved.
                </p>
            </section>

            <!-- 12 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    12. Changes to These Terms
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    We may update these Terms from time to time. Material changes
                    will be announced in-app or by email at least 14 days before
                    taking effect. Continued use of the Platform after changes take
                    effect constitutes acceptance.
                </p>
            </section>

            <!-- 13 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    13. Governing Law
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    These Terms are governed by the laws of the Republic of Kenya.
                    Any dispute arising from your use of the Platform shall first be
                    addressed through good-faith negotiation and, if unresolved,
                    submitted to the exclusive jurisdiction of the courts of Nairobi.
                </p>
            </section>

            <!-- 14 -->
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    14. Contact
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Questions about these Terms? Email
                    <a
                        href="mailto:legal@betslip-pirates.com"
                        class="text-sky-400 underline decoration-dotted hover:text-sky-300"
                    >
                        legal@betslip-pirates.com
                    </a>
                    .
                </p>
            </section>

            <!-- Footer note -->
            <div
                class="mt-12 rounded-lg border border-[#232d42] bg-[#161c2a] p-4 text-center"
            >
                <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                    By using Betslip Pirates, you confirm you are 18 or older.
                </p>
            </div>
        </div>
    </div>
</template>