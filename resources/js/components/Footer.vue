<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const year = new Date().getFullYear();

const version = computed(
    () => (page.props.app as { version?: string })?.version ?? 'dev',
);
const releasedAt = computed(
    () => (page.props.app as { released_at?: string })?.released_at ?? '',
);

const columns = [
    {
        title: 'Platform',
        links: [
            { label: 'Fixtures', href: '/fixtures' },
            { label: 'Marketplace', href: '/marketplace' },
            { label: 'Watchlist', href: '/watchlist' },
            { label: 'Leaderboard', href: '/leaderboard' },
            { label: 'Contests', href: '/contests/mine' },
            { label: 'How It Works', href: '/how-it-works' },
        ],
    },
    {
        title: 'Company',
        links: [
            { label: 'About Us', href: '/about' },
            { label: 'Contact', href: '/contact' },
            { label: 'Careers', href: '/careers' },
            { label: 'Press Kit', href: '/press' },
        ],
    },
    {
        title: 'Legal',
        links: [
            { label: 'Terms of Service', href: '/terms' },
            { label: 'Privacy Policy', href: '/privacy' },
            { label: 'Responsible Gaming', href: '/responsible-gaming' },
            { label: 'Cookie Policy', href: '/cookies' },
        ],
    },
    {
        title: 'Support',
        links: [
            { label: 'Help Center', href: '/help' },
            { label: 'FAQ', href: '/faq' },
            { label: 'Report', href: '/report' },
            { label: 'Community', href: '/community' },
        ],
    },
];

const socials = [
    {
        name: 'X',
        href: 'https://x.com',
        path: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
    },
    {
        name: 'Facebook',
        href: 'https://facebook.com',
        path: 'M22 12a10 10 0 1 0-11.563 9.875v-6.988H7.898V12h2.539V9.797c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.887h-2.33v6.988A10 10 0 0 0 22 12Z',
    },
    {
        name: 'Instagram',
        href: 'https://instagram.com',
        path: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069ZM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12s.014 3.668.072 4.948c.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24s3.668-.014 4.948-.072c4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948s-.014-3.667-.072-4.947c-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0Zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324ZM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881Z',
    },
    {
        name: 'Telegram',
        href: 'https://telegram.org',
        path: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z',
    },
];

const email = ref('');
const subscribed = ref(false);

const handleSubscribe = () => {
    if (!email.value) return;
    // TODO: wire up to a real endpoint
    subscribed.value = true;
    email.value = '';
    setTimeout(() => (subscribed.value = false), 3000);
};
</script>

