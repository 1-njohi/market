<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    card: { type: Object, required: true },
});

const hasEarnings = computed(() => props.card.total_earned > 0);
</script>

<template>
    <section class="relative overflow-hidden rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/10 via-[#0b1220] to-[#0b1220]">
        <div class="pointer-events-none absolute -top-16 -right-12 h-44 w-44 rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true"></div>

        <!-- Header -->
        <div class="relative flex items-center justify-between px-5 pt-5">
            <h2 class="text-base font-bold text-amber-200">Refer and earn</h2>
            <Link href="/refer" class="inline-flex min-h-11 items-center text-sm font-bold text-sky-300 transition-colors hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none">
                Manage →
            </Link>
        </div>

        <!-- Code -->
        <div class="relative px-5 pb-4">
            <p class="text-sm text-slate-500">Your code</p>
            <p class="mt-1 font-mono text-3xl font-black tracking-widest text-amber-300 uppercase">{{ card.code }}</p>
        </div>

        <!-- Stats -->
        <dl class="relative grid grid-cols-2 divide-x divide-white/[0.06] border-t border-white/[0.06] bg-black/20">
            <div class="p-4">
                <dt class="text-xs text-slate-500">Referees</dt>
                <dd class="mt-1 text-xl font-black text-white tabular-nums">{{ card.referee_count }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs text-slate-500">Total earned</dt>
                <dd class="mt-1 text-xl font-black tabular-nums" :class="hasEarnings ? 'text-emerald-300' : 'text-slate-500'">
                    KES {{ card.total_earned.toFixed(2) }}
                </dd>
            </div>
        </dl>

        <!-- Pitch -->
        <div v-if="card.referee_count === 0" class="relative border-t border-white/[0.06] px-5 py-4">
            <p class="text-sm leading-relaxed text-slate-400">
                Share your code and earn
                <span class="font-black text-slate-100">10%</span>
                of the listed price every time a friend makes a winning purchase.
            </p>
        </div>
        <div v-else class="relative border-t border-white/[0.06] px-5 py-4">
            <p class="text-sm leading-relaxed text-slate-400">
                <span class="font-black text-slate-100">{{ card.total_wins }}</span>
                winning transaction{{ card.total_wins === 1 ? '' : 's' }}
                from your referees so far.
            </p>
        </div>
    </section>
</template>