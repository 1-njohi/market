<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import LogoutButton from './LogoutButton.vue';

const page = usePage();

// ─── Flash message handling ────────────────────────────────
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

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
    <header
        class="[#070b14] fixed top-0 right-0 left-0 z-50 mx-auto flex w-full max-w-[1200px] justify-center bg-[#070b14] transparent text-sm not-has-[nav]:hidden md:px-6 lg:px-0"
    >
        <nav
            class="flex w-[95%] items-center justify-between rounded-lg border border-[#232d42] bg-[#161c2a] p-4 shadow-lg md:w-[75rem]"
        >
            <!-- BRAND -->
            <Link href="/" class="flex items-center space-x-2 text-sky-400">
                <img
                    src="../../img/logo.png"
                    alt="Betslip Pirates Logo"
                    class="pointer-events-none h-10 w-auto object-contain select-none"
                />
                <span
                    class="hidden text-sm font-black tracking-widest text-slate-200 uppercase md:block"
                >
                    BETSLIP PIRATES
                </span>
            </Link>

            <!-- NAV ACTIONS -->
            <div class="flex items-center gap-3">
                <template v-if="$page.props.auth.user">
                    <Link
                        :href="dashboard()"
                        class="inline-block rounded border border-transparent bg-[#242f48] px-4 py-2 text-xs font-bold tracking-wide text-sky-400 uppercase transition-colors hover:bg-[#2e3c5c]"
                    >
                        Dashboard
                    </Link>

                    <!-- Settings gear icon -->
                    <Link
                        href="/settings"
                        class="flex h-9 w-9 items-center justify-center rounded border border-transparent text-slate-400 transition-colors hover:bg-[#242f48] hover:text-white"
                        title="Settings"
                        aria-label="Settings"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                            />
                        </svg>
                    </Link>
                    <LogoutButton />
                </template>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded px-4 py-2 text-xs font-bold tracking-wide text-slate-400 uppercase transition-colors hover:text-slate-200"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="inline-block rounded border border-transparent bg-[#242f48] px-4 py-2 text-xs font-bold tracking-wide text-slate-200 uppercase transition-colors hover:bg-[#2e3c5c]"
                    >
                        Register
                    </Link>
                </template>
            </div>
        </nav>
    </header>

    <main class="pt-20 bg-[#070b14]">
        <slot />
    </main>

    <!-- ─── Global Toast Container ────────────────────────────── -->
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 bottom-0 z-[9999] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6"
        >
            <!-- Success Toast -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="translate-y-8 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-8 opacity-0"
            >
                <div
                    v-if="visibleSuccess"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-emerald-200 bg-white p-4 shadow-lg"
                >
                    <div
                        class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="3"
                            stroke="currentColor"
                            class="h-3 w-3 text-emerald-600"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 12.75 6 6 9-13.5"
                            />
                        </svg>
                    </div>

                    <p class="flex-1 text-sm font-medium text-slate-800">
                        {{ visibleSuccess }}
                    </p>

                    <button
                        type="button"
                        @click="dismissSuccess"
                        class="-mt-1 -mr-1 flex h-6 w-6 flex-shrink-0 cursor-pointer items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                        aria-label="Dismiss"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="h-3.5 w-3.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </Transition>

            <!-- Error Toast -->
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="translate-y-8 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-8 opacity-0"
            >
                <div
                    v-if="visibleError"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-rose-200 bg-white p-4 shadow-lg"
                >
                    <div
                        class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-rose-100"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="3"
                            stroke="currentColor"
                            class="h-3 w-3 text-rose-600"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </div>

                    <p class="flex-1 text-sm font-medium text-slate-800">
                        {{ visibleError }}
                    </p>

                    <button
                        type="button"
                        @click="dismissError"
                        class="-mt-1 -mr-1 flex h-6 w-6 flex-shrink-0 cursor-pointer items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                        aria-label="Dismiss"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="h-3.5 w-3.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>
