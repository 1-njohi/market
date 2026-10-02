<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const contest = computed(() => page.props.contest);
const standings = computed(() => page.props.standings);

const legLabel = (legId) => {
    const leg = contest.value.legs.find((l) => l.id === legId);
    if (!leg) return 'Leg';
    return `${leg.fixture.home_team} vs ${leg.fixture.away_team}`;
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
    <Head :title="`Results — ${contest.name}`" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-4xl px-4 py-12 md:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 border-b border-[#232d42] pb-6">
                <p
                    class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                >
                    Contest Results
                </p>
                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    {{ contest.name }}
                </h1>
                <p class="mt-3 text-sm text-slate-400">
                    Hosted by {{ contest.host_name }} · Settled
                    {{ formatKickoff(contest.settled_at) }}
                </p>
            </div>

            <!-- Legs summary -->
            <div
                class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#111622]/40"
            >
                <div
                    class="border-b border-[#232d42] bg-[#111a30] px-4 py-2.5"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Legs
                    </span>
                </div>
                <ul class="divide-y divide-[#232d42]/60">
                    <li
                        v-for="(leg, i) in contest.legs"
                        :key="leg.id"
                        class="flex items-center justify-between gap-3 px-4 py-2.5"
                    >
                        <span class="text-[11px] text-slate-400">
                            <span class="text-slate-600">#{{ i + 1 }}</span>
                            {{ leg.fixture.home_team }} vs
                            {{ leg.fixture.away_team }}
                        </span>
                        <span
                            class="font-mono text-[11px] font-bold"
                            :class="
                                leg.status === 'void'
                                    ? 'text-slate-500'
                                    : 'text-emerald-400'
                            "
                        >
                            {{
                                leg.status === 'void'
                                    ? 'VOID'
                                    : leg.result_selection
                            }}
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Standings -->
            <div class="space-y-3">
                <div
                    v-for="row in standings"
                    :key="row.user_id"
                    class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
                >
                    <div class="flex items-center gap-4 p-4">
                        <span
                            class="w-8 flex-shrink-0 font-mono text-lg font-black text-slate-500"
                        >
                            #{{ row.rank }}
                        </span>

                        <img
                            :src="row.avatar"
                            :alt="row.name"
                            class="h-10 w-10 flex-shrink-0 rounded-full border border-[#232d42] object-cover"
                        />

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="truncate font-mono text-sm font-black text-white"
                                >
                                    {{ row.name }}
                                </span>
                                <span
                                    v-if="row.is_verified"
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full bg-sky-500 text-[7px] font-black text-[#070b14]"
                                    >✓</span
                                >
                            </div>
                            <p class="mt-0.5 font-mono text-[10px] text-slate-500">
                                {{ row.code }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-right">
                            <div>
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    Correct
                                </span>
                                <span
                                    class="mt-0.5 block font-mono text-lg font-black text-white"
                                >
                                    {{ row.correct }}
                                </span>
                            </div>
                            <div>
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    Units
                                </span>
                                <span
                                    class="mt-0.5 block font-mono text-lg font-black text-amber-400"
                                >
                                    {{ row.units.toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Picks -->
                    <div
                        class="border-t border-[#232d42]/60 bg-[#0f1422] px-4 py-2"
                    >
                        <div
                            v-for="pick in row.picks"
                            :key="pick.leg_id"
                            class="flex items-center justify-between border-b border-[#232d42]/30 py-1.5 last:border-b-0"
                        >
                            <span
                                class="truncate text-[10px] text-slate-500"
                            >
                                {{ legLabel(pick.leg_id) }}
                            </span>
                            <span class="flex items-center gap-2">
                                <span
                                    class="font-mono text-[10px]"
                                    :class="
                                        pick.result === 'correct'
                                            ? 'text-emerald-400'
                                            : pick.result === 'void'
                                              ? 'text-slate-500'
                                              : 'text-rose-400'
                                    "
                                >
                                    {{ pick.selection }}
                                </span>
                                <span
                                    class="font-mono text-[10px] text-slate-600"
                                >
                                    @{{ pick.odds.toFixed(2) }}
                                </span>
                                <span
                                    :class="[
                                        'flex h-4 w-4 items-center justify-center rounded-sm font-mono text-[9px] font-black',
                                        pick.result === 'correct'
                                            ? 'bg-emerald-500 text-[#070b14]'
                                            : pick.result === 'void'
                                              ? 'bg-slate-500 text-white'
                                              : 'bg-rose-500 text-white',
                                    ]"
                                >
                                    {{
                                        pick.result === 'correct'
                                            ? '✓'
                                            : pick.result === 'void'
                                              ? 'V'
                                              : '✗'
                                    }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center">
                <Link
                    :href="`/contests/join/${contest.uuid}`"
                    class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
                >
                    ← Back to contest
                </Link>
            </div>
        </div>
    </div>
</template>