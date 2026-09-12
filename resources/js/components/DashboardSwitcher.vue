<template>
    <div
        class="flex items-center rounded border border-[#232d42] bg-[#111622] p-0.5"
    >
        <Link
            href="/buyer/dashboard"
            :class="[
                'flex items-center gap-1 rounded px-2.5 py-1.5 text-[10px] font-black tracking-wider uppercase transition-all',
                active === 'buyer'
                    ? 'bg-sky-500 text-[#070b14] shadow-[0_0_10px_rgba(14,165,233,0.3)]'
                    : 'text-slate-400 hover:text-white',
            ]"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2.5"
                stroke="currentColor"
                class="h-3 w-3"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"
                />
            </svg>
            <span class="hidden sm:inline">Buy</span>
        </Link>

        <Link
            href="/seller/dashboard"
            :class="[
                'flex items-center gap-1 rounded px-2.5 py-1.5 text-[10px] font-black tracking-wider uppercase transition-all',
                active === 'seller'
                    ? 'bg-emerald-500 text-[#070b14] shadow-[0_0_10px_rgba(16,185,129,0.3)]'
                    : 'text-slate-400 hover:text-white',
            ]"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2.5"
                stroke="currentColor"
                class="h-3 w-3"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"
                />
            </svg>
            <span class="hidden sm:inline">Sell</span>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    active: {
        type: String,
        required: true,
        validator: (v) => ['buyer', 'seller'].includes(v),
    },
});
</script>