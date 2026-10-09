<template>
    <div
        v-if="paginator && paginator.last_page > 1"
        class="flex items-center justify-between gap-3 rounded border border-[#232d42] bg-[#111622] px-3 py-2"
    >
        <Link
            v-if="paginator.prev_page_url"
            :href="paginator.prev_page_url"
            preserve-scroll
            preserve-state
            class="rounded border border-[#232d42] bg-[#0a101f] px-3 py-1.5 font-mono text-[9px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/50 hover:text-white"
        >
            ← Prev
        </Link>
        <span
            v-else
            class="rounded border border-transparent px-3 py-1.5 font-mono text-[9px] font-black tracking-widest text-slate-700 uppercase"
        >
            ← Prev
        </span>

        <span class="font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase">
            Page {{ paginator.current_page }} of {{ paginator.last_page }}
        </span>

        <Link
            v-if="paginator.next_page_url"
            :href="paginator.next_page_url"
            preserve-scroll
            preserve-state
            class="rounded border border-[#232d42] bg-[#0a101f] px-3 py-1.5 font-mono text-[9px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/50 hover:text-white"
        >
            Next →
        </Link>
        <span
            v-else
            class="rounded border border-transparent px-3 py-1.5 font-mono text-[9px] font-black tracking-widest text-slate-700 uppercase"
        >
            Next →
        </span>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    paginator: { type: Object, required: true },
});
</script>