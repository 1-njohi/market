<template>
    <div class="min-h-screen bg-[#070b14] px-4 pt-6 pb-16 font-sans text-slate-200 selection:bg-amber-500/30 selection:text-white lg:px-8 lg:pt-8 lg:pb-12">
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- Back -->
            <Link
                href="/fixtures"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl pr-3 text-sm font-bold text-slate-400 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                All fixtures
            </Link>

            <!-- ═══════════════ MATCH HEADER ═══════════════ -->
            <header class="relative overflow-hidden rounded-3xl border border-white/[0.08] bg-[#0e1422]/80 px-5 py-8 sm:px-8 sm:py-10">
                <!-- Half-pitch arc, as on the landing hero -->
                <svg
                    class="pointer-events-none absolute -right-24 bottom-0 h-[150%] w-auto text-white/[0.05]"
                    viewBox="0 0 600 800"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <circle cx="600" cy="400" r="260" />
                    <circle cx="600" cy="400" r="6" fill="currentColor" />
                    <rect x="380" y="230" width="240" height="340" />
                </svg>
                <div class="pointer-events-none absolute -top-24 left-1/4 h-64 w-64 rounded-full bg-sky-500/15 blur-[110px]" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -right-10 -bottom-24 h-56 w-56 rounded-full bg-amber-400/15 blur-[110px]" aria-hidden="true"></div>

                <div class="relative">
                    <p class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
                        <span v-if="fixture?.boosted" class="inline-flex items-center gap-1 rounded-md bg-amber-400 px-2 py-0.5 text-xs font-black text-[#070b14]">
                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3" aria-hidden="true"><path d="M11.3 1.05a.75.75 0 0 1 .4.82L10.6 8h4.65a.75.75 0 0 1 .58 1.23l-7 8.5a.75.75 0 0 1-1.32-.6L8.4 12H3.75a.75.75 0 0 1-.58-1.23l7-8.5a.75.75 0 0 1 1.13-.22Z" /></svg>
                            Boosted odds
                        </span>
                        <span v-if="fixture?.date" class="font-semibold text-amber-300">{{ fixture.date }}</span>
                        <span v-if="fixture?.id_on_api" class="text-slate-500 tabular-nums">ID {{ fixture.id_on_api }}</span>
                    </p>

                    <h1 class="mt-4 text-3xl leading-tight font-black tracking-tight text-white sm:text-5xl">
                        <template v-if="fixture">
                            {{ fixture.home_team }}
                            <span class="mx-1 text-2xl font-bold text-slate-600 sm:text-3xl">v</span>
                            <span class="block sm:inline">{{ fixture.away_team }}</span>
                        </template>
                        <template v-else>Loading match…</template>
                    </h1>

                    <p v-if="pickedCount > 0" class="mt-5 inline-flex items-center gap-2 rounded-full bg-amber-400/10 px-3.5 py-1.5 text-sm font-bold text-amber-200 ring-1 ring-amber-300/25">
                        <span class="h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_10px_rgba(252,211,77,0.8)]"></span>
                        <span class="tabular-nums">{{ pickedCount }}</span>
                        {{ pickedCount === 1 ? 'pick' : 'picks' }} on this match
                    </p>
                </div>
            </header>

            <!-- ═══════════════ MARKET FILTER ═══════════════ -->
            <div v-if="normalizedMarkets.length > 6" class="relative md:max-w-sm">
                <label for="market-search" class="sr-only">Find a market</label>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z" />
                    </svg>
                </div>
                <input
                    id="market-search"
                    v-model="marketQuery"
                    type="search"
                    inputmode="search"
                    autocomplete="off"
                    placeholder="Find a market…"
                    class="min-h-12 w-full rounded-xl border border-white/10 bg-white/[0.04] py-2 pr-4 pl-11 text-base font-medium text-white placeholder-slate-500 transition-all focus:border-amber-300/60 focus:bg-white/[0.06] focus:ring-2 focus:ring-amber-300/20 focus:outline-none [&::-webkit-search-cancel-button]:hidden"
                />
            </div>

            <!-- ═══════════════ MARKETS ═══════════════ -->
            <div v-if="visibleMarkets.length > 0" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <section
                    v-for="market in visibleMarkets"
                    :key="market.rawName"
                    class="overflow-hidden rounded-3xl border border-white/[0.08] bg-white/[0.03]"
                    :aria-label="prettyName(market.displayName)"
                >
                    <header class="flex items-center justify-between gap-3 border-b border-white/[0.06] bg-black/25 px-5 py-3.5">
                        <h2 class="text-base font-bold text-white">{{ prettyName(market.displayName) }}</h2>
                        <span class="text-xs text-slate-500 tabular-nums">{{ market.outcomes.length }} {{ market.outcomes.length === 1 ? 'option' : 'options' }}</span>
                    </header>

                    <div class="grid gap-2 p-4" :class="market.gridClasses">
                        <button
                            v-for="(outcome, index) in market.outcomes"
                            :key="outcome.id"
                            type="button"
                            :disabled="!hasValue(outcome)"
                            :aria-pressed="isSelected(fixture.id, market.rawName, outcome.label)"
                            :aria-label="`${prettyName(market.displayName)}: ${outcome.label}, odds ${formatOdd(outcome.value)}`"
                            @click="handleSelectionMade(outcome, fixture, market.rawName, index)"
                            :class="[ODD_BTN, isSelected(fixture.id, market.rawName, outcome.label) ? ODD_ON : ODD_OFF]"
                        >
                            <span
                                class="line-clamp-2 w-full px-1 text-center text-xs leading-tight font-semibold"
                                :class="isSelected(fixture.id, market.rawName, outcome.label) ? 'text-[#070b14]/70' : 'text-slate-400'"
                            >{{ outcome.label }}</span>
                            <span class="text-base">{{ formatOdd(outcome.value) }}</span>
                        </button>
                    </div>
                </section>
            </div>

            <!-- Empty states -->
            <div v-else class="flex flex-col items-center gap-3 rounded-3xl border border-white/[0.08] bg-white/[0.03] px-6 py-14 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/[0.05] text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z" />
                    </svg>
                </span>
                <p class="text-base font-bold text-slate-200">
                    {{ normalizedMarkets.length === 0 ? 'No markets available for this fixture yet.' : 'No markets match your search.' }}
                </p>
                <p v-if="normalizedMarkets.length !== 0" class="text-sm text-slate-400">Try a different market name.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const selections = ref([]);
