<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const contest = computed(() => page.props.contest);
const entry = computed(() => page.props.entry);
const canJoin = computed(() => page.props.can_join);
const auth = computed(() => page.props.auth?.user ?? null);

const join = () => {
    router.post(`/contests/join/${contest.value.uuid}`);
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
    <Head :title="`${contest.name} — Betslip Pirates`" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-3xl px-2 py-12 md:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 border-b border-[#232d42] pb-6">
                <p
                    class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                >
                    Contest Invite
                </p>
                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    {{ contest.name }}
                </h1>
                <p class="mt-3 text-sm text-slate-400">
                    Hosted by
                    <Link
                        :href="`/profile/${contest.host_code}`"
                        class="font-bold text-sky-400 hover:underline"
                    >
                        {{ contest.host_name }}
                    </Link>
                    · {{ contest.legs.length }} legs ·
                    Deadline {{ formatKickoff(contest.entry_deadline_at) }}
                </p>
                <p
                    v-if="contest.description"
                    class="mt-4 text-sm leading-relaxed text-slate-400"
                >
                    {{ contest.description }}
                </p>
            </div>

            <!-- Join card -->
            <div
                class="mb-8 rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
            >
                <template v-if="entry">
                    <p
                        class="text-[10px] font-black tracking-widest uppercase"
                        :class="{
                            'text-amber-400': entry.status === 'pending',
                            'text-emerald-400': entry.status === 'accepted',
                            'text-rose-400': entry.status === 'rejected',
                        }"
                    >
                        Status: {{ entry.status }}
                    </p>
                    <p
                        v-if="entry.status === 'pending'"
                        class="mt-2 text-xs text-slate-400"
                    >
                        Your request is waiting for the host to approve it.
                    </p>
                    <p
                        v-else-if="entry.status === 'accepted'"
                        class="mt-2 text-xs text-slate-400"
                    >
                        You're in. Head to the contest page to make your picks.
                    </p>
                    <p v-else class="mt-2 text-xs text-slate-400">
                        Your request was not approved.
                    </p>
                </template>

                <template v-else-if="!auth">
                    <p class="text-xs text-slate-400">
                        You need an account to join this contest.
                    </p>
                    <div class="mt-4 flex gap-2">
                        <Link
                            href="/login"
                            class="rounded-lg border border-[#232d42] bg-[#111622] px-5 py-2.5 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/40 hover:text-sky-400"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="`/register?redirect=${encodeURIComponent(`/contests/join/${contest.uuid}`)}`"
                            class="rounded-lg bg-amber-500 px-5 py-2.5 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
                        >
                            Register
                        </Link>
                    </div>
                </template>

                <template v-else-if="canJoin">
                    <p class="text-xs text-slate-400">
                        Free to join. No entry fee. You'll pick one selection
                        per leg before the deadline.
                    </p>
                    <button
                        type="button"
                        @click="join"
                        class="mt-4 cursor-pointer rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
                    >
                        Request to join
                    </button>
                </template>

                <template v-else>
                    <p class="text-xs text-slate-400">
                        This contest is not currently accepting new entries.
                    </p>
                </template>
            </div>

            <!-- Legs preview -->
            <div class="overflow-hidden rounded-lg border border-[#232d42]">
                <div
                    class="flex items-center justify-between border-b border-[#232d42] bg-[#111a30] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        The Picks
                    </span>
                    <span
                        class="font-mono text-[10px] text-slate-500 uppercase"
                    >
                        {{ contest.legs.length }} legs
                    </span>
                </div>

                <ul class="divide-y divide-[#232d42]/60">
                    <li
                        v-for="(leg, i) in contest.legs"
                        :key="leg.id"
                        class="flex items-center justify-between gap-4 px-5 py-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                            >
                                Leg {{ i + 1 }} · {{ leg.market }}
                            </p>
                            <p
                                class="mt-0.5 truncate text-sm font-bold text-slate-200"
                            >
                                {{ leg.fixture.home_team }}
                                <span class="text-slate-500">vs</span>
                                {{ leg.fixture.away_team }}
                            </p>
                        </div>
                        <span
                            class="whitespace-nowrap font-mono text-[10px] text-slate-500"
                        >
                            {{ formatKickoff(leg.fixture.kickoff) }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>