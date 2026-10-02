<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InfoPopover from '@/components/InfoPopover.vue';

const page = usePage();
const contest = computed(() => page.props.contest);
const processing = ref(null);
const copied = ref(false);

const shareUrl = computed(
    () => `${window.location.origin}/contests/join/${contest.value.uuid}`,
);

const shareText = computed(
    () =>
        `Join my contest "${contest.value.name}" on Betslip Pirates — ${contest.value.legs_count} legs, picks lock at the deadline.`,
);

const socialLinks = computed(() => ({
    whatsapp: `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText.value + ' ' + shareUrl.value)}`,
    telegram: `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareText.value)}`,
    twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareText.value)}`,
}));

const resultsUrl = computed(() => `/contests/${contest.value.uuid}/results`);

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch (err) {
        console.error('Failed to copy text:', err);
    }
};

const triggerNativeShare = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: contest.value.name,
                text: shareText.value,
                url: shareUrl.value,
            });
        } catch (err) {
            if (err.name !== 'AbortError') console.error('Share failed:', err);
        }
    } else {
        copyToClipboard(shareUrl.value);
    }
};

const scrollToShare = () => {
    document
        .getElementById('share-section')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

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
    router.post(
        `/contests/${contest.value.id}/entries/${entry.id}/accept`,
        {},
        {
            preserveScroll: true,
            onFinish: () => (processing.value = null),
        },
    );
};

const reject = (entry) => {
    processing.value = entry.id;
    router.post(
        `/contests/${contest.value.id}/entries/${entry.id}/reject`,
        {},
        {
            preserveScroll: true,
            onFinish: () => (processing.value = null),
        },
    );
};

const statusPill = (status) => {
    if (status === 'pending')
        return 'border-amber-500/30 bg-amber-500/10 text-amber-400';
    if (status === 'accepted')
        return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400';
    if (status === 'rejected')
        return 'border-rose-500/30 bg-rose-500/10 text-rose-400';
    return 'border-slate-500/30 bg-slate-500/10 text-slate-400';
};
</script>

<template>
    <Head :title="`Manage — ${contest.name}`" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-3xl px-2 py-12 md:px-6 lg:px-8">
            <Link
                href="/contests/mine"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to my contests
            </Link>

            <!-- HEADER -->
            <div class="mt-6 mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-[#232d42] pb-6">
    <div>
        <div class="flex items-center gap-2">
            <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
                Managing
            </p>
            <InfoPopover title="Managing your contest">
                <p>
                    Share the invite link with anyone you want in the contest. Their
                    requests appear below for you to approve or reject.
                </p>
                <p>
                    You've already made your picks at creation — they're locked in and
                    shown above. You play against everyone you accept.
                </p>
                <p class="text-slate-400">
                    Everyone else's picks stay hidden until the contest settles.
                </p>
            </InfoPopover>
        </div>
        <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
            {{ contest.name }}
        </h1>
        <p class="mt-3 text-sm text-slate-400">
            {{ contest.legs_count }} legs · Deadline
            {{ formatDate(contest.entry_deadline_at) }}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <button
            v-if="contest.status === 'open'"
            type="button"
            @click="scrollToShare"
            class="cursor-pointer rounded-lg border border-[#232d42] bg-[#111622] px-5 py-2.5 text-[10px] font-black tracking-widest text-slate-300 uppercase transition-colors hover:border-sky-500/40 hover:text-sky-400"
        >
            Share invite
        </button>

        <Link
            v-if="contest.status === 'settled'"
            :href="resultsUrl"
            class="rounded-lg bg-amber-500 px-5 py-2.5 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
        >
            View results →
        </Link>
    </div>
</div>

            <!-- SHARE CARD -->
            <section
                v-if="contest.status === 'open'"
                id="share-section"
                class="mb-8 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <div
                    class="border-b border-[#232d42] bg-[#111a30] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Invite friends
                    </span>
                </div>

                <div class="space-y-4 p-5">
                    <!-- Invite link -->
                    <div>
                        <label
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Invite link
                        </label>
                        <p class="mt-1 mb-3 text-[11px] text-slate-500">
                            Anyone with this link can request to join. You
                            approve who gets in.
                        </p>
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-3"
                        >
                            <span
                                class="min-w-0 flex-1 truncate font-mono text-sm font-black tracking-widest text-[#ff8c00]"
                            >
                                {{ shareUrl }}
                            </span>
                            <button
                                type="button"
                                @click="copyToClipboard(shareUrl)"
                                class="flex-shrink-0 cursor-pointer rounded-lg border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:bg-sky-500/20"
                            >
                                {{ copied ? 'Copied!' : 'Copy link' }}
                            </button>
                        </div>
                    </div>

                    <!-- Social share -->
                    <div>
                        <label
                            class="block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Share directly
                        </label>
                        <p class="mt-1 mb-3 text-[11px] text-slate-500">
                            Send it straight to WhatsApp, Telegram, or X.
                        </p>

                        <div
                            class="grid grid-cols-2 gap-3 sm:grid-cols-4"
                        >
                            <a
                                :href="socialLinks.whatsapp"
                                target="_blank"
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-emerald-500 hover:text-emerald-400"
                            >
                                <svg
                                    class="h-5 w-5 fill-current"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.713-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.42 9.864-9.858.002-2.634-1.023-5.11-2.885-6.974C16.528 1.91 14.058.883 11.432.882c-5.443 0-9.863 4.42-9.867 9.861-.001 1.73.458 3.422 1.33 4.925l-.995 3.635 3.722-.975zm13.125-7.391c-.244-.122-1.441-.712-1.664-.792-.223-.081-.385-.122-.547.122-.162.244-.628.792-.77 0-.142-.162-.284-.203-.527-.325-.244-.122-.963-.355-1.835-1.134-.679-.605-1.138-1.353-1.272-1.596-.134-.244-.014-.375.107-.496.111-.109.244-.284.365-.426.122-.142.162-.244.243-.406.081-.162.041-.305-.02-.426-.061-.122-.547-1.319-.75-1.808-.198-.476-.399-.413-.547-.42-.14-.007-.302-.008-.464-.008s-.426.061-.649.305c-.223.244-.852.833-.852 2.031s.872 2.356.994 2.519c.122.163 1.717 2.622 4.16 3.679.581.252 1.034.402 1.388.515.584.185 1.116.159 1.536.096.468-.071 1.441-.589 1.643-1.158.203-.569.203-1.057.142-1.158-.06-.101-.223-.162-.466-.283z"
                                    />
                                </svg>
                                <span
                                    class="text-[10px] font-bold tracking-wider uppercase"
                                    >WhatsApp</span
                                >
                            </a>

                            <a
                                :href="socialLinks.telegram"
                                target="_blank"
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-sky-500 hover:text-sky-400"
                            >
                                <svg
                                    class="h-5 w-5 fill-current"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18.717-.962 4.084-1.362 5.483-.167.55-.542.717-.892.733-.75.033-1.316-.417-2.033-.85-.183-.117-.35-.233-.517-.35-.55-.417-.183-.633.117-.95.083-.083 1.483-1.35 1.517-1.483.003-.017.003-.083-.031-.117s-.1-.017-.133-.017c-.05 0-1.15.65-2.284 1.333l-2.016 1.183c-.45.267-.85.317-1.167.317-.35 0-1.034-.167-1.534-.317-.616-.183-1.1-.283-1.066-.6.017-.167.267-.333.733-.517 2.884-1.117 4.816-1.85 5.8-2.2 2.8-.983 3.383-1.15 3.766-1.15.083 0 .283.017.417.117.117.1.15.233.167.367z"
                                    />
                                </svg>
                                <span
                                    class="text-[10px] font-bold tracking-wider uppercase"
                                    >Telegram</span
                                >
                            </a>

                            <a
                                :href="socialLinks.twitter"
                                target="_blank"
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-slate-500 hover:text-white"
                            >
                                <svg
                                    class="h-5 w-5 fill-current"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                                    />
                                </svg>
                                <span
                                    class="text-[10px] font-bold tracking-wider uppercase"
                                    >X</span
                                >
                            </a>

                            <button
                                type="button"
                                @click="triggerNativeShare"
                                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-[#ff8c00] transition-all hover:border-[#ff8c00] hover:bg-[#ff8c00]/5"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"
                                    />
                                </svg>
                                <span
                                    class="text-[10px] font-bold tracking-wider uppercase"
                                    >More</span
                                >
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- YOUR PICKS (locked) -->
            <section
                v-if="contest.host_picks.length > 0"
                class="mb-8 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <div
                    class="flex items-center justify-between border-b border-[#232d42] bg-[#111a30] px-5 py-3"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                    >
                        Your picks (locked)
                    </span>
                    <span
                        class="font-mono text-[10px] tracking-widest text-slate-500 uppercase"
                    >
                        {{ contest.host_picks.length }} legs
                    </span>
                </div>

                <ul class="divide-y divide-[#232d42]/60">
                    <li
                        v-for="(pick, i) in contest.host_picks"
                        :key="pick.leg_id"
                        class="flex flex-wrap items-center justify-between gap-3 px-5 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                            >
                                Leg {{ i + 1 }} · {{ pick.market }}
                            </p>
                            <p class="mt-0.5 truncate text-sm font-bold text-slate-200">
                                {{ pick.home_team }}
                                <span class="text-slate-500">vs</span>
                                {{ pick.away_team }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <template v-if="pick.selection">
                                <span
                                    class="rounded border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-[10px] font-black tracking-wide text-emerald-400 uppercase"
                                >
                                    {{ pick.selection }}
                                </span>
                                <span class="font-mono text-[10px] text-amber-400">
                                    @{{ Number(pick.odds).toFixed(2) }}
                                </span>
                            </template>
                            <span
                                v-else
                                class="text-[10px] font-bold tracking-widest text-slate-600 uppercase"
                            >
                                —
                            </span>
                        </div>
                    </li>
                </ul>

                <div
                    class="border-t border-[#232d42] bg-[#0a101f] px-5 py-2 text-[10px] text-slate-500"
                >
                    Your picks are locked at creation. To change them, start a new
                    contest.
                </div>
            </section>

            <!-- ENTRIES -->
            <div
                class="mb-4 flex items-center justify-between border-b border-[#232d42] pb-3"
            >
                <span
                    class="text-[10px] font-black tracking-widest text-slate-400 uppercase"
                >
                    Entries [{{ contest.entries.length }}]
                </span>
                <span
                    v-if="pendingCount > 0"
                    class="font-mono text-[10px] font-black tracking-widest text-amber-400 uppercase"
                >
                    {{ pendingCount }} pending
                </span>
            </div>

            <div
                v-if="contest.entries.length === 0"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] p-12 text-center"
            >
                <p
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                >
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
                                <span
                                    class="truncate font-mono text-sm font-black text-white"
                                >
                                    {{ entry.user.name }}
                                </span>
                                <span
                                    v-if="entry.user.is_verified"
                                    class="flex h-3.5 w-3.5 flex-shrink-0 items-center justify-center rounded-full bg-sky-500 text-[7px] font-black text-[#070b14]"
                                    >✓</span
                                >
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