const marketQuery = ref('');

const page = usePage();
// Dynamic Inertia layout tracking payload
const fixture = computed(() => page.props.fixture);

// Odds buttons (full class strings so Tailwind can see them). Same look as the fixtures list.
const ODD_BTN =
    'flex min-h-14 w-full cursor-pointer flex-col items-center justify-center gap-0.5 rounded-xl border px-1 font-black tabular-nums transition-all duration-150 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95 disabled:pointer-events-none disabled:opacity-40';
const ODD_OFF = 'border-white/[0.08] bg-white/[0.04] text-white hover:border-amber-300/50 hover:bg-white/[0.09]';
const ODD_ON = 'border-amber-400 bg-amber-400 text-[#070b14] shadow-[0_8px_20px_-8px_rgba(251,191,36,0.8)]';

const isSelected = (fixture_id, market_name, selectionx) => {
    return selectedSet.value.has(`${fixture_id}-${market_name}-${selectionx}`);
};

// Create a Set for O(1) lookups
const selectedSet = computed(() => {
    const set = new Set();
    selections.value.forEach((sel) => {
        set.add(`${sel.fixture_id}-${sel.market_name}-${sel.selection}`);
    });
    return set;
});

// How many picks you already have on this match, shown in the header.
const pickedCount = computed(() =>
    fixture.value ? selections.value.filter((s) => s.fixture_id === fixture.value.id).length : 0,
);

const handleSelectionMade = (odd, match, type, index) => {
    if (odd === undefined || odd === null || isNaN(odd.value)) {
        console.error('⚠️ Invalid odd value:', odd);
        return;
    }

    const selection = {
        fixture_id: match.id,
        home_team: match.home_team,
        away_team: match.away_team,
        market_name: type,
        selection: odd.label,
        odds: odd,
    };

    saveSelectionToLocalStorage(selection);
};

const saveSelectionToLocalStorage = (selection) => {
    try {
        // Get existing selections from localStorage
        const stored_selections = localStorage.getItem('bet_selections');
        let selections = [];

        if (stored_selections) {
            selections = JSON.parse(stored_selections);
            // Ensure it's an array
            if (!Array.isArray(selections)) {
                selections = [];
            }
        }

        // Check if this selection already exists (optional - to prevent duplicates)
        const exists = selections.some(
            (item) =>
                item.fixture_id === selection.fixture_id &&
                item.market_name === selection.market_name,
        );

        if (exists) {
            // Check if this selection already exists
            const existingIndex = selections.findIndex(
                (item) =>
                    item.fixture_id === selection.fixture_id &&
                    item.market_name === selection.market_name,
            );

            if (existingIndex !== -1) {
                // Remove the existing selection
                selections.splice(existingIndex, 1);
            }

            // Add new selection to the end of the array
            selections.push(selection);

            // Save back to localStorage
            localStorage.setItem('bet_selections', JSON.stringify(selections));

            // A custom event so other components can react
            window.dispatchEvent(
                new CustomEvent('betSelectionUpdated', {
                    detail: { selections, selection },
                }),
            );
        } else {
            // Add new selection to the end of the array
            selections.push(selection);

            // Save back to localStorage
            localStorage.setItem('bet_selections', JSON.stringify(selections));
        }

        // A custom event so other components can react
        window.dispatchEvent(
            new CustomEvent('betSelectionUpdated', {
                detail: { selections, selection },
            }),
        );
    } catch (error) {
        console.error('❌ Error saving selection to localStorage:', error);
    }
};

