<template>
    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-bold text-emerald-300">
        <span class="relative flex h-2 w-2">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60 motion-reduce:animate-none"></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
        </span>
        Live
    </span>
</template>

<script setup>
// Presentational only. Signals that the adjacent panel is a live work
// queue, not a historical log — it never responds to the period picker.
</script>