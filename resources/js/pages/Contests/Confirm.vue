<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    legs: { type: Array, required: true },
    min_legs: { type: Number, default: 5 },
    max_legs: { type: Number, default: 50 },
    earliest_kickoff: { type: String, default: null },
});

const form = useForm({
    name: '',
    description: '',
    entry_deadline_at: '',
});

// datetime-local wants 'YYYY-MM-DDTHH:mm' in local time. earliest_kickoff
// is ISO-8601 with offset, so we peel it apart via the Date object.
const maxDeadline = computed(() => {
    if (!props.earliest_kickoff) return null;
    const d = new Date(props.earliest_kickoff);
    d.setMinutes(d.getMinutes() - 1); // strictly before kickoff
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
});

const totalOdds = computed(() =>
    props.legs.reduce((carry, leg) => carry * (leg.odds ?? 1), 1),
);

const submit = () => {
    form.post('/contests', { preserveScroll: true });
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
    <Head title="Configure your contest — Betslip Pirates" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <!-- Back -->
            <Link
                href="/contests/create"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to picks
            </Link>

            <!-- ═══ HERO ═══ -->
            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <p class="text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Step 2 of 2
                </p>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    Configure your contest
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed font-medium text-slate-400">
                    Your picks are locked in. Name it, set the entry deadline,
                    then publish. Friends will request to join once it's live.
                </p>
            </div>

            <!-- ═══ PICKS SUMMARY ═══ -->
            <section class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <header class="flex items-center justify-between border-b border-[#232d42] px-5 py-3">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Your picks
                    </span>
                    <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                        {{ legs.length }} {{ legs.length === 1 ? 'leg' : 'legs' }}
                    </span>
                </header>

                <ul class="divide-y divide-[#232d42]/60">
                    <li
                        v-for="leg in legs"
                        :key="`${leg.fixture_id}-${leg.market_id}`"
                        class="flex items-start justify-between gap-4 px-5 py-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-200">
                                {{ leg.home_team }} <span class="text-slate-500">vs</span> {{ leg.away_team }}
                            </p>
                            <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                <span v-if="leg.league">{{ leg.league }}</span>
                                <span v-if="leg.league && leg.kickoff" class="text-slate-600"> · </span>
                                <span v-if="leg.kickoff">{{ formatKickoff(leg.kickoff) }}</span>
                            </p>
                            <p class="mt-1 truncate text-[11px] font-semibold text-sky-400">
                                {{ leg.market_label }} —
                                <span class="text-slate-300">{{ leg.selection }}</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="font-mono text-sm font-black text-emerald-400">
                                {{ leg.odds != null ? Number(leg.odds).toFixed(2) : '—' }}
                            </p>
                        </div>
                    </li>
                </ul>

                <footer class="flex items-center justify-between border-t border-[#232d42] bg-[#0f1422] px-5 py-3">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Total odds
                    </span>
                    <span class="font-mono text-lg font-black text-emerald-400">
                        {{ totalOdds.toFixed(2) }}×
                    </span>
                </footer>
            </section>

            <!-- ═══ CONFIG ═══ -->
            <section class="mb-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                <!-- Name -->
                <label for="name" class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Contest name
                </label>
                <p class="mt-1 mb-3 text-[11px] text-slate-500">
                    Shown to everyone you invite.
                </p>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    maxlength="80"
                    placeholder="Sunday Crew"
                    class="w-full rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                />
                <p v-if="form.errors.name" class="mt-1 text-[11px] text-rose-400">
                    {{ form.errors.name }}
                </p>

                <!-- Description -->
                <div class="mt-6">
                    <label for="description" class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                        Description <span class="font-medium text-slate-600">— optional</span>
                    </label>
                    <p class="mt-1 mb-3 text-[11px] text-slate-500">
                        A one-line pitch for the invite link.
                    </p>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        maxlength="500"
                        placeholder="Weekly five-pick challenge among friends."
                        class="w-full resize-none rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                    ></textarea>
                    <p class="mt-1 text-right text-[10px] font-bold tracking-widest text-slate-600 uppercase">
                        {{ form.description.length }} / 500
                    </p>
                </div>

                <!-- Deadline -->
                <div class="mt-6">
                    <label for="deadline" class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                        Entry deadline
                    </label>
                    <p class="mt-1 mb-3 text-[11px] text-slate-500">
                        Picks lock at this time. Must be before the earliest kickoff.
                    </p>
                    <input
                        id="deadline"
                        v-model="form.entry_deadline_at"
                        type="datetime-local"
                        :max="maxDeadline"
                        class="w-full rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-2.5 font-mono text-sm text-white focus:border-sky-500 focus:outline-none"
                    />
                    <p v-if="form.errors.entry_deadline_at" class="mt-1 text-[11px] text-rose-400">
                        {{ form.errors.entry_deadline_at }}
                    </p>
                </div>
            </section>

            <p v-if="form.errors.legs" class="mb-6 text-[11px] text-rose-400">
                {{ form.errors.legs }}
            </p>

            <!-- ═══ ACTIONS ═══ -->
            <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    href="/contests/create"
                    class="rounded-lg border border-[#232d42] px-6 py-3 text-center text-xs font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/50 hover:text-slate-200"
                >
                    Back
                </Link>
                <button
                    type="button"
                    @click="submit"
                    :disabled="form.processing || !form.name.trim() || !form.entry_deadline_at"
                    class="cursor-pointer rounded-lg bg-[#ff8c00] px-8 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100"
                >
                    {{ form.processing ? 'Publishing…' : 'Publish contest' }}
                </button>
            </div>
        </div>
    </div>
</template>