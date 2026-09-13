<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const values = [
    {
        title: 'Ship, then refine',
        body: "We launch fast, learn from real users, and improve. Perfect plans that never see daylight don't help anyone.",
    },
    {
        title: 'Local first',
        body: 'Every decision starts with the Kenyan market — payments, language, tone. We build for the people we serve.',
    },
    {
        title: 'Small team, real ownership',
        body: 'Every person owns a whole surface. No layers, no waiting for permission. You decide and you deliver.',
    },
    {
        title: "Respect the users' money",
        body: 'People trust us with their wallets. Everything we build assumes that trust must be earned daily.',
    },
];

const roles = [
    {
        title: 'Growth Marketer',
        location: 'Nairobi',
        type: 'Remote',
        description:
            'Own acquisition across TikTok, X, and Telegram. Comfortable with both paid and organic, and comfortable operating without a playbook.',
        tags: ['Content', 'Paid Social', 'Community'],
    },
];
</script>

<template>
    <Head title="Careers" />

    <div
        class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8"
    >
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to home
            </Link>

            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <h1
                    class="text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    Careers
                </h1>
                <p
                    class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-400"
                >
                    We're a small Nairobi-based team building the marketplace
                    for Kenyan bettors. If that sounds like something you want
                    to help build, keep reading.
                </p>
            </div>

            <!-- Values -->
            <section class="mb-12">
                <h2
                    class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                >
                    How We Work
                </h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="value in values"
                        :key="value.title"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                    >
                        <h3 class="text-sm font-bold text-slate-200">
                            {{ value.title }}
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-400">
                            {{ value.body }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Open roles -->
            <section class="mb-12">
                <h2
                    class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                >
                    Open Roles
                </h2>

                <div
                    v-if="roles.length === 0"
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-8 text-center"
                >
                    <p class="text-sm text-slate-400">
                        No open roles right now. Send us your CV anyway — we
                        keep strong candidates on file.
                    </p>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="role in roles"
                        :key="role.title"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5 transition-colors hover:border-sky-500/40"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div>
                                <h3 class="text-base font-bold text-slate-100">
                                    {{ role.title }}
                                </h3>
                                <div
                                    class="mt-1 flex flex-wrap gap-3 text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                                >
                                    <span>{{ role.location }}</span>
                                    <span>·</span>
                                    <span>{{ role.type }}</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-slate-400">
                            {{ role.description }}
                        </p>
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <span
                                v-for="tag in role.tags"
                                :key="tag"
                                class="rounded border border-[#232d42] bg-[#0f1422] px-2 py-0.5 text-[9px] font-black tracking-widest text-slate-400 uppercase"
                            >
                                {{ tag }}
                            </span>
                        </div>
                        <a
                            :href="`mailto:careers@betslip-pirates.com?subject=Application: ${role.title}`"
                            class="mt-4 inline-block rounded-lg border border-sky-500/30 bg-sky-500/5 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:border-sky-500 hover:bg-sky-500/10"
                        >
                            Apply via email →
                        </a>
                    </div>
                </div>
            </section>

            <!-- What to include -->
            <section class="mb-12">
                <h2
                    class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                >
                    When You Apply
                </h2>
                <ul
                    class="list-disc space-y-2 pl-5 text-sm leading-relaxed text-slate-400"
                >
                    <li>
                        Send your CV and a short note about why this role
                        interests you.
                    </li>
                    <li>
                        Include links to past work — GitHub, portfolio, shipped
                        products.
                    </li>
                    <li>
                        Tell us one thing you'd improve about Betslip Pirates.
                    </li>
                    <li>
                        Applications go to
                        <a
                            href="mailto:careers@betslip-pirates.com"
                            class="text-sky-400 hover:underline"
                            >careers@betslip-pirates.com</a
                        >.
                    </li>
                </ul>
            </section>

            <!-- CTA -->
            <section
                class="rounded-xl border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#111a30] p-6 text-center"
            >
                <h2
                    class="text-base font-black tracking-widest text-white uppercase"
                >
                    Don't see your role?
                </h2>
                <p
                    class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-400"
                >
                    If you're exceptional at something we clearly need, email us
                    anyway. We've hired people without a job posting before.
                </p>
                <a
                    href="mailto:careers@betslip-pirates.com"
                    class="mt-5 inline-block rounded-lg bg-[#ff8c00] px-6 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02]"
                >
                    Email us
                </a>
            </section>
        </div>
    </div>
</template>
