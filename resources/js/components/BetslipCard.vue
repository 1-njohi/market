<template>
    <div
        class="scrollbar-color-sky-500_slate-200 mb-2 max-h-[90vh] w-full max-w-xl scrollbar-thin overflow-x-hidden overflow-y-auto rounded-xl border border-gray-800 bg-[#0d1527] font-sans shadow-2xl md:max-h-[80vh]"
    >
        <!-- Betslip Header Banner -->
        <div
            class="flex items-center justify-between border-b border-[#081f2c] bg-[#0b2d3f] px-4 py-3 text-sm font-bold tracking-wide text-white"
        >
            <div class="flex items-center space-x-2">
                <span>BETSLIP</span>
            </div>
        </div>

        <!-- Active Content Area -->
        <div class="space-y-3 bg-white p-3">
            <!-- Purple Multi-bet Boost Notification Card -->
            <div
                class="flex items-center space-x-3 rounded-lg bg-[#7c3aed] p-3 text-white shadow-sm"
            >
                <div class="text-s flex-2">
                    <p class="leading-relaxed font-normal text-purple-100">
                        Your bet of
                        <span class="font-bold text-white"
                            >{{ selections.length }} selection(s)</span
                        >
                    </p>
                </div>
            </div>

            <!-- Multi-bet Tab Title Header Line -->
            <div
                class="flex items-center justify-between rounded border border-[#e2e8f0] bg-[#f1f5f9] px-3 py-2"
            >
                <span
                    class="text-xs font-bold tracking-wider text-[#0f172a] uppercase"
                    v-if="selections.length > 1"
                >
                    MULTI BET ({{ selections.length }})
                </span>
                <span
                    class="text-xs font-bold tracking-wider text-[#0f172a] uppercase"
                    v-else
                >
                    SINGLE BET
                </span>
                <button
                    type="button"
                    class="flex items-center space-x-1.5 rounded border border-[#cbd5e1] bg-white px-2.5 py-1 text-xs font-semibold text-[#334155] shadow-sm transition-colors hover:bg-slate-50"
                >
                    <span>↪</span>
                    <span>Share</span>
                </button>
            </div>

            <!-- Bet Slip Game List Container -->
            <div class="divide-y divide-dashed divide-slate-300">
                <div
                    v-for="item in selections"
                    :key="item.id"
                    class="flex flex-col justify-between py-3 first:pt-1 last:pb-1"
                >
                    <!-- Row 1: Game Title and Delete Marker -->
                    <div class="flex items-start justify-between">
                        <div
                            class="flex items-center space-x-1.5 text-xs font-bold text-slate-900"
                        >
                            <span class="cursor-pointer hover:underline"
                                >{{ item.home_team }} –
                                {{ item.away_team }}</span
                            >
                        </div>
                        <button
                            type="button"
                            @click="removeSelection(item)"
                            class="h-5 w-5 cursor-pointer rounded-xl bg-red-500 px-1 text-sm font-bold text-white transition-colors hover:text-green-500"
                            title="Remove selection"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Row 2: Selected Target Market Subtitle -->
                    <div class="mt-0.5 pl-5 text-[11px] text-slate-500">
                        {{ formatMarketName(item.market_name) }}
                    </div>

                    <!-- Row 3: Actual Pick Selection & Associated Live Odds Value -->
                    <div class="mt-1 flex items-center justify-between pl-5">
                        <div class="text-xs text-slate-700">
                            Your Pick:
                            <span class="font-bold text-slate-900">{{
                                item.market_name == 'double_chance'
                                    ? formatDoubleChanceName(item.selection)
                                    : item.selection
                            }}</span>
                        </div>
                        <div class="text-xs font-bold text-slate-900">
                            {{ item.odds?.value?.toFixed(2) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Odds Calculation Output -->
            <div
                class="flex items-center justify-between border-t border-slate-300 pt-3"
            >
                <span class="text-xs font-bold tracking-wide text-slate-800"
                    >TOTAL ODDS:</span
                >
                <span class="text-s font-black text-slate-900">{{
                    totalOdds.toFixed(2)
                }}</span>
            </div>
        </div>

        <!-- Sticky Bottom Action Buttons -->
        <div class="mt-auto flex flex-col text-xs font-bold tracking-wide">
            <div class="grid grid-cols-12 gap-2 bg-[#0c2430] p-3">
                <button
                    type="button"
                    @click="clearAll"
                    :class="[
                        'col-span-5 rounded border border-[#234d63]/40 bg-[#1b3d4f] px-2 py-3 text-center text-[11px] font-bold tracking-wider uppercase',
                        selections.length > 0
                            ? 'cursor-pointer text-[#94a3b8] transition-colors hover:bg-[#234d63] hover:text-white'
                            : '',
                    ]"
                >
                    Remove All
                </button>

                <button
                    type="button"
                    @click="createDraft"
                    :disabled="selections.length === 0 || submitting"
                    :class="[
                        'col-span-7 rounded border-b-2 border-slate-400 px-2 py-3 text-center text-xs font-black tracking-wider uppercase shadow-md transition-all hover:from-white hover:to-[#cbd5e1]',
                        selections.length > 0 && !submitting
                            ? 'cursor-pointer bg-gradient-to-b from-[#f8fafc] to-[#e2e8f0] text-[#0f172a]'
                            : 'cursor-not-allowed text-gray-500',
                    ]"
                >
                    {{ submitting ? 'Loading…' : 'Create Bet' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();

// Initialize selections as a ref with an empty array
const selections = ref([]);

// Submit state — disables the button while the draft POST is in flight
const submitting = ref(false);

// Helper function to get all selections from localStorage
const getSelectionsFromLocalStorage = () => {
    try {
        const stored_selections = localStorage.getItem('bet_selections');
        if (stored_selections) {
            const parsed = JSON.parse(stored_selections);
            return Array.isArray(parsed) ? parsed : [];
        }
        return [];
    } catch (error) {
        console.error('❌ Error reading selections from localStorage:', error);
        return [];
    }
};

// Update selections from localStorage
const updateSelections = () => {
    selections.value = getSelectionsFromLocalStorage();
};

// Optional: Watch for localStorage changes from other tabs/windows
const handleStorageChange = (event) => {
    if (event.key === 'bet_selections') {
        updateSelections();
    }
};

// Lifecycle hooks
onMounted(() => {
    updateSelections();
    window.addEventListener('betSelectionUpdated', updateSelections);
    window.addEventListener('storage', handleStorageChange);
});

onBeforeUnmount(() => {
    window.removeEventListener('betSelectionUpdated', updateSelections);
    window.removeEventListener('storage', handleStorageChange);
});

// Expose to window for debugging
window.updateSelections = updateSelections;
window.getSelections = getSelectionsFromLocalStorage;

// 1. Multiply all active row selections to generate total combo multiplier odds
const totalOdds = computed(() => {
    if (selections.value.length === 0) return 0;
    return selections.value.reduce((acc, curr) => acc * curr.odds.value, 1);
});

const removeSelection = (selection) => {
    const new_selections = selections.value.filter(
        (item) =>
            !(
                item.fixture_id === selection.fixture_id &&
                item.market_name === selection.market_name
            ),
    );

    localStorage.setItem('bet_selections', JSON.stringify(new_selections));

    window.dispatchEvent(
        new CustomEvent('betSelectionUpdated', {
            detail: { selections: new_selections },
        }),
    );
};

const clearAll = (from_system) => {
    if (!from_system) {
        if (!confirm('Are you sure you want to do that? This is irrevasable'))
            return;
    }
    let new_selections = [];

    localStorage.setItem('bet_selections', JSON.stringify(new_selections));

    window.dispatchEvent(
        new CustomEvent('betSelectionUpdated', {
            detail: { selections, new_selections },
        }),
    );
};

/**
 * Hand off the current selections to the server, which stashes them
 * in the session and redirects to the pricing/confirm page.
 */
const createDraft = () => {
    if (submitting.value) return;

    if (selections.value.length === 0) {
        alert('Your betslip is currently empty!');
        return;
    }

    if (!page.props.auth.user) {
        alert(
            'You are NOT logged in. Login to create your betslip! Your slip will be here waiting for you as it is!',
        );
        window.location.href = '/login';
        return;
    }

    submitting.value = true;

    router.post(
        '/betslip/draft',
        {
            selections: selections.value.map((selection) => ({
                odd_id: selection.odds?.id,
            })),
        },
        {
            onFinish: () => {
                submitting.value = false;
            },
            onError: (errors) => {
                console.error(errors);
                alert('Could not save your betslip. Please try again.');
            },
        },
    );
};

const formatMarketName = (str) => {
    const marketNames = {
        three_way: '3 Way',
        double_chance: 'Double Chance',
        over_under: 'Over/Under',
        under_over: 'Under/Over',
        both_team_to_score: 'Both Teams to Score',
        both_teams_to_score: 'Both Teams to Score',
    };

    if (marketNames[str]) {
        return marketNames[str];
    }

    return str
        .split('_')
        .map((word) => {
            if (!isNaN(word) && word.trim() !== '') {
                return word;
            }
            return word.charAt(0).toUpperCase() + word.slice(1);
        })
        .join(' ');
};

const formatDoubleChanceName = (str) => {
    const doubleChanceNames = {
        one_x: '1 or X',
        x_two: 'X or 2',
        one_two: '1 or 2',
    };

    if (doubleChanceNames[str]) {
        return doubleChanceNames[str];
    }

    return str
        .split('_')
        .map((word) => {
            if (!isNaN(word) && word.trim() !== '') {
                return word;
            }
            return word.charAt(0).toUpperCase() + word.slice(1);
        })
        .join(' ');
};
</script>
