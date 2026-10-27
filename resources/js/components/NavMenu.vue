<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const isOpen = ref(false);
const container = ref(null);
const modalElement = ref(null);
const closeButton = ref(null);

const open = () => (isOpen.value = true);
const close = () => (isOpen.value = false);

watch(isOpen, async (v) => {
    document.body.classList.toggle('overflow-hidden', v);
    // Move focus into the drawer so keyboard and screen-reader users land inside it.
    if (v) {
        await nextTick();
        closeButton.value?.focus();
    }
});

const handleClickOutside = (e) => {
    if (
        isOpen.value &&
        modalElement.value &&
        !modalElement.value.contains(e.target) &&
        container.value &&
        !container.value.contains(e.target)
    ) {
        close();
    }
};

const handleKeydown = (e) => {
    if (e.key === 'Escape' && isOpen.value) close();
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});
onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
    document.body.classList.remove('overflow-hidden');
});

// Each link is a name plus a one-line hint (these used to be one "NAME - hint" string).
const primaryLinks = [
    { label: 'Portfolio', hint: 'Your reputation', href: '/portfolio' },
    { label: 'Marketplace', hint: 'Browse betslips', href: '/marketplace' },
    { label: 'Fixtures', hint: 'Create a betslip', href: '/fixtures' },
    { label: 'Watchlist', hint: 'Monitor betslips', href: '/watchlist' },
    { label: 'Contests', hint: 'View contests', href: '/contests/mine' },
    { label: 'Jackpots', hint: 'View jackpots', href: '/jackpots' },
    { label: 'Leaderboard', hint: 'View tipster rankings', href: '/leaderboard' },
];

const accountLinks = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Notifications', href: '/notifications' },
    { label: 'Referrals', href: '/refer' },
    { label: 'Settings', href: '/settings' },
];

const guestLinks = [
    { label: 'Marketplace', href: '/marketplace' },
    { label: 'How it works', href: '/how-it-works' },
    { label: 'Leaderboard', href: '/leaderboard' },
    { label: 'About', href: '/about' },
    { label: 'Help', href: '/help' },
];

// Uses the Inertia page URL, so it stays correct after client-side navigation.
const isCurrent = (href) => {
    const path = (page.url || '').split('?')[0].split('#')[0];
    return path === href || path.startsWith(href + '/');
};

const logout = () => {
    close();
    router.post('/logout');
};

// Full class strings so Tailwind can see them
const ROW =
    'group flex items-center justify-between gap-3 rounded-2xl border px-4 transition-all duration-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-[0.98]';
const ROW_ON = 'border-amber-300/40 bg-amber-300/10';
const ROW_OFF = 'border-transparent hover:border-white/10 hover:bg-white/[0.05]';
</script>

