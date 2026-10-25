<template>
    <SectionWrapper id="record">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
            <!-- Left: copy -->
            <div>
                <SectionHeading
                    align="left"
                    eyebrow="Your record"
                    title="Your record is everything."
                />
                <p class="mt-6 text-base leading-relaxed text-slate-400">
                    Every pick you make — matchday, contest, logged bet — is
                    timestamped, verified after full time, and counted
                    toward a single accuracy score. It's yours forever.
                    It's what you sell, what buyers trust, what separates a
                    sharp from a mug.
                </p>

                <ul class="mt-6 space-y-2 text-sm text-slate-400">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-sky-400">▸</span>
                        <span>143 picks · 64% accuracy</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-sky-400">▸</span>
                        <span>Longest streak: 9 correct in a row</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-sky-400">▸</span>
                        <span>3 contest wins · 12 sub-leagues entered</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 text-sky-400">▸</span>
                        <span>Tier: Sharp</span>
                    </li>
                </ul>

                <div class="mt-8">
                    <Link
                        :href="recordHref"
                        class="inline-block rounded-xl bg-amber-500 px-6 py-3 text-xs font-bold tracking-wider text-black uppercase shadow-lg shadow-amber-500/20 transition-all hover:bg-amber-400 active:scale-[0.98]"
                    >
                        See your record →
                    </Link>
                </div>
            </div>

            <!-- Right: record card visual -->
            <div
                class="mx-auto w-full max-w-sm rounded-xl border border-[#232d42] bg-[#111622] p-6 shadow-2xl"
            >
                <div class="flex items-center gap-3 border-b border-[#232d42] pb-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full border border-sky-500/30 bg-sky-500/10 font-mono text-sm font-black text-sky-400"
                    >
                        DK
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-black tracking-wider text-white uppercase">
                            @denis
                        </p>
                        <p
                            class="mt-0.5 inline-block rounded border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 text-[9px] font-black tracking-widest text-amber-400 uppercase"
                        >
                            Sharp
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex items-baseline justify-between">
                        <span
                            class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                        >
                            Accuracy
                        </span>
                        <span class="font-mono text-lg font-black text-white">
                            64%
                        </span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-700">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-400"
                            style="width: 64%"
                        ></div>
                    </div>
                </div>

                <div class="mt-5 space-y-2 border-t border-[#232d42] pt-4 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Picks</span>
                        <span class="font-mono font-black text-slate-200">143</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Longest streak</span>
                        <span class="font-mono font-black text-slate-200">9</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Contests won</span>
                        <span class="font-mono font-black text-slate-200">3</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Sub-leagues</span>
                        <span class="font-mono font-black text-slate-200">12</span>
                    </div>
                </div>
            </div>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';

const page = usePage();
const recordHref = computed(() => {
    const user = page.props.auth?.user;
    return user ? `/u/${user.username ?? user.code ?? user.id}` : '/register';
});
</script>