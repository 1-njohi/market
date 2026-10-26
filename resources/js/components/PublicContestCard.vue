<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Money from '@/components/Money.vue';
import ReputationBadge from '@/components/ReputationBadge.vue';
import { useTicker } from '@/composables/useTicker';

interface FixturePreview {
    id: number;
    home_team: string;
    away_team: string;
    kickoff: string | null;
    market_name: string;
}

interface Contest {
    uuid: string;
    name: string;
    description: string | null;
    host_name: string;
    host_code: string;
    host_reputation: number;
    host_rank: number | null;
    legs_count: number;
    fixture_preview: FixturePreview[];
    entries_count: number;
    pending_count: number;
    total_picks: number;
    auto_entry: boolean;
    entry_deadline_at: string;
    ends_at: string;
    is_joined: boolean;
    prize_pool: number;
}

const props = defineProps<{
    contest: Contest;
}>();

// Shared ticker, so the countdown actually counts down instead of freezing at render time.
const { now } = useTicker();

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

const formatKickoff = (iso: string | null): string => (iso ? formatAbsolute(iso) : 'TBD');

const msLeft = computed<number>(
    () => new Date(props.contest.entry_deadline_at).getTime() - now.value,
);

const timeLeft = computed<string>(() => {
    const ms = msLeft.value;
    if (ms <= 0) return 'Closed';

    const mins = Math.floor(ms / 60_000);
    const hours = Math.floor(mins / 60);
    const days = Math.floor(hours / 24);

    if (days >= 1) return `${days}d ${hours % 24}h left`;
    if (hours >= 1) return `${hours}h ${mins % 60}m left`;
    return `${mins}m left`;
});

// Under 60 minutes reads as urgent, so colour it amber.
const isUrgent = computed<boolean>(() => msLeft.value > 0 && msLeft.value < 60 * 60 * 1000);

// The preview holds at most 3 fixtures; tell the visitor how many more are in the contest.
const moreLegs = computed<number>(() =>
    Math.max(0, props.contest.legs_count - props.contest.fixture_preview.length),
);
</script>

