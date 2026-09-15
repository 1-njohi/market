<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    selections: { type: Array, required: true },
    total_odds: { type: Number, required: true },
});

const price = ref<number | null>(null);
const caption = ref('');
const submitting = ref(false);


const canSubmit = computed(
    () => price.value !== null && price.value >= 1 && !submitting.value,
);

const fmt = (n: number) =>
    'KES ' +
    Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 2 });

const submit = () => {
    if (!canSubmit.value) return;
    submitting.value = true;

    router.post(
        '/betslip',
        {
            price: price.value,
            caption: caption.value.trim() || null,
        },
        {
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Name your price — Betslip Pirates" />

    <div
        class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8"
    >
        <div class="mx-auto w-full max-w-3xl">
            <!-- Back -->
            <Link
                href="/"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Cancel and go back
            </Link>

            <!-- ═══ HERO ═══ -->
            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <p
                    class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                >
                    Step 2 of 2
                </p>
                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    Name your price
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-relaxed font-medium text-slate-400"
                >
                    Your selections are locked in. Set what buyers will pay to
                    unlock them, and add a short pitch if you want to.
                </p>
            </div>

            <!-- ═══ SELECTIONS SUMMARY ═══ -->
            <section
                class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <header
                    class="flex items-center justify-between border-b border-[#232d42] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Your selections
                    </span>
                    <span
                        class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                    >
                        {{ selections.length }}
                        {{ selections.length === 1 ? 'leg' : 'legs' }}
                    </span>
                </header>

                <ul class="divide-y divide-[#232d42]/60">
                    <li
                        v-for="(leg, i) in selections"
                        :key="leg.odd_id"
                        class="flex items-start justify-between gap-4 px-5 py-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-bold text-slate-200"
                            >
                                {{ leg.home_team }}
                                <span class="text-slate-500">vs</span>
                                {{ leg.away_team }}
                            </p>
                            <p
                                class="mt-0.5 truncate text-[11px] text-slate-500"
                            >
                                <span v-if="leg.league">{{ leg.league }}</span>
                                <span
                                    v-if="leg.league && leg.kickoff"
                                    class="text-slate-600"
                                >
                                    ·
                                </span>
                                <span v-if="leg.kickoff">{{
                                    leg.kickoff
                                }}</span>
                            </p>
                            <p
                                class="mt-1 truncate text-[11px] font-semibold text-sky-400"
                            >
                                {{ leg.market }}
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p
                                class="font-mono text-sm font-black text-emerald-400"
                            >
                                {{ Number(leg.odds).toFixed(2) }}
                            </p>
                        </div>
                    </li>
                </ul>

                <footer
                    class="flex items-center justify-between border-t border-[#232d42] bg-[#0f1422] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        Total odds
                    </span>
                    <span class="font-mono text-lg font-black text-emerald-400">
                        {{ Number(total_odds).toFixed(2) }}×
                    </span>
                </footer>
            </section>

            <!-- ═══ PRICE + CAPTION ═══ -->
            <section
                class="mb-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
            >
                <!-- Price -->
                <label
                    for="price"
                    class="block text-[10px] font-black tracking-widest text-sky-400 uppercase"
                >
                    Price to unlock
                </label>
                <p class="mt-1 mb-3 text-[11px] text-slate-500">
                    What a buyer pays to see these picks. Paid into your pending
                    balance.
                </p>
                <div class="relative">
                    <span
                        class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm font-black tracking-widest text-slate-500"
                    >
                        KES
                    </span>
                    <input
                        id="price"
                        v-model.number="price"
                        type="number"
                        min="1"
                        step="1"
                        placeholder="0"
                        class="w-full rounded-lg border border-[#232d42] bg-[#070b14] py-3 pr-4 pl-16 font-mono text-lg font-black text-white placeholder-slate-700 focus:border-sky-500 focus:outline-none"
                    />
                </div>

                <!-- Caption -->
                <div class="mt-6">
                    <label
                        for="caption"
                        class="block text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Caption
                        <span class="font-medium text-slate-600"
                            >— optional</span
                        >
                    </label>
                    <p class="mt-1 mb-3 text-[11px] text-slate-500">
                        A one-line pitch. Buyers see this on the marketplace
                        card.
                    </p>
                    <textarea
                        id="caption"
                        v-model="caption"
                        rows="3"
                        maxlength="280"
                        placeholder="e.g. Three bankers from the Premier League this weekend. Studied every fixture."
                        class="w-full resize-none rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                    ></textarea>
                    <p
                        class="mt-1 text-right text-[10px] font-bold tracking-widest text-slate-600 uppercase"
                    >
                        {{ caption.length }} / 280
                    </p>
                </div>
            </section>

            <!-- ═══ ACTIONS ═══ -->
            <div
                class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <Link
                    href="/"
                    class="rounded-lg border border-[#232d42] px-6 py-3 text-center text-xs font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/50 hover:text-slate-200"
                >
                    Cancel
                </Link>
                <button
                    type="button"
                    @click="submit"
                    :disabled="!canSubmit"
                    class="rounded-lg bg-[#ff8c00] px-8 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100"
                >
                    {{ submitting ? 'Creating…' : 'Create betslip' }}
                </button>
            </div>
        </div>
    </div>
</template>
