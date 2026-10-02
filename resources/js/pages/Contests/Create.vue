<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ContestFixturePicker from '@/components/ContestFixturePicker.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import { computed, ref } from 'vue';

const page = usePage();
const leagues = computed(() => page.props.leagues);
const maxLegs = computed(() => page.props.max_legs ?? 50);
const minLegs = computed(() => page.props.min_legs ?? 5);

const name = ref('');
const description = ref('');
const deadline = ref('');
const search = ref('');

// Each entry: { fixture_id, market_id, selection|null, home_team, away_team, market_label, kickoff, options: [{value, odd}] }
const selectedLegs = ref([]);
const errors = ref({});

const allFixtures = computed(() => {
    const out = [];
    for (const league of leagues.value) {
        for (const fixture of league.fixtures) {
            out.push({ ...fixture, league_name: league.name });
        }
    }
    return out;
});

const filteredFixtures = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (q === '') return allFixtures.value.slice(0, 30);

    return allFixtures.value
        .filter(
            (f) =>
                f.home_team.toLowerCase().includes(q) ||
                f.away_team.toLowerCase().includes(q),
        )
        .slice(0, 30);
});

const isSelected = (fixtureId, marketId) =>
    selectedLegs.value.some(
        (l) => l.fixture_id === fixtureId && l.market_id === marketId,
    );

const handleToggleLeg = (pick) => {
    const idx = selectedLegs.value.findIndex(
        (l) =>
            l.fixture_id === pick.fixture_id &&
            l.market_id === pick.market_id,
    );

    if (idx >= 0) {
        // Same selection clicked → remove the leg entirely.
        if (selectedLegs.value[idx].selection === pick.selection) {
            selectedLegs.value.splice(idx, 1);
            return;
        }
        // Different selection in the same market → replace.
        selectedLegs.value[idx].selection = pick.selection;
        selectedLegs.value = [...selectedLegs.value];
        return;
    }

    // New leg.
    if (selectedLegs.value.length >= maxLegs.value) return;

    selectedLegs.value.push({
        fixture_id: pick.fixture_id,
        market_id: pick.market_id,
        selection: pick.selection,
        home_team: pick.home_team,
        away_team: pick.away_team,
        market_label: pick.market_label,
        kickoff: pick.kickoff,
        options: pick.options,
    });
};

const setSelection = (leg, value) => {
    leg.selection = value;
    // Trigger reactivity by reassigning the array — Vue 3 tracks this on
    // objects inside a ref'd array, but forcing a new reference avoids
    // edge cases in older setups.
    selectedLegs.value = [...selectedLegs.value];
};

const removeLeg = (index) => {
    selectedLegs.value.splice(index, 1);
};

const allLegsHaveSelection = computed(() =>
    selectedLegs.value.every((l) => l.selection),
);

const canSubmit = computed(
    () =>
        selectedLegs.value.length >= minLegs.value &&
        allLegsHaveSelection.value &&
        name.value.trim() !== '' &&
        deadline.value !== '',
);

