<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const lastUpdated = 'September 13, 2026';
</script>

<template>
    <Head title="Cookie Policy" />

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
                    Cookie Policy
                </h1>
                <p class="mt-2 text-xs font-semibold tracking-widest text-slate-500 uppercase">
                    Last updated: {{ lastUpdated }}
                </p>
            </div>

            <p class="mb-8 text-sm leading-relaxed text-slate-400">
                This policy explains what cookies and similar technologies
                Betslip Pirates uses, why we use them, and how you can control
                them. We keep this list short on purpose — we don't run
                advertising networks or third-party trackers.
            </p>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    1. What Are Cookies?
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Cookies are small text files that a website stores on your
                    device. They let the site remember things between page
                    loads — like whether you're logged in. There are also
                    similar technologies (local storage, session storage) that
                    serve the same purpose.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    2. Cookies We Use
                </h2>

                <div class="overflow-hidden rounded-lg border border-[#232d42]">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-400 uppercase">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Purpose</th>
                                <th class="px-4 py-3">Duration</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#232d42]/60">
                            <tr>
                                <td class="px-4 py-3 font-mono text-[11px] text-sky-400">
                                    betslip_session
                                </td>
                                <td class="px-4 py-3 text-slate-400">
                                    Keeps you logged in between page loads. Essential.
                                </td>
                                <td class="px-4 py-3 text-slate-500">2 hours</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-mono text-[11px] text-sky-400">
                                    XSRF-TOKEN
                                </td>
                                <td class="px-4 py-3 text-slate-400">
                                    Protects forms from cross-site request forgery. Essential.
                                </td>
                                <td class="px-4 py-3 text-slate-500">2 hours</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-mono text-[11px] text-sky-400">
                                    bet_selections
                                </td>
                                <td class="px-4 py-3 text-slate-400">
                                    Stores your in-progress betslip selections locally.
                                </td>
                                <td class="px-4 py-3 text-slate-500">Until cleared</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-mono text-[11px] text-sky-400">
                                    appearance
                                </td>
                                <td class="px-4 py-3 text-slate-400">
                                    Remembers your light/dark theme preference.
                                </td>
                                <td class="px-4 py-3 text-slate-500">1 year</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    3. What We Don't Use
                </h2>
                <ul class="list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-400">
                    <li>Advertising or retargeting cookies.</li>
                    <li>Third-party analytics like Google Analytics.</li>
                    <li>Social media tracking pixels.</li>
                    <li>Cross-site tracking of any kind.</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    4. Third-Party Cookies
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    When you deposit via Paystack or complete M-Pesa
                    transactions, those providers may set their own cookies on
                    their domains. We do not control those cookies. Refer to
                    their respective privacy and cookie policies.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    5. Managing Cookies
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    You can block or delete cookies in your browser settings.
                    However, if you disable the essential cookies listed above,
                    the Platform will not work — you won't be able to log in or
                    complete transactions.
                </p>
            </section>

            <section>
                <h2 class="mb-3 text-sm font-black tracking-widest text-white uppercase">
                    6. Contact
                </h2>
                <p class="text-sm leading-relaxed text-slate-400">
                    Questions about this policy? Email
                    <a href="mailto:privacy@betslip-pirates.com" class="text-sky-400 hover:underline">
                        privacy@betslip-pirates.com
                    </a>.
                </p>
            </section>
        </div>
    </div>
</template>