<template>
    <div class="relative flex max- h-[calc(100vh-2rem)]  items-center overflow-hidden bg-[#070b14] px-4 pt-28 pb-12 lg:px-8">
        <!-- Same glows and pitch arc as the landing hero -->
        <svg
            class="pointer-events-none absolute -right-40 bottom-0 h-[130%] w-auto text-white/[0.04]"
            viewBox="0 0 600 800"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
        >
            <circle cx="600" cy="400" r="260" />
            <circle cx="600" cy="400" r="6" fill="currentColor" />
            <rect x="380" y="230" width="240" height="340" />
        </svg>
        <div class="pointer-events-none absolute -top-32 left-[5%] h-[28rem] w-[28rem] rounded-full bg-sky-600/20 blur-[150px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute right-[8%] -bottom-32 h-[26rem] w-[26rem] rounded-full bg-amber-500/15 blur-[160px]" aria-hidden="true"></div>

        <div class="relative mx-auto grid w-full max-w-5xl items-center gap-16 lg:grid-cols-[1fr_28rem]">
            <!-- Pitch: desktop only, so phones go straight to the form -->
            <aside class="hidden lg:block">
                <p class="mb-6 inline-flex items-center gap-2.5 rounded-full border border-sky-400/25 bg-sky-400/10 py-1.5 pr-4 pl-3 text-xs font-semibold text-sky-300">
                    <span class="h-2 w-2 rounded-full bg-sky-400"></span>
                    Free matchday contests. No stake.
                </p>

                <h2 class="text-6xl leading-[0.98] font-black tracking-[-0.05em] text-white">
                    Call it.<br />
                    Prove it.<br />
                    <span class="text-amber-400">Sell it.</span>
                </h2>

                <ul class="mt-10 space-y-5">
                    <li class="flex items-start gap-4">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-400/15 text-sm font-black text-sky-300">1</span>
                        <div>
                            <p class="font-bold text-white">Free contests</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-slate-400">Call every fixture, every matchday, with your crew.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-400/15 text-sm font-black text-indigo-300">2</span>
                        <div>
                            <p class="font-bold text-white">A verified record</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-slate-400">Every result is checked after full time, so nobody can fake it.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-400/15 text-sm font-black text-amber-300">3</span>
                        <div>
                            <p class="font-bold text-white">The marketplace</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-slate-400">Hit the accuracy threshold and sell your picks.</p>
                        </div>
                    </li>
                </ul>
            </aside>

            <!-- Card -->
            <div class="mx-auto w-full max-w-md lg:max-w-none">
                <div class="rounded-3xl border border-white/[0.08] bg-[#0e1422]/90 p-6 shadow-[0_30px_80px_-30px_rgba(0,0,0,0.9)] backdrop-blur-xl sm:p-8">
                    <h1 class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-1.5 text-sm leading-relaxed text-slate-400">{{ subtitle }}</p>

                    <div class="mt-7">
                        <slot />
                    </div>
                </div>

                <div v-if="$slots.footer" class="mt-6 text-center text-sm text-slate-400">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>