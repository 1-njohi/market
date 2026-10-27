<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Money from '@/components/Money.vue';
import { useTicker } from '@/composables/useTicker';

// One card for both open matchdays and open jackpots. The section turns each payload
// into this shape, so the card never needs to know which one it is showing.
interface Preview {
    home_team: string;
    away_team: string;
    kickoff: string | null;
    market_name: string;
}

interface Round {
    key: string;
    href: string;
    accent: 'sky' | 'purple';
    chip: string; // "Matchday 1", "Jackpot"
    perk: string; // shown top-right when you haven't joined, e.g. "No entry fee"
    title: string;
    subtitle: string | null;
    legs: number;
    entries: number;
    entriesLabel: string; // "Entered" / "Playing"
    prizePool: number;
    preview: Preview[];
    deadline: string;
    deadlineLabel: string; // "Locks in" / "Entries close"
    isJoined: boolean;
    ctaLabel: string;
}

const props = withDefaults(
    defineProps<{
        round: Round;
        wide?: boolean; // desktop two-column layout, for when it is the only card
    }>(),
    { wide: false },
);

const { now } = useTicker();

// Full class strings so Tailwind can find them.
const ACCENTS = {
    sky: {
        border: 'border-sky-400/25 group-hover:border-sky-300/60 group-hover:shadow-[0_24px_60px_-28px_rgba(56,189,248,0.5)]',
        glow: 'bg-sky-400/15',
        chip: 'bg-sky-400/10 text-sky-300',
    },
    purple: {
        border: 'border-purple-400/25 group-hover:border-purple-300/60 group-hover:shadow-[0_24px_60px_-28px_rgba(192,132,252,0.5)]',
        glow: 'bg-purple-400/15',
        chip: 'bg-purple-400/10 text-purple-300',
    },
};
const accent = computed(() => ACCENTS[props.round.accent]);

const formatAbsolute = (iso: string): string =>
    new Date(iso).toLocaleString('en-KE', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });

const formatKickoff = (iso: string | null): string => (iso ? formatAbsolute(iso) : 'TBD');

const msLeft = computed<number>(() => new Date(props.round.deadline).getTime() - now.value);

const timeLeft = computed<string>(() => {
    const ms = msLeft.value;
    if (ms <= 0) return 'Closed';

    const mins = Math.floor(ms / 60_000);
    const hours = Math.floor(mins / 60);
    const days = Math.floor(hours / 24);

    if (days >= 1) return `${days}d ${hours % 24}h`;
    if (hours >= 1) return `${hours}h ${mins % 60}m`;
    return `${mins}m`;
});

// Under an hour reads as urgent.
const isUrgent = computed<boolean>(() => msLeft.value > 0 && msLeft.value < 60 * 60 * 1000);

// The preview holds a few legs; say how many more the round has.
const moreLegs = computed<number>(() => Math.max(0, props.round.legs - props.round.preview.length));
</script>

<template>
    <Link :href="round.href" class="group block h-full rounded-3xl focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none">
        <article
            class="relative grid h-full grid-cols-1 overflow-hidden rounded-3xl border bg-[#0b1220] transition-all duration-300 group-hover:-translate-y-1 group-active:scale-[0.99]"
            :class="[accent.border, wide ? 'lg:grid-cols-[1.1fr_1fr]' : '']"
        >
            <div class="pointer-events-none absolute -top-16 -right-12 h-44 w-44 rounded-full blur-3xl" :class="accent.glow" aria-hidden="true"></div>

            <!-- Top: what it is + status, then the title -->
            <div class="relative px-5 pt-5 pb-4" :class="wide ? 'lg:col-start-1 lg:row-start-1 lg:p-7' : ''">
                <div class="flex items-center justify-between gap-3">
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="accent.chip">{{ round.chip }}</span>
                    <span v-if="round.isJoined" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-bold text-emerald-300">
                        <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.5 6.5l2.2 2.2L9.5 3.8" />
                        </svg>
                        You're in
                    </span>
                    <span v-else class="text-xs font-semibold text-slate-500">{{ round.perk }}</span>
                </div>

                <h3 class="mt-4 line-clamp-2 text-xl leading-snug font-black tracking-tight text-white" :class="wide ? 'lg:text-3xl' : ''">{{ round.title }}</h3>
                <p v-if="round.subtitle" class="mt-2 text-sm leading-relaxed text-slate-400" :class="wide ? '' : 'line-clamp-2'">{{ round.subtitle }}</p>
            </div>

            <!-- What you'd be picking: every leg shows its own market -->
            <div
                v-if="round.preview.length > 0"
                class="relative px-5 pb-4"
                :class="wide ? 'lg:col-start-2 lg:row-span-3 lg:row-start-1 lg:flex lg:flex-col lg:justify-center lg:border-l lg:border-white/[0.06] lg:bg-black/20 lg:p-7' : ''"
            >
                <p class="mb-2 hidden text-sm font-semibold text-slate-300" :class="wide ? 'lg:block' : ''">What you'll pick</p>
                <ul class="divide-y divide-white/[0.05] overflow-hidden rounded-2xl border border-white/[0.06] bg-white/[0.03]">
                    <li v-for="(f, i) in round.preview" :key="i" class="px-3.5 py-3">
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
            <dl class="relative grid grid-cols-2 divide-x divide-white/[0.06] border-t border-white/[0.06] bg-black/20" :class="wide ? 'lg:col-start-1 lg:row-start-2' : ''">
                <div class="px-3 py-3 text-center">
                    <dt class="text-xs text-slate-500">Legs</dt>
                    <dd class="mt-0.5 text-lg font-black text-white tabular-nums">{{ round.legs }}</dd>
                </div>
                <div class="px-3 py-3 text-center">
                    <dt class="text-xs text-slate-500">{{ round.entriesLabel }}</dt>
                    <dd class="mt-0.5 text-lg font-black text-white tabular-nums">{{ round.entries }}</dd>
                </div>
            </dl>

            <!-- Deadline + call to action (prize pool joins when there is one) -->
            <footer class="relative border-t border-white/[0.06]" :class="wide ? 'lg:col-start-1 lg:row-start-3' : ''">
                <div v-if="round.prizePool > 0" class="flex items-center justify-between border-b border-white/[0.06] bg-amber-400/5 px-5 py-3">
                    <span class="text-sm text-slate-400">Prize pool</span>
                    <span class="text-base font-black text-amber-300 tabular-nums"><Money :value="round.prizePool" :currency="'KES'" /></span>
                </div>

                <div class="flex items-center justify-between gap-3 px-5 py-4" :class="wide ? 'lg:px-7' : ''">
                    <div class="min-w-0" :title="formatAbsolute(round.deadline)">
                        <p class="text-xs text-slate-500">{{ round.deadlineLabel }}</p>
                        <p class="text-base font-black tabular-nums" :class="msLeft <= 0 ? 'text-slate-500' : isUrgent ? 'text-amber-300' : 'text-white'">{{ timeLeft }}</p>
                    </div>

                    <span
                        class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-5 text-sm font-black transition-all duration-200 group-hover:gap-3"
                        :class="round.isJoined ? 'border border-emerald-400/40 bg-emerald-400/10 text-emerald-200' : 'bg-amber-400 text-[#070b14] shadow-[0_10px_30px_-12px_rgba(251,191,36,0.8)]'"
                    >
                        {{ round.ctaLabel }}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                </div>
            </footer>
        </article>
    </Link>
</template>