<template>
    <footer class="relative mt-12 w-full overflow-hidden border-t border-white/[0.08] bg-[#070b14] font-sans text-slate-400">
        <!-- Same soft glows as the landing sections -->
        <div class="pointer-events-none absolute -top-24 left-[10%] h-64 w-64 rounded-full bg-sky-600/10 blur-[120px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -top-24 right-[10%] h-64 w-64 rounded-full bg-amber-500/10 blur-[120px]" aria-hidden="true"></div>

        <div class="relative mx-auto w-full max-w-[1200px] px-5 py-14 md:px-6 lg:px-8">
            <!-- TOP: brand + newsletter -->
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <!-- BRAND -->
                <div class="lg:col-span-5">
                    <img src="../../img/logo.png" alt="Fainali Logo" class="pointer-events-none h-11 w-auto rounded-xl object-contain select-none" />

                    <p class="mt-5 max-w-md text-base leading-relaxed text-slate-400">
                        Discover, copy and share winning betslips from the sharpest tipsters in the game. Built for punters who take value seriously.
                    </p>

                    <!-- SOCIALS -->
                    <div class="mt-6 flex items-center gap-2.5">
                        <a
                            v-for="s in socials"
                            :key="s.name"
                            :href="s.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="s.name"
                            class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-slate-400 transition-all duration-200 hover:border-amber-300/50 hover:bg-white/[0.08] hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                <path :d="s.path" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- NEWSLETTER -->
                <div class="lg:col-span-7 lg:pl-8">
                    <div class="rounded-3xl border border-white/[0.08] bg-white/[0.03] p-6 backdrop-blur-sm sm:p-8">
                        <h4 class="text-xl font-black tracking-tight text-white">Join the crew</h4>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-400">Get weekly value picks, market insights and product updates — no spam.</p>

                        <form class="mt-5 flex w-full flex-col gap-2.5 sm:flex-row" @submit.prevent="handleSubscribe">
                            <label for="footer-email" class="sr-only">Email address</label>
                            <input
                                id="footer-email"
                                v-model="email"
                                type="email"
                                inputmode="email"
                                autocomplete="email"
                                required
                                placeholder="you@example.com"
                                class="h-12 w-full flex-1 rounded-xl border border-white/10 bg-white/[0.04] px-4 text-base font-medium text-white placeholder-slate-500 transition-all focus:border-amber-300/60 focus:bg-white/[0.06] focus:ring-2 focus:ring-amber-300/20 focus:outline-none"
                            />
                            <button
                                type="submit"
                                class="h-12 shrink-0 cursor-pointer rounded-xl bg-amber-400 px-7 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all duration-200 hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:ring-offset-2 focus-visible:ring-offset-[#070b14] focus-visible:outline-none active:scale-[0.98]"
                            >
                                Subscribe
                            </button>
                        </form>

                        <p v-if="subscribed" role="status" class="mt-3 text-sm font-semibold text-emerald-300">✓ You're in. Check your inbox.</p>
                    </div>
                </div>
            </div>

            <!-- MIDDLE: link columns -->
            <div class="mt-12 grid grid-cols-2 gap-x-6 gap-y-10 border-t border-white/[0.08] pt-12 md:grid-cols-4">
                <nav v-for="col in columns" :key="col.title" :aria-label="col.title">
                    <h5 class="text-sm font-bold text-white">{{ col.title }}</h5>
                    <ul class="mt-3 space-y-0.5">
                        <li v-for="link in col.links" :key="link.label">
                            <Link
                                :href="link.href"
                                class="-mx-2 inline-flex min-h-11 items-center rounded-lg px-2 text-sm text-slate-400 transition-colors hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- RESPONSIBLE GAMING STRIP -->
            <div class="mt-12 flex items-start gap-4 rounded-2xl border border-amber-300/20 bg-amber-300/[0.06] p-5">
                <span class="flex h-9 shrink-0 items-center justify-center rounded-lg bg-amber-300 px-2.5 text-sm font-black text-[#070b14]">18+</span>
                <p class="text-sm leading-relaxed text-slate-300">
                    Please gamble responsibly. Betting involves risk — never stake more than you can afford to lose. If you need support, visit
                    <a
                        href="https://www.begambleaware.org"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold text-amber-200 underline decoration-dotted underline-offset-4 hover:text-amber-100"
                    >
                        BeGambleAware.org</a
                    >.
                </p>
            </div>

            <!-- REGISTERED BUSINESS -->
            <div class="mt-4 rounded-2xl border border-white/[0.08] bg-white/[0.03] p-5">
                <p class="text-sm font-bold text-white">Registered business</p>
                <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 md:grid-cols-5">
                    <div>
                        <dt class="text-xs text-slate-500">Business name</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-200">Wazo Tank</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Registration no.</dt>
                        <dd class="mt-0.5 font-mono text-sm font-semibold text-slate-200">BN-EZCBKQ26</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Registered</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-200">11 May 2021</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Jurisdiction</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-200">Republic of Kenya</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-500">Contact</dt>
                        <dd class="mt-0.5">
                            <a href="mailto:hello@fainali.com" class="block truncate text-sm font-semibold text-sky-300 underline decoration-dotted underline-offset-4 hover:text-sky-200">
                                hello@fainali.com
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- BOTTOM: copyright + legal links -->
            <div class="mt-10 flex flex-col items-center justify-between gap-5 border-t border-white/[0.08] pt-8 md:flex-row md:items-start">
                <div class="flex flex-col items-center gap-1 text-center md:items-start md:text-left">
                    <p class="text-sm text-slate-400">© {{ year }} Wazo Tank. All rights reserved.</p>
                    <p class="text-xs text-slate-500">Fainali is a trading name of Wazo Tank (BN-EZCBKQ26)</p>
                    <p class="text-xs text-slate-600 tabular-nums">
                        v{{ version }}
                        <span v-if="releasedAt" class="ml-2">{{ releasedAt }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-x-1 gap-y-1 md:justify-end">
                    <Link href="/terms" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-slate-400 transition-colors hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none">Terms</Link>
                    <Link href="/privacy" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-slate-400 transition-colors hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none">Privacy</Link>
                    <Link href="/cookies" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm text-slate-400 transition-colors hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none">Cookies</Link>
                    <span class="px-3 text-sm text-slate-600">Made in Kenya</span>
                </div>
            </div>
        </div>
    </footer>
</template>