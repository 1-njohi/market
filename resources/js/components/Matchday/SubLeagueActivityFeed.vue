<script setup lang="ts">
const props = defineProps({
    activity: { type: Array, default: () => [] },
});

const iconFor = (row: any) => {
    if (row.type === 'join') return '👥';
    if (row.type === 'pick') return '🎯';
    if (row.type === 'settlement') return '🏆';
    return '•';
};

const toneFor = (row: any) => {
    if (row.type === 'join') return 'text-purple-400';
    if (row.type === 'pick') return 'text-sky-400';
    if (row.type === 'settlement') return 'text-amber-400';
    return 'text-slate-400';
};

const timeAgo = (iso: string) => {
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};
</script>

<template>
    <div v-if="activity.length === 0" class="rounded-lg border border-[#232d42] bg-[#161c2a] px-6 py-10 text-center">
        <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
            No activity yet
        </p>
        <p class="mx-auto mt-2 max-w-md text-[11px] leading-relaxed text-slate-500">
            When members submit picks or matchdays settle, you'll see it here.
        </p>
    </div>

    <div v-else class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
        <div
            class="border-b border-[#232d42] bg-[#121824] px-4 py-2 text-[9px] font-black tracking-widest text-slate-500 uppercase">
            Recent activity
        </div>
        <ul class="divide-y divide-[#232d42]/40">
            <li v-for="(row, i) in activity" :key="i" class="flex items-center gap-3 px-4 py-3">
                <span class="text-base">{{ iconFor(row) }}</span>
                <span class="min-w-0 flex-1 text-[11px]" :class="toneFor(row)">
                    <!-- Show the new message with fixture context -->
                    {{ row.message }}
                </span>
                <span class="flex-shrink-0 font-mono text-[10px] text-slate-500">
                    {{ timeAgo(row.at) }}
                </span>
            </li>
        </ul>
    </div>
</template>