<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ContestFixturePicker from '@/components/ContestFixturePicker.vue';
import ContestPicksWrapper from '@/components/ContestPicksWrapper.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import { computed, ref } from 'vue';

const page = usePage();
const leagues = computed(() => page.props.leagues);
const maxLegs = computed(() => page.props.max_legs ?? 50);
const minLegs = computed(() => page.props.min_legs ?? 5);

const search = ref('');
const selectedLegs = ref([]);
const errors = ref({});
const submitting = ref(false);

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
        // Same odd clicked → remove the leg.
        if (selectedLegs.value[idx].selection === pick.selection) {
            selectedLegs.value.splice(idx, 1);
            return;
        }
        // Different odd in the same market → replace.
        selectedLegs.value[idx].selection = pick.selection;
        selectedLegs.value = [...selectedLegs.value];
        return;
    }

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
        !submitting.value,
);

const submit = () => {
    if (!canSubmit.value) return;
    submitting.value = true;

    router.post(
        '/contests/draft',
        {
            legs: selectedLegs.value.map((l) => ({
                fixture_id: l.fixture_id,
                market_id: l.market_id,
                selection: l.selection,
            })),
        },
        {
            preserveScroll: true,
            onError: (e) => { errors.value = e; },
            onFinish: () => { submitting.value = false; },
        },
    );
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
                        Step 1 of 2
                    </p>
                    <InfoPopover title="How to set this up">
                        <p>
                            Pick 5 to 50 fixtures and choose your own
                            selection for each one. You'll name the contest
                            and set the deadline on the next step.
                        </p>
                        <p>
                            Once you publish, your picks are locked in and
                            friends can request to join.
                        </p>
                    </InfoPopover>
                </div>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    Pick your fixtures
                </h1>
                <p class="mt-3 text-sm text-slate-400">
                    Choose 5 to 50 fixtures and make a pick on each one.
                    You'll configure the contest on the next step.
                </p>
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

            <p v-if="errors.legs" class="mb-6 text-[11px] text-rose-400">
                {{ errors.legs }}
            </p>

            <!-- CANCEL (bottom) -->
            <div class="flex justify-center pt-4 pb-24">
                <Link
                    href="/contests/mine"
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase transition-colors hover:text-slate-300"
                >
                    Cancel
                </Link>
            </div>
        </div>

        <!-- FLOATING PICKS -->
        <ContestPicksWrapper
            :legs="selectedLegs"
            :can-submit="canSubmit"
            :submitting="submitting"
            :min-legs="minLegs"
            :errors="errors"
            @review-picks="submit"
            @remove-leg="removeLeg"
        />
    </div>
</template>