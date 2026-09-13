<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const channels = [
    {
        icon: '💬',
        name: 'Telegram',
        description: 'Daily tips, market chatter, and instant product announcements.',
        members: '2,400+',
        href: 'https://t.me/',
        accent: 'sky',
    },
    {
        icon: '🐦',
        name: 'X / Twitter',
        description: 'Product updates, winner highlights, and community retweets.',
        members: '1,200+',
        href: 'https://x.com/',
        accent: 'slate',
    },
    {
        icon: '📸',
        name: 'Instagram',
        description: 'Behind-the-scenes, team moments, and visual betslip stories.',
        members: '800+',
        href: 'https://instagram.com/',
        accent: 'rose',
    },
    {
        icon: '📱',
        name: 'WhatsApp Channel',
        description: 'Broadcast-only updates. Zero chatter, all signal.',
        members: '1,500+',
        href: 'https://whatsapp.com/',
        accent: 'emerald',
    },
];

const guidelines = [
    {
        title: 'Be useful or be quiet',
        body: 'Post analysis, share wins, ask real questions. Don\'t spam links or DM strangers with unsolicited picks.',
    },
    {
        title: 'No promises of guaranteed returns',
        body: 'Betting involves risk. Anyone selling "100% sure wins" is lying. Report them.',
    },
    {
        title: 'Respect the culture',
        body: 'We are a Kenyan community first. Sheng, Swahili, and English are all welcome. Abuse of any kind is not.',
    },
    {
        title: 'Keep it clean',
        body: 'No politics, no tribalism, no NSFW content. There are places for those conversations — this is not one of them.',
    },
];

const events = [
    {
        date: 'Monthly',
        title: 'Community Predictions',
        description: 'Free-to-enter prediction contests with wallet credit prizes. Winners announced in the Telegram group.',
    },
    {
        date: 'Quarterly',
        title: 'Top Seller Spotlight',
        description: 'We feature our highest-ROI sellers in a community AMA. Ask them anything about their process.',
    },
    {
        date: 'Ad-hoc',
        title: 'Product Feedback Sessions',
        description: 'Join a small group of users to shape upcoming features. Applications open in Telegram.',
    },
];
</script>

<template>
    <Head title="Community" />

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
                    Community
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-400">
                    Betslip Pirates is bigger than a marketplace. It's a
                    network of Kenyan bettors who analyse the game and share
                    what they see. Join the conversation.
                </p>
            </div>

            <!-- Channels -->
            <section class="mb-12">
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Where to Find Us
                </h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <a
                        v-for="channel in channels"
                        :key="channel.name"
                        :href="channel.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group rounded-lg border border-[#232d42] bg-[#161c2a] p-5 transition-colors hover:border-sky-500/40"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-[#232d42] bg-[#0f1422] text-lg">
                                {{ channel.icon }}
                            </div>
                            <span class="text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                {{ channel.members }} members
                            </span>
                        </div>
                        <h3 class="mt-4 text-sm font-black tracking-widest text-white uppercase transition-colors group-hover:text-sky-400">
                            {{ channel.name }}
                        </h3>
                        <p class="mt-2 text-xs leading-relaxed text-slate-400">
                            {{ channel.description }}
                        </p>
                    </a>
                </div>
            </section>

            <!-- Guidelines -->
            <section class="mb-12">
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Community Guidelines
                </h2>
                <p class="mb-5 text-sm leading-relaxed text-slate-400">
                    Four rules. Break them and you'll be removed from the
                    channels without warning.
                </p>
                <div class="space-y-3">
                    <div
                        v-for="(rule, index) in guidelines"
                        :key="rule.title"
                        class="flex gap-4 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-sky-500/30 bg-sky-500/10 font-mono text-xs font-black text-sky-400">
                            {{ index + 1 }}
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-200">
                                {{ rule.title }}
                            </h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-400">
                                {{ rule.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Events -->
            <section class="mb-12">
                <h2 class="mb-4 text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Regular Events
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="event in events"
                        :key="event.title"
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                    >
                        <span class="rounded border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[9px] font-black tracking-widest text-amber-400 uppercase">
                            {{ event.date }}
                        </span>
                        <h3 class="mt-3 text-sm font-bold text-slate-200">
                            {{ event.title }}
                        </h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">
                            {{ event.description }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="rounded-xl border border-[#232d42] bg-gradient-to-br from-[#161c2a] to-[#111a30] p-6 text-center">
                <h2 class="text-base font-black tracking-widest text-white uppercase">
                    Ready to join?
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-400">
                    Start with Telegram — it's where the sharpest conversations
                    happen.
                </p>
                <a
                    href="https://t.me/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-5 inline-block rounded-lg bg-[#ff8c00] px-6 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02]"
                >
                    Open Telegram
                </a>
            </section>
        </div>
    </div>
</template>