<template>
    <Link
        :href="`/contests/join/${contest.uuid}`"
        class="group block h-full rounded-3xl focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
    >
        <article
            class="relative flex h-full flex-col overflow-hidden rounded-3xl border bg-[#0b1220] transition-all duration-300 group-hover:-translate-y-1 group-active:scale-[0.99]"
            :class="
                contest.is_joined
                    ? 'border-emerald-400/30 group-hover:border-emerald-300/60 group-hover:shadow-[0_24px_60px_-28px_rgba(52,211,153,0.5)]'
                    : 'border-white/[0.07] group-hover:border-amber-300/40 group-hover:shadow-[0_24px_60px_-28px_rgba(251,191,36,0.4)]'
            "
        >
            <div
                class="pointer-events-none absolute -top-16 -right-12 h-44 w-44 rounded-full blur-3xl"
                :class="contest.is_joined ? 'bg-emerald-400/10' : 'bg-amber-400/10'"
                aria-hidden="true"
            ></div>

            <!-- Top: type + status -->
            <header class="relative flex items-center justify-between gap-3 px-5 pt-5">
                <span class="rounded-full bg-amber-400/10 px-2.5 py-1 text-xs font-bold text-amber-300">Public contest</span>
                <span v-if="contest.is_joined" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-bold text-emerald-300">
                    <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2.5 6.5l2.2 2.2L9.5 3.8" />
                    </svg>
                    You're in
                </span>
                <span v-else class="text-xs font-semibold text-slate-500">{{ contest.auto_entry ? 'Instant entry' : 'Host approves entries' }}</span>
            </header>

            <!-- Body -->
            <div class="relative flex-1 px-5 pt-4 pb-4">
                <h3 class="line-clamp-2 text-xl leading-snug font-black tracking-tight text-white">{{ contest.name }}</h3>

                <!-- Host: name, code, reputation -->
                <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    <span>
                        by <span class="font-semibold text-slate-300">{{ contest.host_name }}</span>
                    </span>
                    <span class="font-mono text-xs text-slate-600">@{{ contest.host_code }}</span>
                    <ReputationBadge v-if="contest.host_reputation > 0" :points="contest.host_reputation" :rank="contest.host_rank ?? null" :compact="true" />
                </div>

                <p v-if="contest.description" class="mt-3 line-clamp-2 text-sm leading-relaxed text-slate-400">{{ contest.description }}</p>

                <!-- What you'd be picking: every leg shows its own market -->
                <ul v-if="contest.fixture_preview.length > 0" class="mt-4 divide-y divide-white/[0.05] overflow-hidden rounded-2xl border border-white/[0.06] bg-white/[0.03]">
                    <li v-for="f in contest.fixture_preview" :key="f.id" class="px-3.5 py-3">
                        <p class="truncate text-sm font-semibold text-slate-200">
                            {{ f.home_team }} <span class="font-normal text-slate-500">v</span> {{ f.away_team }}
                        </p>
                        <p class="mt-1.5 flex items-center gap-2">
                            <span class="shrink-0 rounded-lg bg-amber-400/10 px-2 py-0.5 text-xs font-bold text-amber-200">{{ f.market_name }}</span>
                            <span class="truncate text-xs text-slate-500 tabular-nums">{{ formatKickoff(f.kickoff) }}</span>
                        </p>
                    </li>
                    <li v-if="moreLegs > 0" class="px-3.5 py-2 text-xs font-semibold text-sky-300">+{{ moreLegs }} more {{ moreLegs === 1 ? 'fixture' : 'fixtures' }}</li>
                </ul>
            </div>

            <!-- Stats -->
            <dl class="relative grid grid-cols-3 divide-x divide-white/[0.06] border-t border-white/[0.06] bg-black/20">
                <div class="px-3 py-3 text-center">
                    <dt class="text-xs text-slate-500">Legs</dt>
                    <dd class="mt-0.5 text-lg font-black text-white tabular-nums">{{ contest.legs_count }}</dd>
                </div>
                <div class="px-3 py-3 text-center">
                    <dt class="text-xs text-slate-500">Joined</dt>
                    <dd class="mt-0.5 text-lg font-black text-white tabular-nums">{{ contest.entries_count }}</dd>
                    <p v-if="contest.pending_count > 0" class="text-xs font-semibold text-amber-300 tabular-nums">{{ contest.pending_count }} pending</p>
                </div>
                <div class="px-3 py-3 text-center">
                    <dt class="text-xs text-slate-500">Picks made</dt>
                    <dd class="mt-0.5 text-lg font-black text-white tabular-nums">{{ contest.total_picks }}</dd>
                </div>
            </dl>

            <!-- Prize pool (only when there is one) -->
            <div v-if="contest.prize_pool > 0" class="relative flex items-center justify-between border-t border-white/[0.06] bg-amber-400/5 px-5 py-3">
                <span class="text-sm text-slate-400">Prize pool</span>
                <span class="text-base font-black text-amber-300 tabular-nums"><Money :value="contest.prize_pool" :currency="'KES'" /></span>
            </div>

            <!-- Deadline + call to action -->
            <footer class="relative flex items-center justify-between gap-3 border-t border-white/[0.06] px-5 py-4">
                <div class="min-w-0" :title="formatAbsolute(contest.entry_deadline_at)">
                    <p class="text-xs text-slate-500">Entries close</p>
                    <p class="text-base font-black tabular-nums" :class="msLeft <= 0 ? 'text-slate-500' : isUrgent ? 'text-amber-300' : 'text-white'">{{ timeLeft }}</p>
                </div>

                <span
                    class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-5 text-sm font-black transition-all duration-200 group-hover:gap-3"
                    :class="contest.is_joined ? 'border border-emerald-400/40 bg-emerald-400/10 text-emerald-200' : 'bg-amber-400 text-[#070b14] shadow-[0_10px_30px_-12px_rgba(251,191,36,0.8)]'"
                >
                    {{ contest.is_joined ? 'View contest' : 'Join contest' }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </span>
            </footer>
        </article>
    </Link>
</template>