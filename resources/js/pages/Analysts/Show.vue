<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AnalystPickFull from '@/components/AnalystPickFull.vue';
import AnalystRecordStrip from '@/components/AnalystRecordStrip.vue';

const props = defineProps<{
    pick: any;
    related: any[];
    record: any;
    meta: { og_title: string; og_description: string; og_image: string; canonical: string };
}>();

// ─────────────────────────────────────────────────────────────
// Social channels — PLACEHOLDER URLs.
// Swap the href values here when the real handles exist. Nothing
// else in the file needs to change.
// ─────────────────────────────────────────────────────────────
const CHANNELS = {
    whatsappChannel: {
        label: 'WhatsApp Channel',
        href: 'https://whatsapp.com/channel/PLACEHOLDER',
        icon: 'whatsapp',
        tone: 'emerald',
        blurb: 'Every new pick, delivered to your phone.',
    },
    telegram: {
        label: 'Telegram',
        href: 'https://t.me/PLACEHOLDER',
        icon: 'telegram',
        tone: 'sky',
        blurb: 'Instant alerts when a pick goes live.',
    },
    twitter: {
        label: 'X (Twitter)',
        href: 'https://x.com/PLACEHOLDER',
        icon: 'twitter',
        tone: 'slate',
        blurb: 'Picks and post-match notes in your feed.',
    },
    facebook: {
        label: 'Facebook',
        href: 'https://facebook.com/PLACEHOLDER',
        icon: 'facebook',
        tone: 'blue',
        blurb: 'Join the page for weekly pick threads.',
    },
} as const;

type ChannelKey = keyof typeof CHANNELS;

// ─────────────────────────────────────────────────────────────
// SEO / structured data
// ─────────────────────────────────────────────────────────────
function jsonLd(): string {
    const p = props.pick;
    if (!p) {
        return JSON.stringify({ '@context': 'https://schema.org', '@type': 'Article' });
    }
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: p.analyst_title ?? 'Analyst pick',
        datePublished: p.analyst_published_at ?? undefined,
        dateModified: p.settled_at ?? p.analyst_published_at ?? undefined,
        author: {
            '@type': 'Person',
            name: p.analyst?.name ?? 'Fainali Analyst',
            url: p.analyst?.slug ? `https://fainali.co.ke/analysts/${p.analyst.slug}` : undefined,
        },
        publisher: { '@type': 'Organization', name: 'Fainali', url: 'https://fainali.co.ke' },
        image: props.meta?.og_image,
        articleSection: 'Sports Predictions',
    });
}

// ─────────────────────────────────────────────────────────────
// Share (this pick specifically) — different from "follow
// the channel". One is "here's this pick", the other is "here's
// everywhere we publish".
// ─────────────────────────────────────────────────────────────
const shareUrl = computed(() =>
    typeof window !== 'undefined' ? window.location.href : props.meta.canonical,
);

const shareText = computed(() => {
    const title = props.pick?.analyst_title ?? 'Analyst pick';
    const odds = Number(props.pick?.total_odds ?? 0).toFixed(2);
    const name = props.pick?.analyst?.name ?? 'Fainali analyst';
    return `${title} @ ${odds}× — ${name} on Fainali`;
});

const shareLinks = computed(() => ({
    whatsapp: `https://wa.me/?text=${encodeURIComponent(shareText.value + '\n' + shareUrl.value)}`,
    telegram: `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareText.value)}`,
    twitter:  `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText.value)}&url=${encodeURIComponent(shareUrl.value)}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`,
}));

const copyState = ref<'idle' | 'copied' | 'failed'>('idle');

async function copyLink() {
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        copyState.value = 'copied';
        setTimeout(() => (copyState.value = 'idle'), 2000);
    } catch {
        copyState.value = 'failed';
        setTimeout(() => (copyState.value = 'idle'), 2000);
    }
}
</script>

