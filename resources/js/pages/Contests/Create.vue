<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const leagues = computed(() => page.props.leagues);
const minLegs = computed(() => page.props.min_legs ?? 5);
const maxLegs = computed(() => page.props.max_legs ?? 50);

const name = ref('');
const description = ref('');
const deadline = ref('');
const search = ref('');

const selectedLegs = ref([]);
const errors = ref({});

// Flatten for searching.
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

const toggleLeg = (fixture, market) => {
    const idx = selectedLegs.value.findIndex(
        (l) => l.fixture_id === fixture.id && l.market_id === market.id,
    );

    if (idx >= 0) {
        selectedLegs.value.splice(idx, 1);
        return;
    }

    if (selectedLegs.value.length >= maxLegs.value) return;

    selectedLegs.value.push({
        fixture_id: fixture.id,
        market_id: market.id,
        home_team: fixture.home_team,
        away_team: fixture.away_team,
        market_label: market.label,
        kickoff: fixture.kickoff,
    });
};

const removeLeg = (index) => {
    selectedLegs.value.splice(index, 1);
};

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

            <div class="mt-6 mb-8 border-b border-[#232d42] pb-6">
                <p
                    class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                >
                    New Contest
                </p>
                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    Create a private challenge
                </h1>
                <p class="mt-3 text-sm text-slate-400">
                    Pick the fixtures, share the invite link, and score your
                    friends' picks. Free to run.
                </p>
            </div>

            <!-- Details -->
            <div
                class="mb-6 space-y-4 rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
            >
                <div class="grid gap-2">
                    <label
                        class="text-[10px] font-black tracking-widest text-slate-400 uppercase"
                    >
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
                    <label
                        class="text-[10px] font-black tracking-widest text-slate-400 uppercase"
                    >
                        Description (optional)
                    </label>
                    <textarea
                        v-model="description"
                        rows="2"
                        maxlength="500"
                        placeholder="Weekly five-pick challenge among friends."
                        class="w-full resize-none rounded border border-[#232d42] bg-[#0a101f] px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                    ></textarea>
                    <p v-if="errors.description" class="text-[11px] text-rose-400">
                        {{ errors.description }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <label
                        class="text-[10px] font-black tracking-widest text-slate-400 uppercase"
                    >
                        Entry deadline
                    </label>
                    <input
                        v-model="deadline"
                        type="datetime-local"
                        class="w-full rounded border border-[#232d42] bg-[#0a101f] px-3 py-2 font-mono text-sm text-white focus:border-sky-500 focus:outline-none"
                    />
                    <p class="text-[10px] text-slate-500">
                        Picks are locked at this time. Must be before the
                        earliest kickoff below.
                    </p>
                    <p v-if="errors.entry_deadline_at" class="text-[11px] text-rose-400">
                        {{ errors.entry_deadline_at }}
                    </p>
                </div>
            </div>

            <!-- Fixture picker -->
            <div
                class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <div
                    class="flex items-center justify-between border-b border-[#232d42] bg-[#111a30] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Pick your legs
                    </span>
                    <span
                        class="font-mono text-[10px] tracking-widest uppercase"
                        :class="
                            selectedLegs.length < minLegs
                                ? 'text-amber-400'
                                : selectedLegs.length >= maxLegs
                                ? 'text-rose-400'
                                : 'text-slate-500'
                        "
                    >
                        {{ selectedLegs.length }} / {{ maxLegs }}
                    </span>
                </div>

                <div class="p-5">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by team name…"
                        class="mb-4 w-full rounded border border-[#232d42] bg-[#0a101f] px-3 py-2 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                    />

                    <div
                        v-if="filteredFixtures.length === 0"
                        class="py-6 text-center text-xs text-slate-500"
                    >
                        No fixtures match your search.
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="fixture in filteredFixtures"
                            :key="fixture.id"
                            class="rounded border border-[#232d42]/60 bg-[#0a101f] p-3"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                                    >
                                        {{ fixture.league_name }} ·
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
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-for="market in fixture.markets"
                                    :key="market.id"
                                    type="button"
                                    :disabled="
                                        !isSelected(fixture.id, market.id) &&
                                        selectedLegs.length >= maxLegs
                                    "
                                    @click="toggleLeg(fixture, market)"
                                    :class="[
                                        'rounded border px-2.5 py-1 text-[10px] font-bold tracking-wide uppercase transition-all',
                                        isSelected(fixture.id, market.id)
                                            ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-400'
                                            : 'border-[#232d42] bg-[#111622] text-slate-300 hover:border-sky-500/40 hover:text-sky-400',
                                        !isSelected(fixture.id, market.id) &&
                                        selectedLegs.length >= maxLegs
                                            ? 'cursor-not-allowed opacity-40'
                                            : '',
                                    ]"
                                >
                                    {{ market.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected legs -->
            <div
                v-if="selectedLegs.length > 0"
                class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <div
                    class="border-b border-[#232d42] bg-[#111a30] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                    >
                        Your {{ selectedLegs.length }} legs
                    </span>
                </div>
                <ul class="divide-y divide-[#232d42]/60">
                    <li
                        v-for="(leg, i) in selectedLegs"
                        :key="`${leg.fixture_id}-${leg.market_id}`"
                        class="flex items-center justify-between gap-3 px-5 py-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                            >
                                Leg {{ i + 1 }} · {{ leg.market_label }} ·
                                {{ formatKickoff(leg.kickoff) }}
                            </p>
                            <p
                                class="mt-0.5 truncate text-sm font-bold text-slate-200"
                            >
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
                    </li>
                </ul>
            </div>

            <p v-if="errors.legs" class="mb-6 text-[11px] text-rose-400">
                {{ errors.legs }}
            </p>

            <!-- Submit -->
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
                    :disabled="
                        selectedLegs.length < minLegs ||
                        !name.trim() ||
                        !deadline
                    "
                    class="cursor-pointer rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Create contest
                </button>
            </div>
        </div>

        <Footer />
    </div>
</template>