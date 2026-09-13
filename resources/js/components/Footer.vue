<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const year = new Date().getFullYear();

const columns = [
    {
        title: 'Platform',
        links: [
            { label: 'Fixtures', href: '/fixtures' },
            { label: 'Marketplace', href: '/marketplace' },
            { label: 'Leaderboard', href: '/leaderboard' },
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
    <footer
        class="mt-12 w-full border-t border-[#232d42] bg-[#070b14] font-sans text-[#93a3b8]"
    >
        <div class="mx-auto w-full max-w-[1200px] px-4 py-12 md:px-6 lg:px-0">
            <!-- TOP: brand + newsletter -->
            <div
                class="grid grid-cols-1 gap-8 border-b border-[#232d42] pb-10 lg:grid-cols-12"
            >
                <!-- BRAND -->
                <div class="lg:col-span-5">
                    <div class="flex items-center space-x-2">
                        <img
                            src="../../img/logo.png"
                            alt="Betslip Pirates Logo"
                            class="pointer-events-none h-10 w-auto object-contain select-none"
                        />
                        <span
                            class="text-sm font-black tracking-widest text-slate-200 uppercase"
                        >
                            Betslip Pirates
                        </span>
                    </div>

                    <p
                        class="mt-4 max-w-md text-xs leading-relaxed font-medium text-slate-400"
                    >
                        Discover, copy and share winning betslips from the
                        sharpest tipsters in the game. Built for punters who
                        take value seriously.
                    </p>

                    <!-- SOCIALS -->
                    <div class="mt-5 flex items-center gap-3">
                        <a
                            v-for="s in socials"
                            :key="s.name"
                            :href="s.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="s.name"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-[#232d42] bg-[#161c2a] text-slate-400 transition-colors hover:border-sky-500/50 hover:bg-[#1e2942] hover:text-sky-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                class="h-4 w-4"
                            >
                                <path :d="s.path" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- NEWSLETTER -->
                <div class="lg:col-span-7 lg:pl-8">
                    <h4
                        class="text-[10px] font-black tracking-widest text-slate-200 uppercase"
                    >
                        Join the Crew
                    </h4>
                    <p class="mt-2 text-xs font-medium text-slate-400">
                        Get weekly value picks, market insights and product
                        updates — no spam.
                    </p>

                    <form
                        class="mt-4 flex w-full max-w-md flex-col gap-2 sm:flex-row"
                        @submit.prevent="handleSubscribe"
                    >
                        <input
                            v-model="email"
                            type="email"
                            required
                            placeholder="you@example.com"
                            class="w-full flex-1 rounded-lg border border-[#232d42] bg-[#111622] px-4 py-2.5 text-xs font-semibold tracking-wide text-white placeholder-slate-500 transition-all duration-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                        />
                        <button
                            type="submit"
                            class="cursor-pointer rounded-lg bg-[#ff8c00] px-5 py-2.5 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95"
                        >
                            Subscribe
                        </button>
                    </form>

                    <p
                        v-if="subscribed"
                        class="mt-2 text-[10px] font-bold tracking-widest text-emerald-400 uppercase"
                    >
                        ✓ You're in. Check your inbox.
                    </p>
                </div>
            </div>

            <!-- MIDDLE: link columns -->
            <div
                class="grid grid-cols-2 gap-8 border-b border-[#232d42] py-10 md:grid-cols-4"
            >
                <div v-for="col in columns" :key="col.title">
                    <h5
                        class="text-[10px] font-black tracking-widest text-slate-200 uppercase"
                    >
                        {{ col.title }}
                    </h5>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in col.links" :key="link.label">
                            <Link
                                :href="link.href"
                                class="text-xs font-semibold text-slate-400 transition-colors hover:text-sky-400"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- RESPONSIBLE GAMING STRIP -->
            <div
                class="mt-8 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
            >
                <p
                    class="text-[10px] leading-relaxed font-semibold tracking-wide text-slate-500 uppercase"
                >
                    <span class="text-sky-400">18+</span> · Please gamble
                    responsibly. Betting involves risk — never stake more than
                    you can afford to lose. If you need support, visit
                    <a
                        href="https://www.begambleaware.org"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-slate-300 underline decoration-dotted hover:text-sky-400"
                    >
                        BeGambleAware.org
                    </a>
                    .
                </p>
            </div>

            <!-- BOTTOM: copyright + legal links -->
            <div
                class="mt-8 flex flex-col items-center justify-between gap-4 border-t border-[#232d42] pt-6 md:flex-row"
            >
                <p
                    class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                >
                    © {{ year }} Betslip Pirates. All rights reserved.
                </p>

                <div class="flex flex-wrap items-center gap-5">
                    <Link
                        href="/terms"
                        class="text-[10px] font-bold tracking-widest text-slate-500 uppercase transition-colors hover:text-sky-400"
                    >
                        Terms
                    </Link>
                    <Link
                        href="/privacy"
                        class="text-[10px] font-bold tracking-widest text-slate-500 uppercase transition-colors hover:text-sky-400"
                    >
                        Privacy
                    </Link>
                    <Link
                        href="/cookies"
                        class="text-[10px] font-bold tracking-widest text-slate-500 uppercase transition-colors hover:text-sky-400"
                    >
                        Cookies
                    </Link>
                    <span
                        class="text-[10px] font-bold tracking-widest text-slate-600 uppercase"
                    >
                        Made in Kenya
                    </span>
                </div>
            </div>
        </div>
    </footer>
</template>