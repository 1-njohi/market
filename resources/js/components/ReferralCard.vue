<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    card: { type: Object, required: true },
});

const hasEarnings = computed(() => props.card.total_earned > 0);
</script>

<template>
    <div
        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
        >
            <span
                class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
            >
                Refer & Earn
            </span>
            <Link
                href="/refer"
                class="font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
            >
                Manage →
            </Link>
        </div>

        <!-- Code -->
        <div class="border-b border-[#232d42]/60 p-4">
            <span
                class="text-[9px] font-black tracking-widest text-slate-500 uppercase"
            >
                Your code
            </span>
            <p
                class="mt-1.5 font-mono text-lg font-black tracking-widest text-[#ff8c00] uppercase"
            >
                {{ card.code }}
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 divide-x divide-[#232d42]/60">
            <div class="p-3">
                <span
                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                >
                    Referees
                </span>
                <p class="mt-1 font-mono text-sm font-black text-white">
                    {{ card.referee_count }}
                </p>
            </div>
            <div class="p-3">
                <span
                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                >
                    Total earned
                </span>
                <p
                    class="mt-1 font-mono text-sm font-black"
                    :class="hasEarnings ? 'text-emerald-400' : 'text-slate-500'"
                >
                    KES {{ card.total_earned.toFixed(2) }}
                </p>
            </div>
        </div>

        <!-- Pitch -->
        <div
            v-if="card.referee_count === 0"
            class="border-t border-[#232d42]/60 p-4"
        >
            <p class="text-[11px] leading-relaxed text-slate-500">
                Share your code and earn
                <span class="font-black text-slate-300">10%</span>
                of the listed price every time a friend makes a winning
                purchase.
            </p>
        </div>
        <div v-else class="border-t border-[#232d42]/60 p-4">
            <p class="text-[11px] leading-relaxed text-slate-500">
                <span class="font-black text-slate-300">{{
                    card.total_wins
                }}</span>
                winning transaction{{ card.total_wins === 1 ? '' : 's' }}
                from your referees so far.
            </p>
        </div>
    </div>
</template>
