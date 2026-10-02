<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    contests: { type: Array, default: () => [] },
});

const formatDeadline = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    const now = new Date();
    const diffMs = d - now;

    if (diffMs < 0) return 'expired';

    const hours = Math.floor(diffMs / (1000 * 60 * 60));
    if (hours < 1) {
        const mins = Math.floor(diffMs / (1000 * 60));
        return `${mins}m left`;
    }
    if (hours < 24) {
        return `${hours}h left`;
    }
    const days = Math.floor(hours / 24);
    return `${days}d left`;
};

const deadlineClass = (iso) => {
    if (!iso) return 'text-slate-500';
    const d = new Date(iso);
    const hours = (d - new Date()) / (1000 * 60 * 60);
    if (hours < 0) return 'text-rose-400';
    if (hours < 12) return 'text-amber-400';
    return 'text-slate-500';
};

const rowHref = (contest) => {
    if (contest.role === 'host') {
        return `/contests/${contest.id}/manage`;
    }
    return `/contests/${contest.uuid}/picks`;
};
</script>

<template>
    <div
        v-if="contests.length > 0"
        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
    >
        <div
            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
        >
            <span
                class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
            >
                Active Contests [{{ contests.length }}]
            </span>
            <Link
                href="/contests/mine"
                class="font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
            >
                View all →
            </Link>
        </div>

        <div class="divide-y divide-gray-800/40">
            <Link
                v-for="c in contests"
                :key="c.id"
                :href="rowHref(c)"
                class="flex items-center justify-between gap-3 p-3 transition-colors hover:bg-[#111a30]/40"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        :class="[
                            'flex h-5 flex-shrink-0 items-center justify-center rounded-sm px-1.5 font-mono text-[8px] font-black tracking-wider uppercase',
                            c.role === 'host'
                                ? 'bg-amber-500 text-[#070b14]'
                                : 'bg-sky-500 text-[#070b14]',
                        ]"
                    >
                        {{ c.role === 'host' ? 'HOST' : 'PLAY' }}
                    </span>
                    <div class="min-w-0">
                        <p
                            class="truncate font-mono text-[11px] font-bold text-slate-200"
                        >
                            {{ c.name }}
                        </p>
                        <p class="mt-0.5 truncate text-[10px] text-slate-500">
                            {{ c.legs_count }} legs ·
                            <template v-if="c.role === 'host'">
                                {{ c.accepted_entries }} accepted
                                <template v-if="c.pending_requests > 0">
                                    ·
                                    <span class="font-bold text-amber-400">
                                        {{ c.pending_requests }} pending
                                    </span>
                                </template>
                            </template>
                            <template v-else>
                                {{ c.picks_submitted }}/{{ c.legs_count }} picks
                            </template>
                        </p>
                    </div>
                </div>
                <span
                    class="flex-shrink-0 font-mono text-[10px] font-bold whitespace-nowrap"
                    :class="deadlineClass(c.entry_deadline_at)"
                >
                    {{ formatDeadline(c.entry_deadline_at) }}
                </span>
            </Link>
        </div>
    </div>
</template>