<template>
    <div ref="container">
        <!-- Menu trigger -->
        <button
            type="button"
            @click="open"
            :aria-expanded="isOpen"
            aria-haspopup="dialog"
            aria-label="Open navigation"
            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-slate-200 transition-all duration-200 hover:border-amber-300/50 hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>

        <Teleport to="body">
            <div v-if="isOpen" class="fixed inset-0 z-[9999]" @click.self="close">
                <!-- Backdrop -->
                <div class="animate-backdrop absolute inset-0 bg-black/70 backdrop-blur-sm" @click="close"></div>

                <!-- Drawer -->
                <div
                    ref="modalElement"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Navigation"
                    class="animate-drawer absolute inset-y-0 right-0 flex w-full max-w-sm flex-col border-l border-white/10 bg-[#0d1424] shadow-[-30px_0_80px_-20px_rgba(0,0,0,0.9)]"
                >
                    <!-- Header -->
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/[0.07] py-3 pr-3 pl-5 pt-[max(0.75rem,env(safe-area-inset-top))]">
                        <h2 class="text-base font-bold text-white">Menu</h2>
                        <button
                            ref="closeButton"
                            type="button"
                            @click="close"
                            aria-label="Close navigation"
                            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Scrolling body -->
                    <div class="flex-1 overflow-y-auto px-4 py-4">
                        <!-- Authenticated -->
                        <template v-if="user">
                            <!-- User strip -->
                            <div class="mb-5 flex items-center gap-3.5 rounded-2xl border border-white/[0.07] bg-white/[0.03] p-3.5">
                                <img
                                    :src="user.avatar || `https://api.dicebear.com/10.x/lorelei-neutral/svg?seed=${user.code || user.id}`"
                                    :alt="user.name"
                                    class="h-12 w-12 shrink-0 rounded-full bg-[#111622] object-cover ring-2 ring-white/10"
                                />
                                <div class="min-w-0">
                                    <p class="truncate text-base font-bold text-white">{{ user.name }}</p>
                                    <p class="truncate text-sm text-slate-500">Code {{ user.code }}</p>
                                </div>
                            </div>

                            <!-- Explore -->
                            <p class="mb-2 px-2 text-xs font-semibold text-slate-500">Explore</p>
                            <nav class="space-y-1" aria-label="Explore">
                                <Link
                                    v-for="link in primaryLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    @click="close"
                                    :aria-current="isCurrent(link.href) ? 'page' : undefined"
                                    :class="[ROW, 'min-h-14 py-2.5', isCurrent(link.href) ? ROW_ON : ROW_OFF]"
                                >
                                    <span class="min-w-0">
                                        <span class="block text-base font-bold" :class="isCurrent(link.href) ? 'text-amber-200' : 'text-white'">{{ link.label }}</span>
                                        <span class="block text-sm text-slate-500">{{ link.hint }}</span>
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 shrink-0 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-amber-300" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </Link>
                            </nav>

                            <!-- Account -->
                            <p class="mt-6 mb-2 px-2 text-xs font-semibold text-slate-500">Account</p>
                            <nav class="space-y-1" aria-label="Account">
                                <Link
                                    v-for="link in accountLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    @click="close"
                                    :aria-current="isCurrent(link.href) ? 'page' : undefined"
                                    :class="[ROW, 'min-h-12 py-2', isCurrent(link.href) ? ROW_ON : ROW_OFF]"
                                >
                                    <span class="text-base font-semibold" :class="isCurrent(link.href) ? 'text-amber-200' : 'text-slate-200'">{{ link.label }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 shrink-0 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-amber-300" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </Link>
                            </nav>
                        </template>

                        <!-- Guest -->
                        <template v-else>
                            <nav class="space-y-1" aria-label="Site">
                                <Link
                                    v-for="link in guestLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    @click="close"
                                    :aria-current="isCurrent(link.href) ? 'page' : undefined"
                                    :class="[ROW, 'min-h-14 py-2.5', isCurrent(link.href) ? ROW_ON : ROW_OFF]"
                                >
                                    <span class="text-base font-bold" :class="isCurrent(link.href) ? 'text-amber-200' : 'text-white'">{{ link.label }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 shrink-0 text-slate-600 transition-all duration-200 group-hover:translate-x-0.5 group-hover:text-amber-300" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </Link>
                            </nav>
                        </template>
                    </div>

                    <!-- Pinned footer: the one action people reach for, always in thumb range -->
                    <div class="shrink-0 border-t border-white/[0.07] p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                        <button
                            v-if="user"
                            type="button"
                            @click="logout"
                            class="flex min-h-12 w-full cursor-pointer items-center justify-center rounded-xl border border-rose-400/30 bg-rose-400/5 text-sm font-bold text-rose-300 transition-all duration-200 hover:border-rose-300/60 hover:bg-rose-400/10 focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:outline-none active:scale-[0.98]"
                        >
                            Log out
                        </button>

                        <div v-else class="grid gap-2">
                            <Link
                                href="/register"
                                @click="close"
                                class="flex min-h-12 items-center justify-center rounded-xl bg-amber-400 text-sm font-black text-[#070b14] shadow-[0_12px_32px_-12px_rgba(251,191,36,0.8)] transition-all duration-200 hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]"
                            >
                                Register
                            </Link>
                            <Link
                                href="/login"
                                @click="close"
                                class="flex min-h-12 items-center justify-center rounded-xl border border-white/15 bg-white/[0.04] text-sm font-bold text-slate-200 transition-all duration-200 hover:border-white/30 hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none active:scale-[0.98]"
                            >
                                Log in
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes backdropIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
@keyframes drawerIn {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}
.animate-backdrop {
    animation: backdropIn 0.2s ease-out forwards;
}
.animate-drawer {
    animation: drawerIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@media (prefers-reduced-motion: reduce) {
    .animate-backdrop,
    .animate-drawer {
        animation: none;
    }
}
</style>