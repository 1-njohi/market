<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const isOpen = ref(false);
const container = ref(null);
const modalElement = ref(null);

const open = () => (isOpen.value = true);
const close = () => (isOpen.value = false);

watch(isOpen, (v) => {
    document.body.classList.toggle('overflow-hidden', v);
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

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.body.classList.remove('overflow-hidden');
});

const primaryLinks = [
    { label: 'Marketplace', href: '/marketplace' },
    { label: 'Watchlist', href: '/watchlist' },
    { label: 'Leaderboard', href: '/leaderboard' },
];

const accountLinks = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Notifications', href: '/notifications' },
    { label: 'Referals', href: '/refer' },
    { label: 'Settings', href: '/settings' },
];

const guestLinks = [
    { label: 'Marketplace', href: '/marketplace' },
    { label: 'How It Works', href: '/how-it-works' },
    { label: 'Leaderboard', href: '/leaderboard' },
    { label: 'About', href: '/about' },
    { label: 'Help', href: '/help' },
];

const isCurrent = (href) => {
    if (typeof window === 'undefined') return false;
    const path = window.location.pathname;
    return path === href || path.startsWith(href + '/');
};

const logout = () => {
    close();
    router.post('/logout');
};
</script>

<template>
    <div ref="container">
        <!-- Hamburger trigger -->
        <button
            type="button"
            @click="open"
            class="flex h-9 w-9 items-center justify-center rounded border border-[#232d42] bg-[#111622] text-slate-300 transition-colors hover:border-sky-500/40 hover:text-sky-400"
            aria-label="Open navigation"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2.5"
                stroke="currentColor"
                class="h-4 w-4"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"
                />
            </svg>
        </button>

        <Teleport to="body">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="close"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                ></div>

                <!-- Modal -->
                <div
                    ref="modalElement"
                    class="animate-fade-in relative w-full max-w-md rounded border border-gray-800 bg-[#0f1422] p-4 shadow-[0_20px_50px_rgba(7,11,20,0.9),0_0_25px_rgba(14,165,233,0.15)] sm:w-96"
                >
                    <!-- Header -->
                    <div
                        class="mb-4 flex items-center justify-between border-b border-gray-800 pb-3"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            Navigation
                        </span>
                        <button
                            type="button"
                            @click="close"
                            class="cursor-pointer font-mono text-xs text-slate-500 hover:text-slate-300"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Authenticated -->
                    <template v-if="user">
                        <!-- User strip -->
                        <div
                            class="mb-4 flex items-center gap-3 rounded border border-[#232d42] bg-[#070b14] p-3"
                        >
                            <img
                                :src="
                                    user.avatar ||
                                    `https://api.dicebear.com/10.x/lorelei-neutral/svg?seed=${user.code || user.id}`
                                "
                                :alt="user.name"
                                class="h-9 w-9 flex-shrink-0 rounded-full border border-sky-500/30 object-cover"
                            />
                            <div class="min-w-0">
                                <p
                                    class="truncate font-mono text-xs font-black tracking-wide text-slate-200 uppercase"
                                >
                                    {{ user.name }}
                                </p>
                                <p
                                    class="truncate font-mono text-[10px] text-slate-500"
                                >
                                    Code: {{ user.code }}
                                </p>
                            </div>
                        </div>

                        <!-- Primary links -->
                        <nav class="space-y-1">
                            <Link
                                v-for="link in primaryLinks"
                                :key="link.href"
                                :href="link.href"
                                @click="close"
                                :class="[
                                    'flex items-center justify-between rounded border px-3 py-2.5 text-[11px] font-black tracking-widest uppercase transition-all',
                                    isCurrent(link.href)
                                        ? 'border-sky-500/40 bg-sky-500/10 text-sky-300'
                                        : 'border-transparent text-slate-400 hover:border-sky-500/20 hover:bg-sky-500/5 hover:text-sky-400',
                                ]"
                            >
                                {{ link.label }}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-3 w-3 opacity-60"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m8.25 4.5 7.5 7.5-7.5 7.5"
                                    />
                                </svg>
                            </Link>
                        </nav>

                        <!-- Divider -->
                        <div class="my-3 border-t border-[#232d42]/60"></div>

                        <!-- Account links -->
                        <nav class="space-y-1">
                            <Link
                                v-for="link in accountLinks"
                                :key="link.href"
                                :href="link.href"
                                @click="close"
                                :class="[
                                    'flex items-center justify-between rounded border px-3 py-2.5 text-[11px] font-black tracking-widest uppercase transition-all',
                                    isCurrent(link.href)
                                        ? 'border-sky-500/40 bg-sky-500/10 text-sky-300'
                                        : 'border-transparent text-slate-400 hover:border-sky-500/20 hover:bg-sky-500/5 hover:text-sky-400',
                                ]"
                            >
                                {{ link.label }}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-3 w-3 opacity-60"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m8.25 4.5 7.5 7.5-7.5 7.5"
                                    />
                                </svg>
                            </Link>
                        </nav>

                        <!-- Logout -->
                        <button
                            type="button"
                            @click="logout"
                            class="mt-4 flex w-full cursor-pointer items-center justify-center rounded border border-rose-500/30 bg-transparent py-2.5 text-[10px] font-black tracking-widest text-rose-400 uppercase transition-all hover:border-rose-500 hover:bg-rose-500/10"
                        >
                            Log Out
                        </button>
                    </template>

                    <!-- Guest -->
                    <template v-else>
                        <nav class="space-y-1">
                            <Link
                                v-for="link in guestLinks"
                                :key="link.href"
                                :href="link.href"
                                @click="close"
                                :class="[
                                    'flex items-center justify-between rounded border px-3 py-2.5 text-[11px] font-black tracking-widest uppercase transition-all',
                                    isCurrent(link.href)
                                        ? 'border-sky-500/40 bg-sky-500/10 text-sky-300'
                                        : 'border-transparent text-slate-400 hover:border-sky-500/20 hover:bg-sky-500/5 hover:text-sky-400',
                                ]"
                            >
                                {{ link.label }}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-3 w-3 opacity-60"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m8.25 4.5 7.5 7.5-7.5 7.5"
                                    />
                                </svg>
                            </Link>
                        </nav>

                        <!-- Auth actions -->
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <Link
                                href="/login"
                                @click="close"
                                class="flex items-center justify-center rounded border border-[#232d42] bg-transparent py-2.5 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-all hover:border-sky-500/40 hover:text-sky-400"
                            >
                                Log In
                            </Link>
                            <Link
                                href="/register"
                                @click="close"
                                class="flex items-center justify-center rounded border border-amber-500 bg-amber-500 py-2.5 text-[10px] font-black tracking-widest text-[#070b14] uppercase transition-all hover:bg-amber-400"
                            >
                                Register
                            </Link>
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.animate-fade-in {
    animation: fadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
