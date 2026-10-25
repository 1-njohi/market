<template>
    <section class="relative overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5">
        <!-- Placeholder content, greyed -->
        <div class="opacity-30 blur-[1px] select-none" aria-hidden="true">
            <span class="text-sm text-slate-500">{{ label }}</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-4xl font-black tracking-tight text-white tabular-nums">{{ sample }}</span>
            </div>
            <div class="mt-5 flex justify-between border-t border-white/10 pt-3 text-sm">
                <span class="text-slate-500">{{ sublabel }}</span>
                <span class="font-bold text-white tabular-nums">{{ subsample }}</span>
            </div>
        </div>

        <!-- Overlay -->
        <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-[#070b14]/70 px-4 text-center backdrop-blur-[2px]">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-slate-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                    />
                </svg>
            </span>
            <p class="text-sm font-semibold text-slate-300">{{ hint }}</p>
        </div>
    </section>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    sample: { type: String, default: '—' },
    sublabel: { type: String, default: '' },
    subsample: { type: String, default: '—' },
    hint: { type: String, default: 'Unlocks after first activity' },
});
</script>