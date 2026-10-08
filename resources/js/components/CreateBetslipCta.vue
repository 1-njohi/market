<template>
    <!-- Settlement-ready variant: buyer's money waiting to be claimed -->
    <Link
        v-if="settlementCount > 0"
        href="/seller/settlements"
        class="group relative flex items-center justify-between gap-4 overflow-hidden rounded border border-emerald-500/50 bg-gradient-to-r from-emerald-500/15 via-emerald-500/5 to-transparent p-4 transition-all hover:border-emerald-400 hover:from-emerald-500/25 hover:via-emerald-500/15"
    >
        <div
            class="absolute inset-y-0 left-0 w-1 bg-emerald-500 shadow-[0_0_20px_rgba(16,185,129,0.7)]"
        ></div>
        <div class="flex items-center gap-4 pl-2">
            <div
                class="relative flex h-10 w-10 flex-shrink-0 items-center justify-center rounded border border-emerald-500/40 bg-emerald-500/10 text-emerald-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                    class="h-5 w-5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                </svg>
                <span
                    class="absolute -top-1 -right-1 flex h-4 min-w-4 animate-pulse items-center justify-center rounded-full bg-emerald-500 px-1 font-mono text-[9px] font-black text-[#070b14]"
                >
                    {{ settlementCount }}
                </span>
            </div>
            <div class="min-w-0">
                <p
                    class="text-xs font-black tracking-widest text-emerald-400 uppercase"
                >
                    {{ settlementCount }} Settlement{{
                        settlementCount === 1 ? '' : 's'
                    }}
                    Ready
                </p>
                <p class="mt-0.5 text-[11px] text-slate-300">
                    Collect your earnings from settled betslips
                </p>
            </div>
        </div>
        <div
            class="flex flex-shrink-0 items-center gap-2 pr-2 font-mono text-[10px] font-black tracking-widest text-emerald-400 uppercase transition-transform group-hover:translate-x-0.5"
        >
            <span class="hidden sm:inline">Collect</span>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="3"
                stroke="currentColor"
                class="h-4 w-4"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                />
            </svg>
        </div>
    </Link>

    <!-- Default variant: create new betslip -->
    <Link
        v-else
        href="/fixtures"
        class="group relative flex items-center justify-between gap-4 overflow-hidden rounded border border-sky-500/40 bg-gradient-to-r from-sky-500/10 via-sky-500/5 to-transparent p-4 transition-all hover:border-sky-400 hover:from-sky-500/20 hover:via-sky-500/10"
    >
        <div
            class="absolute inset-y-0 left-0 w-1 bg-sky-500 shadow-[0_0_15px_rgba(14,165,233,0.5)]"
        ></div>
        <div class="flex items-center gap-4 pl-2">
            <div
                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded border border-sky-500/40 bg-sky-500/10 text-sky-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                    class="h-5 w-5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4.5v15m7.5-7.5h-15"
                    />
                </svg>
            </div>
            <div class="min-w-0">
                <p
                    class="text-xs font-black tracking-widest text-sky-400 uppercase"
                >
                    Create a Betslip
                </p>
                <p class="mt-0.5 text-[11px] text-slate-400">
                    Browse fixtures and build your next tip
                </p>
            </div>
        </div>
        <div
            class="flex flex-shrink-0 items-center gap-2 pr-2 font-mono text-[10px] font-black tracking-widest text-sky-400 uppercase transition-transform group-hover:translate-x-0.5"
        >
            <span class="hidden sm:inline">Start</span>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="3"
                stroke="currentColor"
                class="h-4 w-4"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                />
            </svg>
        </div>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    settlementCount: { type: Number, default: 0 },
});
</script>