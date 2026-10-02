<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const contest = computed(() => page.props.contest);
const processing = ref(null);

const formatDate = (iso) => {
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

const accept = (entry) => {
    processing.value = entry.id;
    router.post(`/contests/${contest.value.id}/entries/${entry.id}/accept`, {}, {
        preserveScroll: true,
        onFinish: () => (processing.value = null),
    });
};

const reject = (entry) => {
    processing.value = entry.id;
    router.post(`/contests/${contest.value.id}/entries/${entry.id}/reject`, {}, {
        preserveScroll: true,
        onFinish: () => (processing.value = null),
    });
};

const statusPill = (status) => {
    if (status === 'pending')  return 'border-amber-500/30 bg-amber-500/10 text-amber-400';
    if (status === 'accepted') return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400';
    if (status === 'rejected') return 'border-rose-500/30 bg-rose-500/10 text-rose-400';
    return 'border-slate-500/30 bg-slate-500/10 text-slate-400';
};
</script>

<template>
    <Head :title="`Manage — ${contest.name}`" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-3xl px-4 py-12 md:px-6 lg:px-8">
            <Link
                href="/contests/mine"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to my contests
            </Link>

            <div class="mt-6 mb-8 flex items-end justify-between border-b border-[#232d42] pb-6">
                <div>
                    <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                        Managing
                    </p>
                    <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                        {{ contest.name }}
                    </h1>
                    <p class="mt-3 text-sm text-slate-400">
                        {{ contest.legs_count }} legs · Deadline
                        {{ formatDate(contest.entry_deadline_at) }}
                    </p>
                </div>
                <a
                    :href="`/contests/join/${contest.uuid}`"
                    class="rounded-lg border border-[#232d42] bg-[#111622] px-5 py-2.5 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/40 hover:text-sky-400"
                >
                    Share invite
                </a>
            </div>

            <div
                v-if="contest.entries.length === 0"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] p-12 text-center"
            >
                <p class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    No entries yet
                </p>
                <p class="mt-3 text-sm text-slate-400">
                    Share the invite link above. Requests will appear here for
                    you to approve.
                </p>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="entry in contest.entries"
                    :key="entry.id"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <img
                            :src="entry.user.avatar"
                            :alt="entry.user.name"
                            class="h-10 w-10 flex-shrink-0 rounded-full border border-[#232d42] object-cover"
                        />
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="truncate font-mono text-sm font-black text-white">
                                    {{ entry.user.name }}
                                </span>
                                <span
                                    v-if="entry.user.is_verified"
                                    class="flex h-3.5 w-3.5 flex-shrink-0 items-center justify-center rounded-full bg-sky-500 text-[7px] font-black text-[#070b14]"
                                >✓</span>
                            </div>
                            <p class="mt-0.5 text-[10px] text-slate-500">
                                Requested {{ formatDate(entry.joined_at) }} ·
                                {{ entry.picks_count }} picks
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            :class="[
                                'rounded border px-2 py-0.5 font-mono text-[9px] font-black tracking-widest uppercase',
                                statusPill(entry.status),
                            ]"
                        >
                            {{ entry.status }}
                        </span>

                        <template v-if="entry.status === 'pending'">
                            <button
                                type="button"
                                @click="accept(entry)"
                                :disabled="processing === entry.id"
                                class="cursor-pointer rounded border border-emerald-500/40 bg-emerald-500/10 px-3 py-1.5 text-[10px] font-black tracking-widest text-emerald-400 uppercase transition-colors hover:bg-emerald-500/20 disabled:opacity-50"
                            >
                                Accept
                            </button>
                            <button
                                type="button"
                                @click="reject(entry)"
                                :disabled="processing === entry.id"
                                class="cursor-pointer rounded border border-rose-500/40 bg-rose-500/10 px-3 py-1.5 text-[10px] font-black tracking-widest text-rose-400 uppercase transition-colors hover:bg-rose-500/20 disabled:opacity-50"
                            >
                                Reject
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>