<template>
    <Head>
        <title>{{ meta.og_title }}</title>
        <meta name="description" :content="meta.og_description" />
        <link rel="canonical" :href="meta.canonical" />
        <meta property="og:title" :content="meta.og_title" />
        <meta property="og:description" :content="meta.og_description" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="meta.canonical" />
        <meta property="og:image" :content="meta.og_image" />
        <meta name="twitter:card" content="summary_large_image" />
        <component :is="'script'" type="application/ld+json" v-html="jsonLd()" />
    </Head>

    <div class="min-h-screen bg-[#0a1628] px-4 py-10 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <nav class="flex flex-wrap items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-500">
                <Link href="/" class="hover:text-sky-400">Home</Link>
                <span>›</span>
                <Link href="/analyst-picks" class="hover:text-sky-400">Analyst picks</Link>
            </nav>

            <AnalystRecordStrip v-if="record" :record="record" class="mt-4" variant="compact" />

            <!-- main pick content -->
            <div class="mt-8">
                <AnalystPickFull :pick="pick" :related="related ?? []" :record="record" />
            </div>

            <!-- ────────────────────────────────────────────────
                 SHARE THIS PICK
                 Four channels. Native share URLs for each platform,
                 plus a copy-link button. Sits below the pick where
                 readers land after scrolling to the end.
                 ──────────────────────────────────────────────── -->
            <section class="mt-10 rounded-xl border border-[#232d42] bg-[#0f1422] p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-400">
                        Share this pick
                    </p>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">
                        {{ pick?.analyst?.name ?? 'Analyst pick' }}
                    </p>
                </div>
                <p class="mt-1 text-sm text-slate-400">
                    Send it to a friend or post it on your feed.
                </p>

                <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <a :href="shareLinks.whatsapp" target="_blank" rel="noopener"
                        class="flex items-center justify-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2.5 text-[10px] font-black uppercase tracking-widest text-emerald-400 hover:bg-emerald-500/20">
                        WhatsApp
                    </a>
                    <a :href="shareLinks.telegram" target="_blank" rel="noopener"
                        class="flex items-center justify-center gap-2 rounded-lg border border-sky-500/30 bg-sky-500/10 px-3 py-2.5 text-[10px] font-black uppercase tracking-widest text-sky-400 hover:bg-sky-500/20">
                        Telegram
                    </a>
                    <a :href="shareLinks.twitter" target="_blank" rel="noopener"
                        class="flex items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#111622] px-3 py-2.5 text-[10px] font-black uppercase tracking-widest text-slate-300 hover:border-slate-500/50">
                        X / Twitter
                    </a>
                    <a :href="shareLinks.facebook" target="_blank" rel="noopener"
                        class="flex items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#111622] px-3 py-2.5 text-[10px] font-black uppercase tracking-widest text-slate-300 hover:border-blue-500/50">
                        Facebook
                    </a>
                </div>

                <button
                    type="button"
                    @click="copyLink"
                    class="mt-3 w-full rounded-lg border border-[#232d42] py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:border-sky-500/50 hover:text-slate-200"
                >
                    {{ copyState === 'copied' ? 'Copied to clipboard' : (copyState === 'failed' ? 'Copy failed — long-press the URL' : 'Copy link') }}
                </button>
            </section>

            <!-- ────────────────────────────────────────────────
                 FOLLOW THE CHANNELS
                 Placeholder URLs from the CHANNELS map at the top
                 of this file. Change the href values there and
                 every instance updates.
                 ──────────────────────────────────────────────── -->
            <section class="mt-6">
                <div class="flex items-baseline justify-between gap-2">
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-400">
                        Follow the picks
                    </p>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">
                        Wherever you read
                    </p>
                </div>
                <p class="mt-1 text-sm text-slate-400">
                    Every pick lands on our channels the moment it publishes.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <a
                        v-for="(channel, key) in CHANNELS"
                        :key="key"
                        :href="channel.href"
                        target="_blank"
                        rel="noopener"
                        class="group flex items-start gap-3 rounded-xl border border-[#232d42] bg-[#111622] p-4 transition-colors"
                        :class="{
                            'hover:border-emerald-500/50': channel.tone === 'emerald',
                            'hover:border-sky-500/50':     channel.tone === 'sky',
                            'hover:border-slate-500/50':   channel.tone === 'slate',
                            'hover:border-blue-500/50':    channel.tone === 'blue',
                        }"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border"
                            :class="{
                                'border-emerald-500/30 bg-emerald-500/10 text-emerald-400': channel.tone === 'emerald',
                                'border-sky-500/30 bg-sky-500/10 text-sky-400':             channel.tone === 'sky',
                                'border-[#232d42] bg-[#0a1628] text-slate-300':              channel.tone === 'slate',
                                'border-blue-500/30 bg-blue-500/10 text-blue-400':          channel.tone === 'blue',
                            }"
                        >
                            <!-- icons -->
                            <svg v-if="channel.icon === 'whatsapp'" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <svg v-else-if="channel.icon === 'telegram'" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                <path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                            <svg v-else-if="channel.icon === 'twitter'" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            <svg v-else viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-white">{{ channel.label }}</p>
                            <p class="mt-0.5 text-[11px] leading-relaxed text-slate-400">{{ channel.blurb }}</p>
                        </div>
                        <span class="self-center text-[10px] font-black uppercase tracking-widest text-slate-500 transition-colors"
                            :class="{
                                'group-hover:text-emerald-400': channel.tone === 'emerald',
                                'group-hover:text-sky-400':     channel.tone === 'sky',
                                'group-hover:text-slate-300':   channel.tone === 'slate',
                                'group-hover:text-blue-400':    channel.tone === 'blue',
                            }">
                            Open
                        </span>
                    </a>
                </div>

                <p class="mt-3 text-[10px] font-medium uppercase tracking-widest text-slate-600">
                    Links are placeholders until the channels are live.
                </p>
            </section>

            <!-- ────────────────────────────────────────────────
                 BROWSE THE ARCHIVE
                 Last CTA — the "what next" after following.
                 ──────────────────────────────────────────────── -->
            <Link
                href="/analyst-picks"
                class="mt-6 flex items-center justify-between gap-3 rounded-xl border border-[#232d42] bg-[#111622] p-5 transition-colors hover:border-sky-500/50 group"
            >
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-sky-400">
                        Full archive
                    </p>
                    <p class="mt-2 text-sm font-bold text-white">
                        Every pick, every result.
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-400">
                        Wins and losses, ROI, and the reasoning behind every call.
                    </p>
                </div>
                <span class="shrink-0 text-[10px] font-black uppercase tracking-widest text-slate-300 group-hover:text-sky-400">
                    Browse →
                </span>
            </Link>
        </div>
    </div>
</template>