<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Contest {
    uuid: string;
    name: string;
    description: string | null;
    host_name: string;
    host_code: string;
    legs_count: number;
    entries_count: number;
    entry_deadline_at: string;
    ends_at: string;
    is_joined: boolean;
}

const props = defineProps<{
    contest: Contest;
}>();

const formatAbsolute = (iso: string): string => {
    const d = new Date(iso);
    return d.toLocaleString('en-KE', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

const timeLeft = computed<string>(() => {
    const ms = new Date(props.contest.entry_deadline_at).getTime() - Date.now();
    if (ms <= 0) return 'Closed';

    const mins = Math.floor(ms / 60_000);
    const hours = Math.floor(mins / 60);
    const days = Math.floor(hours / 24);

    if (days >= 1) return `${days}d ${hours % 24}h left`;
    if (hours >= 1) return `${hours}h ${mins % 60}m left`;
    return `${mins}m left`;
});

// Under 60 minutes reads as urgent — colour it amber.
const isUrgent = computed<boolean>(() => {
    const ms = new Date(props.contest.entry_deadline_at).getTime() - Date.now();
    return ms > 0 && ms < 60 * 60 * 1000;
});
</script>

<template>
    <Link :href="`/contests/join/${contest.uuid}`" class="group block h-full">
        <article
            class="flex h-full flex-col overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a] transition-colors group-hover:border-sky-500/50">
            <!-- Top strip -->
            <header class="flex items-center justify-between border-b border-[#232d42] px-4 py-2.5">
                <span class="text-[9px] font-black tracking-widest text-amber-400 uppercase">
                    Public Contest
                </span>
                <span v-if="contest.is_joined"
                    class="rounded-sm bg-emerald-500/15 px-2 py-0.5 text-[9px] font-black tracking-widest text-emerald-400 uppercase">
                    You're in
                </span>
                <span v-else class="text-[10px] text-slate-600 transition-colors group-hover:text-sky-400"
                    aria-hidden="true">
                    →
                </span>
            </header>

            <!-- Body -->
            <div class="flex-1 px-4 py-3">
                <h4 class="line-clamp-1 text-sm font-black tracking-wide text-white uppercase">
                    {{ contest.name }}
                </h4>
                <p class="mt-1 text-[10px] text-slate-500">
                    by
                    <span class="font-bold text-slate-400">
                        {{ contest.host_name }}
                    </span>
                </p>

                <p v-if="contest.description" class="mt-2.5 line-clamp-2 text-[11px] leading-relaxed text-slate-400">
                    {{ contest.description }}
                </p>
            </div>

            <!-- Stats row -->
            <footer class="flex items-center justify-between gap-2 border-t border-[#232d42] bg-[#0f1422] px-4 py-2.5">
                <div class="flex items-center gap-3 font-mono text-[10px] text-slate-500">
                    <span>
                        <span class="font-black text-slate-300">
                            {{ contest.legs_count }}
                        </span>
                        <span class="ml-1 uppercase">legs</span>
                    </span>
                    <span class="text-slate-700">·</span>
                    <span>
                        <span class="font-black text-slate-300">
                            {{ contest.entries_count }}
                        </span>
                        <span class="ml-1 uppercase">joined</span>
                    </span>
                </div>

                <span class="font-mono text-[10px] font-black uppercase"
                    :class="isUrgent ? 'text-amber-400' : 'text-slate-400'"
                    :title="formatAbsolute(contest.entry_deadline_at)">
                    {{ timeLeft }}
                </span>
            </footer>
        </article>
    </Link>
</template>