<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import NotificationBell from '@/components/NotificationBell.vue';
import NavMenu from '@/components/NavMenu.vue';
const page = usePage();

// ─── Flash message handling ────────────────────────────────
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const notifications = computed(() => page.props.auth.notifications);
const visibleSuccess = ref<string | null>(null);
const visibleError = ref<string | null>(null);

let successTimer: ReturnType<typeof setTimeout> | null = null;
let errorTimer: ReturnType<typeof setTimeout> | null = null;

// ─── Show helpers ──────────────────────────────────────────
const showSuccess = (msg: string) => {
    if (successTimer) {
        clearTimeout(successTimer);
        successTimer = null;
    }
    visibleSuccess.value = msg;
    successTimer = setTimeout(() => {
        visibleSuccess.value = null;
        successTimer = null;
    }, 4000);
};

const showError = (msg: string) => {
    if (errorTimer) {
        clearTimeout(errorTimer);
        errorTimer = null;
    }
    visibleError.value = msg;
    errorTimer = setTimeout(() => {
        visibleError.value = null;
        errorTimer = null;
    }, 5000);
};

// ─── Dismiss helpers ───────────────────────────────────────
const dismissSuccess = () => {
    if (successTimer) {
        clearTimeout(successTimer);
        successTimer = null;
    }
    visibleSuccess.value = null;
};

const dismissError = () => {
    if (errorTimer) {
        clearTimeout(errorTimer);
        errorTimer = null;
    }
    visibleError.value = null;
};

// ─── Watch the flash and route through the helpers ─────────
watch(
    flashSuccess,
    (msg) => {
        if (msg) showSuccess(msg);
    },
    { immediate: true },
);

watch(
    flashError,
    (msg) => {
        if (msg) showError(msg);
    },
    { immediate: true },
);
</script>

<template>
    <!-- Keyboard users can jump past the nav -->
    <a href="#main"
        class="sr-only z-[10000] rounded-xl bg-amber-400 px-4 py-3 text-sm font-bold text-[#070b14] focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        Skip to content
    </a>

    <header class="fixed inset-x-0 top-0 z-50 px-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <nav class="mx-auto flex w-full max-w-[75rem] items-center justify-between gap-3 rounded-2xl border border-white/[0.08] bg-[#0b1220]/80 py-2.5 pr-2.5 pl-4 shadow-[0_20px_50px_-20px_rgba(0,0,0,0.9)] backdrop-blur-xl"
            aria-label="Main">
            <!-- BRAND -->
            <Link href="/"
                class="flex min-h-11 items-center gap-2.5 rounded-xl pr-2 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none">
                <img src="../../img/logo.png" alt="Fainali Logo"
                    class="pointer-events-none h-9 w-auto object-contain select-none rounded-xl" />

            </Link>

            <!-- NAV ACTIONS -->
            <div class="flex items-center gap-2">
                <template v-if="$page.props.auth.user">
                    <!-- Balance pill: the number people check most, one tap from the wallet -->
                    <Link :href="dashboard()"
                        class="flex min-h-11 items-center gap-2 rounded-xl border border-emerald-400/20 bg-emerald-400/[0.07] px-3.5 transition-all duration-200 hover:border-emerald-300/50 hover:bg-emerald-400/10 focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:outline-none active:scale-[0.98]"
                        title="Available balance">
                        <span class="hidden text-xs text-slate-400 sm:inline">Balance</span>
                        <span class="text-sm font-black text-emerald-300 tabular-nums">
                            KES
                            {{
                                Number(
                                    $page.props.auth.balance || 0,
                                ).toLocaleString(undefined, {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0,
                                })
                            }}
                        </span>
                    </Link>

                    <NotificationBell :initial-unread-count="notifications.unread_count" />

                    <NavMenu />
                </template>
                <template v-else>
                    <Link :href="login()"
                        class="inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-bold text-slate-300 transition-colors hover:bg-white/[0.06] hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none">
                        Log in
                    </Link>
                    <Link :href="register()"
                        class="inline-flex min-h-11 items-center rounded-xl bg-amber-400 px-5 text-sm font-black text-[#070b14] shadow-[0_10px_30px_-12px_rgba(251,191,36,0.8)] transition-all duration-200 hover:bg-amber-300 focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:outline-none active:scale-[0.98]">
                        Register
                    </Link>
                </template>
            </div>
        </nav>
    </header>

    <main id="main" class="bg-[#070b14] pt-[calc(5rem+env(safe-area-inset-top,0px))]">
        <slot />
    </main>

    <!-- ─── Global Toast Container ──────────────────────────────
         Phones: under the nav, so it never covers the bottom tab bar the dashboards use.
         Larger screens: bottom-right. -->
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-[calc(5rem+env(safe-area-inset-top,0px))] z-[9999] flex flex-col items-center gap-2 px-4 sm:top-auto sm:bottom-0 sm:items-end sm:p-6">
            <!-- Success Toast -->
            <Transition enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="-translate-y-3 opacity-0 sm:translate-y-8" enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in" leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-3 opacity-0 sm:translate-y-8">
                <div v-if="visibleSuccess" :key="visibleSuccess" role="status"
                    class="pointer-events-auto relative flex w-full max-w-sm items-start gap-3 overflow-hidden rounded-2xl border border-emerald-400/30 bg-[#0d1424]/95 p-4 shadow-[0_20px_50px_-15px_rgba(0,0,0,0.9)] backdrop-blur-xl">
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 text-emerald-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3"
                            stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>

                    <p class="flex-1 pt-0.5 text-sm leading-relaxed font-medium text-slate-100">{{ visibleSuccess }}</p>

                    <button type="button" @click="dismissSuccess"
                        class="-mt-1 -mr-1.5 flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:outline-none"
                        aria-label="Dismiss">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Time left (matches the 4s timer in the script) -->
                    <span class="toast-timer absolute inset-x-0 bottom-0 h-0.5 origin-left bg-emerald-400/70"
                        style="animation-duration: 4s" aria-hidden="true"></span>
                </div>
            </Transition>

            <!-- Error Toast -->
            <Transition enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="-translate-y-3 opacity-0 sm:translate-y-8" enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in" leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-3 opacity-0 sm:translate-y-8">
                <div v-if="visibleError" :key="visibleError" role="alert"
                    class="pointer-events-auto relative flex w-full max-w-sm items-start gap-3 overflow-hidden rounded-2xl border border-rose-400/30 bg-[#0d1424]/95 p-4 shadow-[0_20px_50px_-15px_rgba(0,0,0,0.9)] backdrop-blur-xl">
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-400/15 text-rose-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3"
                            stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </span>

                    <p class="flex-1 pt-0.5 text-sm leading-relaxed font-medium text-slate-100">{{ visibleError }}</p>

                    <button type="button" @click="dismissError"
                        class="-mt-1 -mr-1.5 flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:outline-none"
                        aria-label="Dismiss">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Time left (matches the 5s timer in the script) -->
                    <span class="toast-timer absolute inset-x-0 bottom-0 h-0.5 origin-left bg-rose-400/70"
                        style="animation-duration: 5s" aria-hidden="true"></span>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes toastTimer {
    from {
        transform: scaleX(1);
    }

    to {
        transform: scaleX(0);
    }
}

.toast-timer {
    animation-name: toastTimer;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
}

@media (prefers-reduced-motion: reduce) {
    .toast-timer {
        display: none;
    }
}
</style>