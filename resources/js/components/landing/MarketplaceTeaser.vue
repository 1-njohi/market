<template>
    <SectionWrapper variant="darker">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
            <!-- Left: preview card -->
            <div
                class="mx-auto w-full max-w-md rounded-xl border border-[#232d42] bg-[#111622] p-5 shadow-2xl"
            >
                <div class="flex items-center justify-between border-b border-[#232d42] pb-3">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full border border-amber-500/30 bg-amber-500/10 font-mono text-[10px] font-black text-amber-400"
                        >
                            DK
                        </div>
                        <div>
                            <p class="text-xs font-black tracking-wider text-white uppercase">
                                @denis
                            </p>
                            <p
                                class="text-[9px] font-black tracking-widest text-amber-400 uppercase"
                            >
                                Sharp
                            </p>
                        </div>
                    </div>
                    <span
                        class="rounded border border-emerald-500/30 bg-emerald-500/10 px-1.5 py-0.5 text-[8px] font-black tracking-widest text-emerald-400 uppercase"
                    >
                        Verified
                    </span>
                </div>

                <div class="mt-4 text-xs text-slate-400">
                    3-leg parlay · BTTS + Over 2.5 + Arsenal win
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                        Total odds
                    </span>
                    <span class="font-mono text-lg font-black text-amber-400">
                        5.20
                    </span>
                </div>

                <div
                    class="mt-4 flex items-center justify-between border-t border-[#232d42] pt-3"
                >
                    <span class="text-lg font-black text-white">120 KES</span>
                    <button
                        type="button"
                        class="rounded-lg bg-amber-500 px-4 py-2 text-[10px] font-black tracking-widest text-black uppercase"
                    >
                        Unlock
                    </button>
                </div>
            </div>

            <!-- Right: copy -->
            <div>
                <SectionHeading
                    align="left"
                    eyebrow="Marketplace"
                    title="The marketplace unlocks when you're good."
                />
                <p class="mt-6 text-base leading-relaxed text-slate-400">
                    Every week, our sharpest pickers publish their calls. You
                    can browse today — see who's selling, what they're
                    picking, and what their record says about them. But to
                    sell, you need to earn it. 20 verified picks. 55%
                    accuracy. Then it's yours.
                </p>
                <div class="mt-8">
                    <Link
                        href="/marketplace"
                        class="inline-block rounded-xl border border-[#232d42] bg-[#111622] px-6 py-3 text-xs font-bold tracking-wider text-slate-300 uppercase transition-all hover:border-sky-500/40 hover:text-white"
                    >
                        Browse the marketplace →
                    </Link>
                </div>
            </div>
        </div>
    </SectionWrapper>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';
</script>