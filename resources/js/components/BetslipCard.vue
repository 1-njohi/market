<template>
    <div
        class="scrollbar-color-sky-500_slate-200 flex max-h-[75vh] w-full flex-col overflow-hidden scrollbar-thin font-sans text-slate-300"
    >
        <!-- ═══ HEADER ═══ -->
        <header class="flex items-center justify-between border-b border-[#232d42] px-5 py-3">
            <span class="text-[10px] font-black tracking-widest text-emerald-400 uppercase">
                Betslip
            </span>
            <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                {{ selections.length }}
                {{ selections.length === 1 ? 'selection' : 'selections' }}
            </span>
        </header>

        <!-- ═══ STATUS STRIP ═══ -->
        <div class="flex items-center justify-between border-b border-[#232d42] bg-[#0f1422] px-5 py-2">
            <span class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                {{ selections.length > 1 ? `Multi bet (${selections.length})` : 'Single bet' }}
            </span>
            <button
                type="button"
                class="cursor-pointer rounded border border-[#232d42] bg-[#161c2a] px-2.5 py-1 text-[10px] font-bold tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/50 hover:text-sky-400"
            >
                Share
            </button>
        </div>

        <!-- ═══ BODY ═══ -->
        <div class="flex-1 overflow-y-auto">
            <!-- Empty state -->
            <div
                v-if="selections.length === 0"
                class="px-5 py-8 text-center text-[11px] text-slate-500"
            >
                Your betslip is empty. Tap an odd on any fixture to add a selection.
            </div>

            <!-- Selections list -->
            <ul v-else class="divide-y divide-[#232d42]/60">
                <li
                    v-for="(item, i) in selections"
                    :key="item.id"
                    class="flex items-start justify-between gap-3 px-5 py-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                            Leg {{ i + 1 }} · {{ formatMarketName(item.market_name) }}
                        </p>
                        <p class="mt-0.5 truncate text-sm font-bold text-slate-200">
                            {{ item.home_team }}
                            <span class="text-slate-500">vs</span>
                            {{ item.away_team }}
                        </p>
                        <p class="mt-0.5 truncate text-[11px] font-semibold text-sky-400">
                            {{
                                item.market_name === 'double_chance'
                                    ? formatDoubleChanceName(item.selection)
                                    : item.selection
                            }}
                        </p>
                    </div>

                    <div class="flex flex-shrink-0 flex-col items-end gap-1">
                        <span class="font-mono text-sm font-black text-emerald-400">
                            {{ item.odds?.value?.toFixed(2) }}
                        </span>
                        <button
                            type="button"
                            @click="removeSelection(item)"
                            class="cursor-pointer text-[10px] font-black tracking-widest text-rose-400 uppercase hover:text-rose-300"
                            title="Remove selection"
                        >
                            Remove
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <!-- ═══ FOOTER — total odds ═══ -->
        <footer class="flex items-center justify-between border-t border-[#232d42] bg-[#0f1422] px-5 py-3">
            <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                Total odds
            </span>
            <span class="font-mono text-lg font-black text-emerald-400">
                {{ totalOdds.toFixed(2) }}×
            </span>
        </footer>

        <!-- ═══ ACTIONS ═══ -->
        <div class="grid grid-cols-12 gap-2 border-t border-[#232d42] bg-[#0d1527] px-5 py-4">
            <button
                type="button"
                @click="clearAll"
                :disabled="selections.length === 0"
                class="col-span-5 cursor-pointer rounded-lg border border-[#232d42] px-3 py-3 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/50 hover:text-slate-200 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-[#232d42] disabled:hover:text-slate-400"
            >
                Remove all
            </button>

            <button
                type="button"
                @click="createDraft"
                :disabled="selections.length === 0 || submitting"
                class="col-span-7 cursor-pointer rounded-lg bg-[#ff8c00] px-3 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100"
            >
                {{ submitting ? 'Loading…' : 'Create bet' }}
            </button>
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