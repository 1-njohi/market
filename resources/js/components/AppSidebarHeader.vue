<script setup lang="ts">
// import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';

import { router } from '@inertiajs/vue3';
// import type { BreadcrumbItem } from '@/types';
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

// Safely pull the flash data dynamically from the global pipeline
const flashSuccess = computed(() => page.props.flash?.success);

// Optional: Automatically trigger a toast animation or log when a new success arrives
watch(flashSuccess, (newMessage) => {
    if (newMessage) {
        setTimeout(() => {
            page.props.flash = null;
        }, 4000);
        // Trigger your custom alert banner component here if you use one
    }
});

// withDefaults(
//     defineProps<{
//         breadcrumbs?: BreadcrumbItem[];
//     }>(),
//     {
//         breadcrumbs: () => [],
//     },
// );
</script>

<template>
    <header
        class="fixed top-0 right-0 left-0 z-50 mt-2 flex w-full justify-center bg-transparent"
    >
        <nav
            class="flex w-[95%] items-center justify-between rounded-lg border border-[#232d42] bg-[#161c2a] p-4 shadow-lg md:w-[75rem]"
        >
            <!-- BRAND / LOGO ACCENT -->
            <div
                @click="router.get('/')"
                class="flex cursor-pointer items-center space-x-2 text-sky-400"
            >
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
            </div>

            <!-- NAVIGATION LINKS & ACTIONS -->
            <div class="flex items-center gap-3">
                <template v-if="$page.props.auth.user">
                    <Link
                        @click="router.get('/dashboard')"
                        class="inline-block cursor-pointer rounded border border-transparent bg-[#242f48] px-4 py-2 text-xs font-bold tracking-wide text-sky-400 uppercase transition-colors hover:bg-[#2e3c5c]"
                    >
                        Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link
                        @click="router.get('/login')"
                        class="inline-block cursor-pointer rounded px-4 py-2 text-xs font-bold tracking-wide text-slate-400 uppercase transition-colors hover:text-slate-200"
                    >
                        Log in
                    </Link>
                    <Link
                        @click="router.get('/register')"
                        class="inline-block cursor-pointer rounded border border-transparent bg-[#242f48] px-4 py-2 text-xs font-bold tracking-wide text-slate-200 uppercase transition-colors hover:bg-[#2e3c5c]"
                    >
                        Register
                    </Link>
                </template>
            </div>
        </nav>
    </header>

    <!-- Main content with margin-top to account for fixed header -->
    <main class="pb-20">
        <!-- GLOBAL CYBERPUNK ALERTS CONSOLE -->
        <div
            v-if="flashSuccess"
            class="should be coming from the bottom like a real toast fixed right-5 bottom-5 left-5 z-50 flex animate-pulse items-center space-x-3 rounded border border-emerald-500/30 bg-[#161c2a] px-4 py-3 shadow-[0_0_15px_rgba(16,185,129,0.15)] transition-all"
        >
            <!-- Emerald Terminal Dot Indicator -->
            <div
                class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_8px_#10b981]"
            ></div>

            <div class="flex flex-col">
                <span
                    class="mt-0.5 text-xs font-bold tracking-wide text-slate-200 uppercase"
                >
                    {{ flashSuccess }}
                </span>
            </div>
        </div>
        <slot />
    </main>
</template>
