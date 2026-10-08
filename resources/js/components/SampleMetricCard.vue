<template>
    <div class="relative overflow-hidden rounded border border-[#232d42] bg-[#111622]/30 p-4">
        <!-- Placeholder content, greyed -->
        <div class="opacity-30 blur-[1px] select-none">
            <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                {{ label }}
            </span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="font-mono text-3xl font-black tracking-tight text-white">
                    {{ sample }}
                </span>
            </div>
            <div class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] uppercase">
                <span class="text-slate-500">{{ sublabel }}</span>
                <span class="font-bold text-white">{{ subsample }}</span>
            </div>
        </div>

        <!-- Overlay -->
        <div class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 bg-[#070b14]/70 px-3 text-center backdrop-blur-[2px]">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="h-4 w-4 text-slate-400"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                />
            </svg>
            <p class="text-[9px] font-black tracking-widest text-slate-400 uppercase">
                {{ hint }}
            </p>
        </div>
    </div>
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