const submit = () => {
    router.post(
        '/contests',
        {
            name: name.value,
            description: description.value,
            entry_deadline_at: deadline.value,
            legs: selectedLegs.value.map((l) => ({
                fixture_id: l.fixture_id,
                market_id: l.market_id,
                selection: l.selection,
            })),
        },
        {
            preserveScroll: true,
            onError: (e) => {
                errors.value = e;
            },
        },
    );
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
</script>

<template>
    <Head title="Create Contest — Betslip Pirates" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-4xl px-2 py-12 md:px-6 lg:px-8">
            <Link
                href="/contests/mine"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to my contests
            </Link>

            <!-- HEADER -->
            <div class="mt-6 mb-8 border-b border-[#232d42] pb-6">
                <div class="flex items-center gap-2">
                    <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                        New Contest
                    </p>
                    <InfoPopover title="How to set this up">
                        <p>
                            Pick 5 to 50 fixtures and choose your own
                            selection for each one. When you publish, your
                            picks are locked in and friends can request to
                            join.
                        </p>
                        <p>
                            Once everyone's picks are in and the deadline
                            passes, no changes. When all fixtures settle, we
                            score and rank everyone.
                        </p>
                        <p class="text-slate-400">
                            You can edit your picks later from the picks
                            page until the deadline.
                        </p>
                    </InfoPopover>
                </div>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    Create a private challenge
                </h1>
                <p class="mt-3 text-sm text-slate-400">
                    Pick the fixtures, make your selections, and share the
                    invite link. You'll play against your friends.
                </p>
            </div>

            <!-- DETAILS -->
            <div class="mb-6 space-y-4 rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                <div class="grid gap-2">
                    <label class="text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        Contest name
                    </label>
                    <input
                        v-model="name"
                        type="text"
                        maxlength="80"
                        placeholder="Sunday Crew"
                        class="w-full rounded border border-[#232d42] bg-[#0a101f] px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                    />
                    <p v-if="errors.name" class="text-[11px] text-rose-400">
                        {{ errors.name }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <label class="text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        Description (optional)
                    </label>
                    <textarea
                        v-model="description"
                        rows="2"
                        maxlength="500"
                        placeholder="Weekly five-pick challenge among friends."
                        class="w-full resize-none rounded border border-[#232d42] bg-[#0a101f] px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                    ></textarea>
                </div>

                <div class="grid gap-2">
                    <label class="text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        Entry deadline
                    </label>
                    <input
                        v-model="deadline"
                        type="datetime-local"
                        class="w-full rounded border border-[#232d42] bg-[#0a101f] px-3 py-2 font-mono text-sm text-white focus:border-sky-500 focus:outline-none"
                    />
                    <p class="text-[10px] text-slate-500">
                        Picks lock at this time. Must be before the earliest
                        kickoff below.
                    </p>
                    <p v-if="errors.entry_deadline_at" class="text-[11px] text-rose-400">
                        {{ errors.entry_deadline_at }}
                    </p>
                </div>
            </div>

            <!-- FIXTURE PICKER -->
            <div class="mb-6">
                <ContestFixturePicker
                    :leagues="leagues"
                    :selected-legs="selectedLegs"
                    :max-legs="maxLegs"
                    @toggle-leg="handleToggleLeg"
                />
            </div>

            <!-- YOUR PICKS -->
            <div
                v-if="selectedLegs.length > 0"
                class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <div class="border-b border-[#232d42] bg-[#111a30] px-5 py-3">
                    <span class="text-[10px] font-black tracking-widest text-emerald-400 uppercase">
                        Your {{ selectedLegs.length }} picks
                    </span>
                </div>
                <ul class="divide-y divide-[#232d42]/60">
                    <li
                        v-for="(leg, i) in selectedLegs"
                        :key="`${leg.fixture_id}-${leg.market_id}`"
                        class="p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                                    Leg {{ i + 1 }} · {{ leg.market_label }} ·
                                    {{ formatKickoff(leg.kickoff) }}
                                </p>
                                <p class="mt-0.5 truncate text-sm font-bold text-slate-200">
                                    {{ leg.home_team }}
                                    <span class="text-slate-500">vs</span>
                                    {{ leg.away_team }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="removeLeg(i)"
                                class="cursor-pointer text-[10px] font-black tracking-widest text-rose-400 uppercase hover:text-rose-300"
                            >
                                Remove
                            </button>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="option in leg.options"
                                :key="option.value"
                                type="button"
                                @click="setSelection(leg, option.value)"
                                :class="[
                                    'flex items-center gap-2 rounded border px-3 py-1.5 text-[11px] font-bold tracking-wide uppercase transition-all',
                                    leg.selection === option.value
                                        ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-400'
                                        : 'border-[#232d42] bg-[#111622] text-slate-300 hover:border-sky-500/40 hover:text-sky-400',
                                ]"
                            >
                                <span>{{ option.value }}</span>
                                <span class="font-mono text-[10px] text-slate-500">
                                    {{ option.odd.toFixed(2) }}
                                </span>
                            </button>
                        </div>
                    </li>
                </ul>

                <!-- Progress hint -->
                <div
                    v-if="!allLegsHaveSelection"
                    class="border-t border-[#232d42] bg-[#0a101f] px-4 py-2 text-[10px] text-amber-400"
                >
                    Pick a selection for every leg to continue.
                </div>
            </div>

            <p v-if="errors.legs" class="mb-6 text-[11px] text-rose-400">
                {{ errors.legs }}
            </p>

            <!-- SUBMIT -->
            <div class="flex items-center justify-end gap-3">
                <Link
                    href="/contests/mine"
                    class="rounded-lg border border-[#232d42] px-5 py-3 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-slate-600 hover:text-slate-200"
                >
                    Cancel
                </Link>
                <button
                    type="button"
                    @click="submit"
                    :disabled="!canSubmit"
                    class="cursor-pointer rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Publish contest
                </button>
            </div>

            <p
                v-if="selectedLegs.length > 0 && selectedLegs.length < minLegs"
                class="mt-3 text-right text-[10px] text-amber-400"
            >
                Add {{ minLegs - selectedLegs.length }} more
                {{ minLegs - selectedLegs.length === 1 ? 'leg' : 'legs' }}.
            </p>
        </div>
    </div>
</template>