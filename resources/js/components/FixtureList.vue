<template>
    <div class="overflow-hidden rounded- border border-white/[0.08] bg-white/[0.03] backdrop-blur-sm">
        <!-- DESKTOP COLUMN HEADERS: generated from the same market list as the rows, so they always line up -->
        <div :class="['hidden items-end gap-3 border-b border-white/[0.06] bg-black/25 px-5 py-3 lg:grid', GRID]">
            <p class="text-xs font-semibold text-slate-400">Match</p>
            <div v-for="m in MARKETS" :key="m.type" class="text-center">
                <p class="mb-1.5 text-xs font-bold text-slate-200">{{ m.title }}</p>
                <div class="grid gap-1.5" :style="{ gridTemplateColumns: `repeat(${m.options.length}, minmax(0, 1fr))` }">
                    <span v-for="o in m.options" :key="o.key" class="text-xs text-slate-400">{{ o.label }}</span>
                </div>
            </div>
            <span></span>
        </div>

        <!-- EMPTY STATE -->
        <div v-if="filteredLeagues.length === 0" class="flex flex-col items-center gap-3 px-6 py-14 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/[0.05] text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z" />
                </svg>
            </span>
            <p class="text-base font-bold text-slate-200">
                {{ totalFixtures === 0 ? 'No fixtures available right now.' : 'No fixtures match your search.' }}
            </p>
            <p v-if="totalFixtures !== 0" class="text-sm text-slate-400">Try a different team name.</p>
        </div>

        <!-- LEAGUE BLOCK -->
        <template v-else>
            <section v-for="league in filteredLeagues" :key="league.id" class="border-b border-white/[0.06] last:border-none">
                <!-- LEAGUE HEADER -->
                <button
                    type="button"
                    @click="toggleLeague(league.id)"
                    :aria-expanded="isLeagueExpanded(league.id)"
                    :aria-controls="`league-${league.id}`"
                    class="flex w-full cursor-pointer items-center justify-between gap-3 bg-white/[0.03] px-5 py-4 text-left transition-colors select-none hover:bg-white/[0.06] focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none focus-visible:ring-inset"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="h-2.5 w-2.5 shrink-0 rounded-full transition-all"
                            :class="isLeagueExpanded(league.id) ? 'bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.7)]' : 'bg-white/25'"
                        ></span>
                        <div class="min-w-0">
                            <h4 class="truncate text-base font-bold text-white">{{ league.name }}</h4>
                            <p class="text-xs text-slate-400">
                                {{ league.country }} · {{ league.fixtures?.length ?? 0 }}
                                {{ (league.fixtures?.length ?? 0) === 1 ? 'match' : 'matches' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <span v-if="pickedInLeague(league) > 0" class="rounded-full bg-amber-400 px-2.5 py-1 text-xs font-black text-[#070b14] tabular-nums">
                            {{ pickedInLeague(league) }} picked
                        </span>
                        <span
                            :class="['text-slate-400 transition-transform duration-200 ease-out', isLeagueExpanded(league.id) ? 'rotate-180 text-amber-300' : '']"
                            aria-hidden="true"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </span>
                    </div>
                </button>

                <!-- LEAGUE FIXTURES (COLLAPSIBLE) -->
                <Transition
                    enter-active-class="transition-opacity duration-300 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <ul v-show="isLeagueExpanded(league.id)" :id="`league-${league.id}`" class="divide-y divide-white/[0.05]">
                        <li v-for="match in league.fixtures" :key="match.id">
                            <!-- DESKTOP VIEW -->
                            <div :class="['hidden items-center gap-3 px-5 py-3.5 transition-colors hover:bg-white/[0.03] lg:grid', GRID]">
                                <div class="min-w-0 space-y-1.5">
                                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-400">
                                        <span v-if="match.boosted" class="inline-flex items-center gap-1 rounded-md bg-amber-400 px-1.5 py-0.5 text-xs font-black text-[#070b14]">
                                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3" aria-hidden="true"><path d="M11.3 1.05a.75.75 0 0 1 .4.82L10.6 8h4.65a.75.75 0 0 1 .58 1.23l-7 8.5a.75.75 0 0 1-1.32-.6L8.4 12H3.75a.75.75 0 0 1-.58-1.23l7-8.5a.75.75 0 0 1 1.13-.22Z" /></svg>
                                            Boosted odds
                                        </span>
                                        <span class="font-semibold text-amber-300">{{ match.date }}</span>
                                        <span class="text-white/20" aria-hidden="true">|</span>
                                        <span class="tabular-nums">ID {{ match.id_on_api }}</span>
                                    </p>
                                    <div class="text-sm font-bold text-white">
                                        <p class="truncate">{{ match.home_team }}</p>
                                        <p class="truncate">{{ match.away_team }}</p>
                                    </div>
                                </div>

                                <div v-for="m in MARKETS" :key="m.type" class="grid gap-1.5" :style="{ gridTemplateColumns: `repeat(${m.options.length}, minmax(0, 1fr))` }">
                                    <button
                                        v-for="o in m.options"
                                        :key="o.key"
                                        type="button"
                                        :disabled="!hasOdd(match, o)"
                                        :aria-pressed="isSelected(match.id, m.type, o.key)"
                                        :aria-label="`${m.title}: ${o.label}, odds ${formatOdd(oddFor(match, o)?.value)}`"
                                        @click.stop.prevent="() => handleSelectionMade(oddFor(match, o), match, m.type, o.arg)"
                                        :class="[ODD_BTN, isSelected(match.id, m.type, o.key) ? ODD_ON : ODD_OFF]"
                                    >
                                        {{ formatOdd(oddFor(match, o)?.value) }}
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="viewFixture(match.id_on_api)"
                                    :title="`${match.additional_markets_count} more markets`"
                                    :aria-label="`More markets for ${match.home_team} v ${match.away_team}`"
                                    class="flex h-9 w-9 cursor-pointer items-center justify-center justify-self-end rounded-full border border-white/10 bg-white/[0.04] text-slate-400 transition-all hover:border-amber-300/60 hover:text-amber-300 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                            </div>

                            <!-- MOBILE VIEW -->
                            <div class="space-y-4 px-4 py-5 lg:hidden">
                                <div class="flex items-start justify-between gap-3">
                                    <button type="button" class="min-w-0 cursor-pointer text-left focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none" @click="viewFixture(match.id_on_api)">
                                        <span v-if="match.boosted" class="mb-1.5 inline-flex items-center gap-1 rounded-md bg-amber-400 px-1.5 py-0.5 text-xs font-black text-[#070b14]">
                                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3" aria-hidden="true"><path d="M11.3 1.05a.75.75 0 0 1 .4.82L10.6 8h4.65a.75.75 0 0 1 .58 1.23l-7 8.5a.75.75 0 0 1-1.32-.6L8.4 12H3.75a.75.75 0 0 1-.58-1.23l7-8.5a.75.75 0 0 1 1.13-.22Z" /></svg>
                                            Boosted odds
                                        </span>
                                        <span class="block text-base font-bold text-white">{{ match.home_team }}</span>
                                        <span class="block text-base font-bold text-slate-300">{{ match.away_team }}</span>
                                        <span class="mt-1.5 flex items-center gap-2 text-xs text-slate-400">
                                            <span class="font-semibold text-amber-300">{{ match.date }}</span>
                                            <span class="text-white/20" aria-hidden="true">|</span>
                                            <span class="tabular-nums">ID {{ match.id_on_api }}</span>
                                        </span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="viewFixture(match.id_on_api)"
                                        class="inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-1 rounded-xl border border-white/10 bg-white/[0.04] px-3 text-sm font-bold text-slate-300 transition-colors hover:border-amber-300/60 hover:text-amber-300 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
                                    >
                                        +{{ match.additional_markets_count }}
                                        <span class="text-xs font-semibold text-slate-400">markets</span>
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div v-for="(m, mi) in MARKETS" :key="m.type" :class="mi < 2 ? 'col-span-2' : 'col-span-1'">
                                        <p class="mb-1.5 text-xs font-semibold text-slate-400">{{ m.title }}</p>
                                        <div class="grid gap-1.5" :style="{ gridTemplateColumns: `repeat(${m.options.length}, minmax(0, 1fr))` }">
                                            <button
                                                v-for="o in m.options"
                                                :key="o.key"
                                                type="button"
                                                :disabled="!hasOdd(match, o)"
                                                :aria-pressed="isSelected(match.id, m.type, o.key)"
                                                :aria-label="`${m.title}: ${o.mobileLabel ? o.mobileLabel(match) : o.label}, odds ${formatOdd(oddFor(match, o)?.value)}`"
                                                @click.stop.prevent="() => handleSelectionMade(oddFor(match, o), match, m.type, o.arg)"
                                                :class="[ODD_BTN, 'flex min-h-14 flex-col items-center justify-center gap-0.5 px-1', isSelected(match.id, m.type, o.key) ? ODD_ON : ODD_OFF]"
                                            >
                                                <span class="w-full truncate px-0.5 text-xs font-semibold" :class="isSelected(match.id, m.type, o.key) ? 'text-[#070b14]/70' : 'text-slate-400'">
                                                    {{ o.mobileLabel ? o.mobileLabel(match) : o.label }}
                                                </span>
                                                <span class="text-base">{{ formatOdd(oddFor(match, o)?.value) }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </Transition>
            </section>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    leagues: { type: Array, required: true, default: () => [] },
    search: { type: String, default: '' },
    defaultExpandedLeagueId: { type: [Number, String], default: 1 },
});

// ── MARKETS ───────────────────────────────────────────────────
// One description of every market, used for the desktop headers, the desktop rows and the mobile blocks.
//   key : what isSelected() matches on (the stored selection value)
//   arg : the 4th argument handleSelectionMade() expects (an index for 3-way/double chance, the label otherwise)
//   path: where the odd lives inside match.odds
const MARKETS = [
    {
        type: 'three_way',
        title: '3 way',
        options: [
            { key: 'Home', label: 'Home', arg: 0, path: ['three_way', 'home'], mobileLabel: (m) => m.home_team },
            { key: 'Draw', label: 'Draw', arg: 1, path: ['three_way', 'draw'] },
            { key: 'Away', label: 'Away', arg: 2, path: ['three_way', 'away'], mobileLabel: (m) => m.away_team },
        ],
    },
    {
        type: 'double_chance',
        title: 'Double chance',
        options: [
            { key: 'one_x', label: '1 or X', arg: 0, path: ['double_chance', 'one_x'] },
            { key: 'x_two', label: 'X or 2', arg: 1, path: ['double_chance', 'x_two'] },
            { key: 'one_two', label: '1 or 2', arg: 2, path: ['double_chance', 'one_two'] },
        ],
    },
    {
        type: 'over_under_2.5',
        title: 'Over/Under 2.5',
        options: [
            { key: 'Over', label: 'Over', arg: 'Over', path: ['over_under', 'over'] },
            { key: 'Under', label: 'Under', arg: 'Under', path: ['over_under', 'under'] },
        ],
    },
    {
        type: 'both_team_to_score',
        title: 'Both teams to score',
        options: [
            { key: 'Yes', label: 'Yes', arg: 'Yes', path: ['both_team_to_score', 'yes'], mobileLabel: () => 'Yes (GG)' },
            { key: 'No', label: 'No', arg: 'No', path: ['both_team_to_score', 'no'], mobileLabel: () => 'No (NG)' },
        ],
    },
];

// Desktop columns: match | 3 way | double chance | over/under | btts | more-markets button.
// Proportional to the number of buttons in each market, so every button is about the same width.
const GRID = 'lg:grid-cols-[minmax(0,3fr)_minmax(0,2.4fr)_minmax(0,2.4fr)_minmax(0,1.6fr)_minmax(0,1.6fr)_2.25rem]';

// Odds buttons (full class strings so Tailwind can see them)
const ODD_BTN =
    'min-h-11 w-full cursor-pointer rounded-xl border text-sm font-black tabular-nums transition-all duration-150 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95 disabled:pointer-events-none disabled:opacity-40';
const ODD_OFF = 'border-white/[0.08] bg-white/[0.04] text-white hover:border-amber-300/50 hover:bg-white/[0.09]';
const ODD_ON = 'border-amber-400 bg-amber-400 text-[#070b14] shadow-[0_8px_20px_-8px_rgba(251,191,36,0.8)]';

const oddFor = (match, option) => option.path.reduce((o, k) => o?.[k], match.odds);
const hasOdd = (match, option) => Number.isFinite(Number(oddFor(match, option)?.value));

const filteredLeagues = computed(() => {
    const q = props.search.trim().toLowerCase();

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

const searchActive = computed(() => props.search.trim() !== '');

// ── ACCORDION ─────────────────────────────────────────────────
const expandedLeagueIds = ref(new Set([props.defaultExpandedLeagueId]));

const isLeagueExpanded = (id) => {
    if (searchActive.value) return true;
    return expandedLeagueIds.value.has(id);
};

const toggleLeague = (id) => {
    if (expandedLeagueIds.value.has(id)) {
        expandedLeagueIds.value.delete(id);
    } else {
        expandedLeagueIds.value.add(id);
    }
};

// ── SELECTIONS ────────────────────────────────────────────────
const selections = ref([]);

const selectedSet = computed(() => {
    const set = new Set();
    selections.value.forEach((sel) => {
        set.add(`${sel.fixture_id}-${sel.market_name}-${sel.selection}`);
    });
    return set;
});

const isSelected = (fixture_id, market_name, selectionx) => {
    return selectedSet.value.has(`${fixture_id}-${market_name}-${selectionx}`);
};

// How many fixtures in this league already have a pick, shown on the league header.
const pickedInLeague = (league) => {
    const picked = new Set(selections.value.map((s) => s.fixture_id));
    return (league.fixtures ?? []).filter((f) => picked.has(f.id)).length;
};

const viewFixture = (id) => {
    window.location.href = `/fixture/${id}`;
};

const formatOdd = (value) => {
    const n = Number(value);
    return Number.isFinite(n) ? n.toFixed(2) : '—';
};

const updateSelections = () => {
    selections.value = getSelectionsFromLocalStorage();
};

const handleSelectionMade = (odd, match, type, index) => {
    const value = Number(odd?.value);

    if (!odd || !Number.isFinite(value)) {
        console.error('⚠️ Invalid odd value:', odd);
        return;
    }

    let odd_value = null;
    if (type === 'three_way') {
        odd_value = ['Home', 'Draw', 'Away'][index];
    } else if (type === 'double_chance') {
        odd_value = ['one_x', 'x_two', 'one_two'][index];
    } else if (type === 'over_under_2.5') {
        odd_value = index;
    } else if (type === 'both_team_to_score') {
        odd_value = index;
    }

    const selection = {
        fixture_id: match.id,
        home_team: match.home_team,
        away_team: match.away_team,
        market_name: type,
        selection: odd_value,
        odds: { ...odd, value },
    };

    saveSelectionToLocalStorage(selection);
};

const saveSelectionToLocalStorage = (selection) => {
    try {
        const stored_selections = localStorage.getItem('bet_selections');
        let selections = [];

        if (stored_selections) {
            selections = JSON.parse(stored_selections);
            if (!Array.isArray(selections)) {
                selections = [];
            }
        }

        const existingIndex = selections.findIndex(
            (item) =>
                item.fixture_id === selection.fixture_id &&
                item.market_name === selection.market_name,
        );

        if (existingIndex !== -1) {
            selections.splice(existingIndex, 1);
        }

        selections.push(selection);

        localStorage.setItem('bet_selections', JSON.stringify(selections));

        window.dispatchEvent(
            new CustomEvent('betSelectionUpdated', {
                detail: { selections, selection },
            }),
        );
    } catch (error) {
        console.error('❌ Error saving selection to localStorage:', error);
    }
};

const getSelectionsFromLocalStorage = () => {
    try {
        const stored_selections = localStorage.getItem('bet_selections');
        if (stored_selections) {
            const selections = JSON.parse(stored_selections);
            return Array.isArray(selections) ? selections : [];
        }
        return [];
    } catch (error) {
        console.error('❌ Error reading selections from localStorage:', error);
        return [];
    }
};

onMounted(() => {
    updateSelections();
    window.addEventListener('betSelectionUpdated', updateSelections);
});

onBeforeUnmount(() => {
    window.removeEventListener('betSelectionUpdated', updateSelections);
});
</script>