// Helper function to get all selections from localStorage
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

const updateSelections = () => {
    selections.value = getSelectionsFromLocalStorage();
};

onMounted(() => {
    updateSelections();
    window.addEventListener('betSelectionUpdated', updateSelections);
});

onBeforeUnmount(() => {
    window.removeEventListener('betSelectionUpdated', updateSelections);
});
// Expose to window for debugging
window.handleSelectionMade = handleSelectionMade;

// Display helpers
const hasValue = (outcome) => Number.isFinite(Number(outcome.value)) && Number(outcome.value) > 0;
const formatOdd = (value) => (Number.isFinite(Number(value)) && Number(value) > 0 ? Number(value).toFixed(2) : '—');
const prettyName = (name) => {
    const t = String(name);
    return t.charAt(0).toUpperCase() + t.slice(1);
};

// Global dictionary map fixing ugly lower-case API string values into presentation labels
const labelDictionary = {
    yes: 'Yes',
    no: 'No',
    home: 'Home',
    draw: 'Draw',
    away: 'Away',
    odd: 'Odd',
    even: 'Even',
    one_x: '1 or X',
    x_two: 'X or 2',
    one_two: '1 or 2',
    home_draw: '1 or X',
    draw_away: 'X or 2',
    home_away: '1 or 2',
    home_home: 'Home/Home',
    home_draw: 'Home/Draw',
    home_away: 'Home/Away',
    draw_home: 'Draw/Home',
    draw_draw: 'Draw/Draw',
    draw_away: 'Draw/Away',
    away_home: 'Away/Home',
    away_draw: 'Away/Draw',
    away_away: 'Away/Away',
    home_yes: 'Home & Yes',
    home_no: 'Home & No',
    draw_yes: 'Draw & Yes',
    draw_no: 'Draw & No',
    away_yes: 'Away & Yes',
    away_no: 'Away & No',
};

// COMPUTED LAYER: Normalizes both arrays and mixed object payloads into unified rendering contracts
const normalizedMarkets = computed(() => {
    if (!fixture.value?.odds) return [];

    const marketIdMap = {
        three_way: 1,
        home_away: 2,
        second_half_winner: 3,
        goals_over_under: 5,
        goals_over_under_first_half: 6,
        ht_ft_double: 7,
        both_teams_score: 8,
        handicap_result: 9,
        exact_score: 10,
        double_chance: 12,
        first_half_winner: 13,
        team_to_score_first: 14,
        team_to_score_last: 15,
        total_home: 16,
        total_away: 17,
        double_chance_first_half: 20,
        odd_even: 21,
        odd_even_first_half: 22,
        results_both_teams_score: 24,
        result_total_goals: 25,
        goals_over_under_second_half: 26,
        win_to_nil_home: 29,
        win_to_nil_away: 30,
        win_both_halves: 32,
        both_teams_score_first_half: 34,
        exact_goals_number: 38,
        home_exact_goals_number: 40,
        away_exact_goals_number: 41,
        second_half_exact_goals_number: 42,
        home_score_goal: 43,
        away_score_goal: 44,
        exact_goals_number_first_half: 46,
    };

    return Object.entries(fixture.value.odds).map(([marketKey, rawData]) => {
        let outcomes = [];

        if (Array.isArray(rawData)) {
            outcomes = rawData
                .filter(item => item !== null) // Skip null items
                .map((item, idx) => ({
                    id: item.id || `${marketKey}_${idx}`,
                    value: item.value,
                    label: item.label || `${idx}`,
                }));
        } else if (typeof rawData === 'object' && rawData !== null) {
            outcomes = Object.entries(rawData)
                .filter(([key, item]) => item !== null) // Skip null values
                .map(([key, item]) => ({
                    id: item.id || `${marketKey}_${key}`,
                    value: item.value,
                    label: labelDictionary[key] || key.replace(/_/g, ' '),
                }));
        }

        const totalItems = outcomes.length;
        let cols = 3;

        if (totalItems === 2 || totalItems === 4) {
            cols = 2;
        } else if (totalItems >= 6) {
            cols = 3;
        } else if (totalItems === 3) {
            cols = 3;
        }

        return {
            rawName: marketKey,
            marketId: marketIdMap[marketKey] || null,
            displayName: marketKey.replace(/_/g, ' '),
            outcomes,
            cols,
            gridClasses: {
                'grid-cols-2': cols === 2,
                'grid-cols-3': cols === 3,
            },
        };
    });
});

// Markets after the optional search box
const visibleMarkets = computed(() => {
    const q = marketQuery.value.trim().toLowerCase();
    if (q === '') return normalizedMarkets.value;
    return normalizedMarkets.value.filter((m) => m.displayName.toLowerCase().includes(q));
});
</script>