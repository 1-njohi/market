<template>
    <div
        class="min-h-screen bg-[#111622] p-2 font-sans text-[#93a3b8] select-none md:p-6"
    >
        <div class="mx-auto max-w-[1200px] space-y-4">
            <div
                class="relative overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a] p-6 text-center"
            >
                <div
                    class="flex items-center justify-between text-[11px] font-bold tracking-wider text-sky-400 uppercase"
                >
                    <span>20/06/24 - 20:00 • ID: 4010</span>
                </div>

                <h1
                    class="mt-4 text-lg font-black tracking-widest text-slate-100 uppercase md:text-2xl"
                >
                    {{
                        fixture
                            ? `${fixture.home_team} - ${fixture.away_team}`
                            : 'Loading Match...'
                    }}
                </h1>
            </div>

            <div
                class="no-scrollbar flex items-center gap-1 overflow-x-auto rounded-lg border-b border-[#232d42] bg-[#121824] p-1"
            >
                <button
                    v-for="tab in ['All']"
                    :key="tab"
                    @click="activeTab = tab"
                    :class="[
                        'shrink-0 cursor-pointer rounded px-4 py-2 text-xs font-black tracking-wider uppercase transition-all',
                        activeTab === tab
                            ? 'border-b-2 border-sky-400 bg-[#242f48] text-white'
                            : 'text-slate-400 hover:bg-[#161c2a] hover:text-slate-200',
                    ]"
                >
                    {{ tab }}
                </button>
            </div>

            <div class="space-y-3">
                <div
                    v-for="market in normalizedMarkets"
                    :key="market.rawName"
                    class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
                >
                    <div
                        class="flex items-center justify-between border-b border-[#232d42] bg-[#121824] px-4 py-2"
                    >
                        <span
                            class="text-[10px] font-black tracking-widest text-slate-200 uppercase"
                        >
                            {{ market.displayName }}
                        </span>
                        <span class="cursor-help text-[10px] text-slate-500"
                            >❓</span
                        >
                    </div>

                    <div
                        class="grid grid-cols-1 gap-1"
                        :class="market.gridClasses"
                    >
                        <button
                            v-for="(outcome, index) in market.outcomes"
                            :key="outcome.id"
                            @click="
                                handleSelectionMade(
                                    outcome,
                                    fixture,
                                    market.rawName,
                                    index,
                                )
                            "
                            :class="[
                                'cursor-pointer rounded border bg-[#242f48] py-2.5 text-center font-bold text-[#38bdf8] transition-all hover:bg-[#2e3c5c]',

                                // Smart column border balancing
                                market.cols === 2
                                    ? 'md:border-b-0 [&:nth-child(odd)]:md:border-r'
                                    : '',
                                market.cols === 3
                                    ? 'md:border-r md:border-b-0 [&:nth-child(3n)]:md:border-r-0'
                                    : '',
                                market.cols === 4
                                    ? 'md:border-r md:border-b-0 [&:nth-child(4n)]:md:border-r-0'
                                    : '',

                                // Gaps row separation padding
                                market.outcomes.length > market.cols
                                    ? 'md:border-b'
                                    : '',

                                isSelected(
                                    fixture.id,
                                    market.rawName,
                                    outcome.label,
                                )
                                    ? 'border-[#ff8c00] bg-[#1e2942] text-white ring-1 ring-[#ff8c00]/30'
                                    : 'border-transparent',
                            ]"
                        >
                            <div
                                class="bg-red500 px-2 md:flex md:justify-between"
                            >
                                <span
                                    class="transtion-colors text-xs font-bold uppercase"
                                    :class="
                                        isSelected(
                                            fixture.id,
                                            market.rawName,
                                            outcome.label,
                                        )
                                            ? 'text-slate-200'
                                            : 'text-slate-400'
                                    "
                                >
                                    {{ outcome.label }}
                                </span>
                                <br />
                                <span
                                    class="text-xs font-black transition-colors"
                                    :class="
                                    isSelected(
                                    fixture.id,
                                    market.rawName,
                                    outcome.label
                                )
                                            ? 'text-[#ff8c00]'
                                            : 'text-white'
                                    "
                                >
                                    {{
                                        outcome.value
                                            ? outcome.value.toFixed(2)
                                            : '0.00'
                                    }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';

const activeTab = ref('All');

const selections = ref([]);

const page = usePage();
// Dynamic Inertia layout tracking payload
const fixture = computed(() => page.props.fixture);

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
            // console.warn('⚠️ This selection already exists in your betslip');
            // Optional: Show a toast notification
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

// Add click listener to document to see if clicks are being registered
onMounted(() => {
    updateSelections();
    window.addEventListener('betSelectionUpdated', updateSelections);

    document.addEventListener('click', (e) => {
        const target = e.target;
        if (target.tagName === 'BUTTON') {
            // console.log('Button classes:', target.className);
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('betSelectionUpdated', updateSelections);
});
// Expose to window for debugging
window.handleSelectionMade = handleSelectionMade;

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
    // Points directly to the reactive local computed fixture constant rather than props
    if (!fixture.value?.odds) return [];

    // Market name to ID mapping
    const marketIdMap = {
        three_way: 1,
        home_away: 2,
        second_half_winner: 3,
        goals_over_under: 4,
        goals_over_under_first_half: 5,
        ht_ft_double: 6,
        both_teams_score: 7,
        handicap_result: 8,
        exact_score: 9,
        double_chance: 10,
        first_half_winner: 11,
        team_to_score_first: 12,
        team_to_score_last: 13,
        total_home: 14,
        total_away: 15,
        double_chance_first_half: 16,
        odd_even: 17,
        odd_even_first_half: 18,
        results_both_teams_score: 19,
        result_total_goals: 20,
        goals_over_under_second_half: 21,
        win_to_nil_home: 22,
        win_to_nil_away: 23,
        win_both_halves: 24,
        both_teams_score_first_half: 25,
        exact_goals_number: 26,
        home_exact_goals_number: 27,
        away_exact_goals_number: 28,
        second_half_exact_goals_number: 29,
        home_score_goal: 30,
        away_score_goal: 31,
        exact_goals_number_first_half: 32,
    };

    return Object.entries(fixture.value.odds).map(([marketKey, rawData]) => {
        let outcomes = [];

        if (Array.isArray(rawData)) {
            outcomes = rawData.map((item, idx) => ({
                id: item.id || `${marketKey}_${idx}`,
                value: item.value,
                label: item.label || `${idx}`,
            }));
        } else if (typeof rawData === 'object' && rawData !== null) {
            outcomes = Object.entries(rawData).map(([key, item]) => ({
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
            marketId: marketIdMap[marketKey] || null, // Get the market ID from the map
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
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
