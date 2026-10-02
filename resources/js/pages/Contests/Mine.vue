<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InfoPopover from '@/components/InfoPopover.vue';

const page = usePage();
const contests = computed(() => page.props.contests);

const formatDate = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('en-KE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

const statusClass = (status) => {
    if (status === 'open') return 'border-sky-500/30 bg-sky-500/10 text-sky-400';
    if (status === 'settled') return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400';
    if (status === 'cancelled') return 'border-rose-500/30 bg-rose-500/10 text-rose-400';
    return 'border-slate-500/30 bg-slate-500/10 text-slate-400';
};
</script>

<template>
    <Head title="My Contests — Betslip Pirates" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-4xl px-2 py-12 md:px-6 lg:px-8">
            <div class="mb-8 flex items-end justify-between border-b border-[#232d42] pb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                            Your contests
                        </p>
                        <InfoPopover title="What are private contests?">
                            <p>
                                Private contests are challenges you run with people you
                                invite. You pick a set of fixtures, they join, and
                                everyone competes on the same picks.
                            </p>
                            <p>
                                When the matches settle, we score everyone and publish
                                the leaderboard.
                            </p>
                            <div class="rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-[11px] text-slate-400">
                                Free to run. No wallet, no entry fee.
                            </div>
                        </InfoPopover>
                    </div>
                    <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                        My Contests
                    </h1>
                </div>
                <Link
                    href="/contests/create"
                    class="rounded-lg bg-amber-500 px-5 py-2.5 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
                >
                    Create contest
                </Link>
            </div>

            <div
                v-if="contests.length === 0"
                class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <div class="border-b border-[#232d42] bg-[#111a30] p-6 text-center">
                    <p class="text-3xl">🏴‍☠️</p>
                    <p
                        class="mt-3 text-[10px] font-black tracking-widest text-amber-400 uppercase"
                    >
                        Private Contests
                    </p>
                    <h2
                        class="mt-2 text-xl font-black tracking-wider text-white uppercase"
                    >
                        No contests yet
                    </h2>
                </div>

                <div class="space-y-4 p-6 text-center">
                    <p class="mx-auto max-w-md text-sm leading-relaxed text-slate-400">
                        Create a private challenge where you and your friends pick
                        the same fixtures and compete on who does better. Free to run —
                        no wallet, no entry fee.
                    </p>

                    <div class="mx-auto max-w-sm space-y-2 text-left">
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-amber-500/20 font-mono text-[10px] font-black text-amber-400"
                            >1</span>
                            <p class="text-xs text-slate-400">
                                Pick 5–50 fixtures and set a deadline.
                            </p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-amber-500/20 font-mono text-[10px] font-black text-amber-400"
                            >2</span>
                            <p class="text-xs text-slate-400">
                                Share the invite link. Approve who joins.
                            </p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-amber-500/20 font-mono text-[10px] font-black text-amber-400"
                            >3</span>
                            <p class="text-xs text-slate-400">
                                Everyone makes their picks. We score them when the
                                matches settle.
                            </p>
                        </div>
                    </div>

                    <Link
                        href="/contests/create"
                        class="mt-4 inline-block rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
                    >
                        Create your first contest
                    </Link>
                </div>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="c in contests"
                    :key="c.id"
                    :href="`/contests/${c.id}/manage`"
                    class="block overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a] transition-colors hover:border-sky-500/40"
                >
                    <div class="flex items-center justify-between gap-4 p-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span
                                    class="truncate font-mono text-sm font-black text-white uppercase"
                                >
                                    {{ c.name }}
                                </span>
                                <span
                                    :class="[
                                        'rounded border px-1.5 py-0.5 font-mono text-[9px] font-black tracking-widest uppercase',
                                        statusClass(c.status),
                                    ]"
                                >
                                    {{ c.status }}
                                </span>
                            </div>
                            <p class="mt-1 text-[10px] text-slate-500">
                                Code {{ c.uuid }} · Deadline {{ formatDate(c.entry_deadline_at) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4 text-right">
                            <div v-if="c.pending_entries > 0">
                                <span class="block text-[9px] font-black tracking-widest text-amber-400 uppercase">
                                    Pending
                                </span>
                                <span class="mt-0.5 block font-mono text-lg font-black text-amber-400">
                                    {{ c.pending_entries }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[9px] font-black tracking-widest text-slate-500 uppercase">
                                    Accepted
                                </span>
                                <span class="mt-0.5 block font-mono text-lg font-black text-white">
                                    {{ c.accepted_entries }}
                                </span>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>