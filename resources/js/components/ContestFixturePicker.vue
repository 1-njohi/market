<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    leagues: { type: Array, required: true, default: () => [] },
    selectedLegs: { type: Array, required: true, default: () => [] },
    maxLegs: { type: Number, default: 50 },
});

const emit = defineEmits(['toggle-leg']);

const search = ref('');
const expandedLeagueIds = ref(new Set());

// Auto-expand the first league that has fixtures.
const firstLeagueId = computed(() => {
    const first = props.leagues.find((l) => (l.fixtures ?? []).length > 0);
    return first?.id ?? null;
});

if (firstLeagueId.value !== null) {
    expandedLeagueIds.value.add(firstLeagueId.value);
}

const isLeagueExpanded = (id) => expandedLeagueIds.value.has(id);

const toggleLeague = (id) => {
    const next = new Set(expandedLeagueIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedLeagueIds.value = next;
};

const filteredLeagues = computed(() => {
    const q = search.value.trim().toLowerCase();

    if (q === '') {
        return props.leagues.filter((l) => (l.fixtures ?? []).length > 0);
    }

    return props.leagues
        .map((league) => ({
            ...league,
            fixtures: (league.fixtures ?? []).filter(
                (f) =>
                    f.home_team.toLowerCase().includes(q) ||
                    f.away_team.toLowerCase().includes(q),
            ),
        }))
        .filter((league) => league.fixtures.length > 0);
});

const totalFixtures = computed(() =>
    props.leagues.reduce((sum, l) => sum + (l.fixtures?.length ?? 0), 0),
);

/**
 * Find the selected leg for a (fixture, market) pair, or null.
 */
const currentSelection = (fixtureId, marketId) => {
    const leg = props.selectedLegs.find(
        (l) => l.fixture_id === fixtureId && l.market_id === marketId,
    );
    return leg?.selection ?? null;
};

/**
 * Is this fixture already picked in any market?
 */
const fixtureIsPicked = (fixtureId) =>
    props.selectedLegs.some((l) => l.fixture_id === fixtureId);

/**
 * Are we at the leg cap and this fixture isn't already in the list?
 */
const fixtureIsLocked = (fixtureId) =>
    props.selectedLegs.length >= props.maxLegs &&
    !fixtureIsPicked(fixtureId);

const onPick = (fixture, market, option) => {
    emit('toggle-leg', {
        fixture_id: fixture.id,
        market_id: market.id,
        selection: option.value,
        home_team: fixture.home_team,
        away_team: fixture.away_team,
        market_label: market.label,
        kickoff: fixture.kickoff,
        options: market.options ?? [],
    });
};

const formatKickoff = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('en-KE', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

const formatOdd = (value) => {
    const n = Number(value);
    return Number.isFinite(n) ? n.toFixed(2) : '—';
};
</script>

<template>
    <div
        class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
    >
        <!-- HEADER -->
        <div
            class="flex items-center justify-between border-b border-[#232d42] bg-[#111a30] px-5 py-3"
        >
            <span
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Pick your fixtures
            </span>
            <span
                class="font-mono text-[10px] tracking-widest uppercase"
                :class="
                    selectedLegs.length >= maxLegs
                        ? 'text-rose-400'
                        : selectedLegs.length >= 5
                          ? 'text-emerald-400'
                          : 'text-slate-500'
                "
            >
                {{ selectedLegs.length }} / {{ maxLegs }}
            </span>
        </div>

        <!-- SEARCH -->
        <div class="border-b border-[#232d42]/60 p-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search by team name…"
                class="w-full rounded border border-[#232d42] bg-[#0a101f] px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
            />
        </div>

        <!-- EMPTY -->
        <div
            v-if="filteredLeagues.length === 0"
            class="p-8 text-center text-xs text-slate-500"
        >
            {{
                totalFixtures === 0
                    ? 'No fixtures are currently available to pick.'
                    : 'No fixtures match your search.'
            }}
        </div>

        <!-- LEAGUES -->
        <div v-else>
            <div
                v-for="league in filteredLeagues"
                :key="league.id"
                class="border-b border-[#232d42]/60 last:border-b-0"
            >
                <!-- LEAGUE HEADER -->
                <button
                    type="button"
                    @click="toggleLeague(league.id)"
                    class="flex w-full cursor-pointer items-center justify-between bg-[#111a30]/70 px-5 py-3 text-left transition-colors hover:bg-[#111a30]"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="h-2 w-2 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,166,35,0.4)]"
                        ></span>
                        <div>
                            <p
                                class="text-[11px] font-black tracking-widest text-white uppercase"
                            >
                                {{ league.name }}
                            </p>
                            <p
                                class="text-[9px] font-bold tracking-wider text-slate-500 uppercase"
                            >
                                {{ league.fixtures.length }}
                                {{
                                    league.fixtures.length === 1
                                        ? 'match'
                                        : 'matches'
                                }}
                            </p>
                        </div>
                    </div>
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="3"
                        stroke="currentColor"
                        :class="[
                            'h-3.5 w-3.5 transition-transform duration-200',
                            isLeagueExpanded(league.id)
                                ? 'rotate-180 text-amber-400'
                                : 'text-slate-500',
                        ]"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m19.5 8.25-7.5 7.5-7.5-7.5"
                        />
                    </svg>
                </button>

                <!-- FIXTURES -->
                <div
                    v-show="isLeagueExpanded(league.id)"
                    class="divide-y divide-[#232d42]/40"
                >
                    <div
                        v-for="fixture in league.fixtures"
                        :key="fixture.id"
                        class="p-4 transition-colors"
                        :class="
                            fixtureIsLocked(fixture.id)
                                ? 'opacity-40'
                                : ''
                        "
                    >
                        <!-- FIXTURE HEADER -->
                        <div
                            class="mb-3 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                                >
                                    {{ formatKickoff(fixture.kickoff) }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-sm font-bold text-slate-200"
                                >
                                    {{ fixture.home_team }}
                                    <span class="text-slate-500">vs</span>
                                    {{ fixture.away_team }}
                                </p>
                            </div>
                            <span
                                v-if="fixtureIsPicked(fixture.id)"
                                class="flex-shrink-0 rounded border border-emerald-500/40 bg-emerald-500/10 px-2 py-0.5 font-mono text-[9px] font-black tracking-widest text-emerald-400 uppercase"
                            >
                                Picked
                            </span>
                        </div>

                        <!-- MARKETS -->
                        <div class="space-y-3">
                            <div
                                v-for="market in fixture.markets"
                                :key="market.id"
                            >
                                <p
                                    class="mb-1.5 text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    {{ market.label }}
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="option in market.options"
                                        :key="option.value"
                                        type="button"
                                        :disabled="fixtureIsLocked(fixture.id)"
                                        @click="onPick(fixture, market, option)"
                                        :class="[
                                            'flex flex-1 min-w-[6rem] items-center justify-between gap-2 rounded border px-3 py-2 text-[11px] font-bold tracking-wide uppercase transition-all',
                                            currentSelection(fixture.id, market.id) ===
                                            option.value
                                                ? 'border-amber-500/60 bg-amber-500/10 text-white ring-1 ring-amber-500/40'
                                                : 'border-[#232d42] bg-[#0a101f] text-slate-300 hover:border-sky-500/40 hover:text-sky-400',
                                            fixtureIsLocked(fixture.id)
                                                ? 'cursor-not-allowed'
                                                : 'cursor-pointer',
                                        ]"
                                    >
                                        <span class="truncate">
                                            {{ option.value }}
                                        </span>
                                        <span
                                            class="font-mono text-[10px]"
                                            :class="
                                                currentSelection(
                                                    fixture.id,
                                                    market.id,
                                                ) === option.value
                                                    ? 'text-amber-400'
                                                    : 'text-slate-500'
                                            "
                                        >
                                            {{ formatOdd(option.odd) }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>