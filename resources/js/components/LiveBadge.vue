<template>
    <span
        class="flex items-center gap-1.5 font-mono text-[9px] font-black tracking-widest text-emerald-400 uppercase"
    >
        <span class="relative flex h-1.5 w-1.5">
            <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"
            ></span>
            <span
                class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"
            ></span>
        </span>
        Live
    </span>
</template>

<script setup>
// Presentational only. Signals that the adjacent panel is a live work
// queue, not a historical log — it never responds to the period picker.
</script>