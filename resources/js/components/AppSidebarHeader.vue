<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import NavMenu from '@/components/NavMenu.vue';
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
        class="[#070b14] transparent fixed top-0 right-0 left-0 z-50 mx-auto flex w-full max-w-[1200px] justify-center bg-[#070b14] text-sm not-has-[nav]:hidden md:px-6 lg:px-0"
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
                    <!-- Balance pill -->
                    <Link
                        :href="dashboard()"
                        class="flex items-center gap-1.5 rounded border border-[#232d42] bg-[#111622] px-3 py-2 transition-colors hover:border-sky-500/40"
                        title="Available balance"
                    >
                        <span
                            class="hidden text-[9px] font-black tracking-widest text-slate-500 uppercase sm:inline"
                        >
                            Balance
                        </span>
                        <span
                            class="font-mono text-xs font-black text-emerald-400"
                        >
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

                    <NavMenu />
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

    <main class="bg-[#070b14] pt-20">
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
