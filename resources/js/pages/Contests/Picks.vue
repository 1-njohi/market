<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InfoPopover from '@/components/InfoPopover.vue';

const page = usePage();
const contest = computed(() => page.props.contest);
const initialPicks = computed(() => page.props.picks);

// Local form state, seeded from server-side picks.
const picks = ref({});
const isProcessing = ref(false);

for (const p of initialPicks.value) {
    picks.value[p.leg_id] = p.selection;
}

const isLocked = computed(() => contest.value.is_locked);

const pickCount = computed(() => Object.keys(picks.value).length);

const setPick = (legId, value) => {
    if (isLocked.value) return;
    picks.value = { ...picks.value, [legId]: value };
};

const submit = () => {
    if (isLocked.value) return;
    isProcessing.value = true;

    const payload = Object.entries(picks.value).map(([legId, selection]) => ({
        leg_id: Number(legId),
        selection,
    }));

    router.post(
        `/contests/${contest.value.uuid}/picks`,
        { picks: payload },
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
};

const formatKickoff = (iso) => {
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
    <Head :title="`Picks — ${contest.name}`" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-3xl px-2 py-12 md:px-6 lg:px-8">
            <Link
                :href="`/contests/join/${contest.uuid}`"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to contest
            </Link>

            <!-- Header -->
            <div class="mt-6 mb-8 border-b border-[#232d42] pb-6">
                <div class="flex items-center gap-2">
                    <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                        Your picks
                    </p>
                    <InfoPopover title="How picks are scored">
                        <p>
                            Pick one selection for each leg. Correct picks earn the
                            odds value as points, wrong picks earn zero.
                        </p>
                        <div class="rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-[11px]">
                            <p class="text-emerald-400">Correct: +odds as points</p>
                            <p class="text-rose-400">Wrong: 0 points</p>
                            <p class="text-slate-400">Void: 0 points, excluded</p>
                        </div>
                        <p>
                            Ranked by correct count first, then total points.
                        </p>
                        <p class="text-slate-400">
                            You can change your picks any time before the deadline.
                        </p>
                    </InfoPopover>
                </div>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    {{ contest.name }}
                </h1>
                <p class="mt-3 text-sm text-slate-400">
                    {{ pickCount }} of {{ contest.legs.length }} legs picked ·
                    Deadline {{ formatKickoff(contest.entry_deadline_at) }}
                </p>
                <div
                    v-if="isLocked"
                    class="mt-3 rounded border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-[11px] text-amber-400"
                >
                    Picks are locked. You can view your selections below.
                </div>
            </div>

            <!-- Legs -->
            <div class="space-y-3">
                <div
                    v-for="(leg, i) in contest.legs"
                    :key="leg.id"
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                            >
                                Leg {{ i + 1 }} · {{ leg.market }}
                            </p>
                            <p
                                class="mt-1 truncate text-sm font-bold text-slate-200"
                            >
                                {{ leg.fixture.home_team }}
                                <span class="text-slate-500">vs</span>
                                {{ leg.fixture.away_team }}
                            </p>
                            <p class="mt-0.5 text-[10px] text-slate-500">
                                Kickoff {{ formatKickoff(leg.fixture.kickoff) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="option in leg.options"
                            :key="option.value"
                            type="button"
                            :disabled="isLocked"
                            @click="setPick(leg.id, option.value)"
                            :class="[
                                'flex items-center gap-2 rounded border px-3 py-2 text-[11px] font-bold tracking-wide uppercase transition-all',
                                picks[leg.id] === option.value
                                    ? 'border-emerald-500/60 bg-emerald-500/10 text-emerald-400'
                                    : 'border-[#232d42] bg-[#111622] text-slate-300 hover:border-sky-500/40 hover:text-sky-400',
                                isLocked && picks[leg.id] !== option.value
                                    ? 'opacity-40'
                                    : '',
                            ]"
                        >
                            <span>{{ option.value }}</span>
                            <span class="font-mono text-[10px] text-slate-500">
                                {{ option.odd.toFixed(2) }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div v-if="!isLocked" class="mt-6 flex justify-end">
                <button
                    type="button"
                    @click="submit"
                    :disabled="isProcessing || pickCount === 0"
                    class="cursor-pointer rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ isProcessing ? 'Saving…' : 'Save picks' }}
                </button>
            </div>
        </div>
    </div>
</template>