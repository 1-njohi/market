<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    seasons: { type: Array, required: true },
    leagues: { type: Array, required: true },
    fixtures: { type: Object, required: true },
    suggested: { type: Object, required: true },
    min_legs: { type: Number, default: 5 },
    window_days: { type: Number, default: 7 },
});

const form = useForm({
    season: props.seasons[0] ?? '',
    league_code: props.leagues[0]?.code ?? '',
    matchday_number: null as number | null,
    name: '',
    description: '',
    fixture_ids: [] as number[],
});

const selected = ref<number[]>([]);

const suggestedNumber = computed(
    () => props.suggested[`${form.season}:${form.league_code}`] ?? 1,
);

const fixturesForLeague = computed(
    () => props.fixtures[form.league_code] ?? [],
);

// Reset fixture selection when league changes — the list changes with it.
watch(() => form.league_code, () => {
    selected.value = [];
});

// Auto-suggest the name when matchday_number is set and name is empty.
watch(() => form.matchday_number, (val) => {
    if (!form.name && val) form.name = `Matchday ${val}`;
});

const toggleFixture = (id: number) => {
    const i = selected.value.indexOf(id);
    if (i === -1) selected.value.push(id);
    else selected.value.splice(i, 1);
};

const selectAll = () => {
    selected.value = fixturesForLeague.value.map((f: any) => f.id);
};

const clearAll = () => {
    selected.value = [];
};

const submit = () => {
    form.fixture_ids = selected.value;
    form.post('/admin/matchdays');
};

const canSubmit = computed(
    () =>
        !form.processing &&
        !!form.matchday_number &&
        form.name.trim().length > 0 &&
        selected.value.length >= props.min_legs,
);

const formatKickoff = (iso: string | null) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('en-KE', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};
</script>

<template>
    <Head title="New matchday — Admin" />

    <div>
        <!-- Header -->
        <div class="mb-6 border-b border-[#232d42] pb-6">
            <Link
                href="/admin/matchdays"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:text-sky-300"
            >
                ← Back to matchdays
            </Link>
            <h1 class="mt-3 text-2xl font-black tracking-wider text-white uppercase">
                New matchday
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                Publish a season fixture for the matchday league. All sub-league members are auto-entered.
            </p>
        </div>

        <!-- Config card -->
        <section class="mb-5 rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Season
                    </label>
                    <select
                        v-model="form.season"
                        class="mt-2 w-full rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-xs text-white focus:border-sky-500 focus:outline-none"
                    >
                        <option v-for="s in seasons" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <p v-if="form.errors.season" class="mt-1 text-[10px] text-rose-400">
                        {{ form.errors.season }}
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        League
                    </label>
                    <select
                        v-model="form.league_code"
                        class="mt-2 w-full rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-xs text-white focus:border-sky-500 focus:outline-none"
                    >
                        <option v-for="l in leagues" :key="l.code" :value="l.code">
                            {{ l.code }} — {{ l.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.league_code" class="mt-1 text-[10px] text-rose-400">
                        {{ form.errors.league_code }}
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Matchday #
                    </label>
                    <input
                        v-model.number="form.matchday_number"
                        type="number"
                        min="1"
                        max="99"
                        :placeholder="`Next unused: ${suggestedNumber}`"
                        class="mt-2 w-full rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-xs text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                    />
                    <p v-if="form.errors.matchday_number" class="mt-1 text-[10px] text-rose-400">
                        {{ form.errors.matchday_number }}
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    Name
                </label>
                <input
                    v-model="form.name"
                    type="text"
                    maxlength="80"
                    class="mt-2 w-full rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-xs text-white focus:border-sky-500 focus:outline-none"
                />
                <p v-if="form.errors.name" class="mt-1 text-[10px] text-rose-400">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="mt-4">
                <label class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    Description <span class="text-slate-600">— optional</span>
                </label>
                <textarea
                    v-model="form.description"
                    rows="2"
                    maxlength="500"
                    class="mt-2 w-full resize-none rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-xs text-white focus:border-sky-500 focus:outline-none"
                ></textarea>
                <p v-if="form.errors.description" class="mt-1 text-[10px] text-rose-400">
                    {{ form.errors.description }}
                </p>
            </div>
        </section>

        <!-- Fixtures card -->
        <section class="mb-5 rounded-lg border border-[#232d42] bg-[#161c2a]">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-[#232d42] bg-[#0f1422] px-5 py-3">
                <div>
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Fixtures — next {{ window_days }} days
                    </span>
                    <p class="mt-0.5 text-[10px] text-slate-600">
                        Min {{ min_legs }} selections. All fixtures must belong to the selected league.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="font-mono text-[10px] font-black"
                        :class="
                            selected.length >= min_legs
                                ? 'text-emerald-400'
                                : 'text-amber-400'
                        "
                    >
                        {{ selected.length }} selected
                    </span>
                    <button
                        type="button"
                        @click="selectAll"
                        class="rounded border border-[#232d42] px-2 py-1 text-[9px] font-black tracking-widest text-slate-400 uppercase hover:border-sky-500/40 hover:text-sky-400"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        @click="clearAll"
                        class="rounded border border-[#232d42] px-2 py-1 text-[9px] font-black tracking-widest text-slate-400 uppercase hover:border-rose-500/40 hover:text-rose-400"
                    >
                        None
                    </button>
                </div>
            </header>

            <div v-if="fixturesForLeague.length === 0" class="px-5 py-12 text-center text-xs text-slate-500">
                No upcoming fixtures for this league in the next {{ window_days }} days.
            </div>

            <ul v-else class="divide-y divide-[#232d42]/60">
                <li
                    v-for="fx in fixturesForLeague"
                    :key="fx.id"
                    class="flex cursor-pointer items-center gap-3 px-5 py-3 transition-colors hover:bg-[#111a30]"
                    @click="toggleFixture(fx.id)"
                >
                    <input
                        type="checkbox"
                        :checked="selected.includes(fx.id)"
                        @click.stop
                        @change="toggleFixture(fx.id)"
                        class="h-4 w-4 cursor-pointer accent-amber-500"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-slate-200">
                            {{ fx.home_team }}
                            <span class="text-slate-500">vs</span>
                            {{ fx.away_team }}
                        </p>
                    </div>
                    <span class="font-mono text-[10px] text-slate-500">
                        {{ formatKickoff(fx.kickoff) }}
                    </span>
                </li>
            </ul>

            <p v-if="form.errors.fixture_ids" class="border-t border-rose-500/20 bg-rose-500/5 px-5 py-2 text-[10px] text-rose-400">
                {{ form.errors.fixture_ids }}
            </p>
            <p v-if="form.errors.legs" class="border-t border-rose-500/20 bg-rose-500/5 px-5 py-2 text-[10px] text-rose-400">
                {{ form.errors.legs }}
            </p>
        </section>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-2">
            <Link
                href="/admin/matchdays"
                class="rounded border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase hover:border-sky-500/40 hover:text-slate-200"
            >
                Cancel
            </Link>
            <button
                type="button"
                @click="submit"
                :disabled="!canSubmit"
                class="rounded border border-amber-500/30 bg-amber-500/10 px-6 py-2 text-[10px] font-black tracking-widest text-amber-400 uppercase transition-colors hover:border-amber-500 hover:bg-amber-500/20 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-amber-500/30 disabled:hover:bg-amber-500/10"
            >
                {{ form.processing ? 'Publishing…' : 'Publish matchday' }}
            </button>
        </div>
    </div>
</template>