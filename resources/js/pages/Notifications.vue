<template>
    <Head title="Notifications — Betslip Pirates" />

    <ListPage
        label="Account"
        title="Notifications"
        subtitle="Everything that's happened on your account — betslips, contests, referrals, and wallet activity."
        back-href="/dashboard"
        back-label="Dashboard"
    >
        <template #filters>
            <div class="flex flex-col gap-3">
                <ListFilters
                    v-model="statusFilter"
                    label="Status"
                    :options="statusOptions"
                    @update:model-value="applyFilters"
                />
                <ListFilters
                    v-model="typeFilter"
                    label="Type"
                    :options="typeOptions"
                    @update:model-value="applyFilters"
                />
            </div>
        </template>

        <!-- Bulk action bar -->
        <div
            v-if="unreadCount > 0"
            class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded border border-sky-500/20 bg-sky-500/5 px-3 py-2"
        >
            <span
                class="font-mono text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                {{ unreadCount }} unread
            </span>
            <button
                type="button"
                @click="markAllAsRead"
                :disabled="isProcessing"
                class="cursor-pointer rounded border border-sky-500/40 bg-sky-500/5 px-3 py-1 font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase transition-all hover:border-sky-500 hover:bg-sky-500/20 disabled:opacity-40"
            >
                Mark all read
            </button>
        </div>

        <div
            class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
        >
            <EmptyState
                v-if="notifications.data.length === 0"
                :title="emptyTitle"
                :body="emptyBody"
                :cta-label="emptyCtaLabel"
                :cta-href="emptyCtaHref"
                accent="sky"
            />

            <div v-else class="divide-y divide-gray-800/40">
                <div
                    v-for="n in notifications.data"
                    :key="n.id"
                    @click="handleClick(n)"
                    :class="[
                        'group flex cursor-pointer items-start gap-3 p-3 transition-colors hover:bg-[#111a30]/40',
                        !n.read ? 'bg-sky-500/[0.03]' : '',
                    ]"
                >
                    <!-- Icon -->
                    <div
                        :class="[
                            'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded text-sm',
                            iconBg(n.type, n.outcome),
                        ]"
                    >
                        {{ iconEmoji(n.type, n.outcome) }}
                    </div>

                    <!-- Content -->
                    <div class="min-w-0 flex-1">
                        <div
                            class="flex items-start justify-between gap-2"
                        >
                            <p
                                class="text-[11px] font-black tracking-widest text-white uppercase"
                            >
                                {{ n.title || 'Notification' }}
                            </p>
                            <div class="flex flex-shrink-0 items-center gap-2">
                                <span
                                    v-if="!n.read"
                                    class="h-1.5 w-1.5 rounded-full bg-sky-500"
                                ></span>
                                <button
                                    type="button"
                                    @click.stop="dismiss(n)"
                                    aria-label="Dismiss notification"
                                    class="cursor-pointer rounded text-slate-600 transition-colors hover:text-rose-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                        class="h-3.5 w-3.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6 18 18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <p
                            class="mt-1 text-[11px] leading-relaxed text-slate-400"
                        >
                            {{ n.message }}
                        </p>

                        <!-- Metadata chips (type-aware) -->
                        <div
                            class="mt-2 flex flex-wrap items-center gap-2 font-mono text-[9px]"
                        >
                            <span
                                v-if="n.betslip_code"
                                class="rounded border border-sky-500/20 bg-sky-500/5 px-1.5 py-0.5 font-bold text-sky-400"
                            >
                                #{{ n.betslip_code }}
                            </span>
                            <span
                                v-if="n.contest_name"
                                class="rounded border border-purple-500/20 bg-purple-500/5 px-1.5 py-0.5 font-bold text-purple-400"
                            >
                                {{ n.contest_name }}
                            </span>
                            <span
                                v-if="n.rank && n.total_participants"
                                class="rounded border border-amber-500/20 bg-amber-500/5 px-1.5 py-0.5 font-bold text-amber-400"
                            >
                                Rank {{ n.rank }}/{{ n.total_participants }}
                            </span>
                            <span
                                v-if="n.amount"
                                class="rounded border border-emerald-500/20 bg-emerald-500/5 px-1.5 py-0.5 font-bold text-emerald-400"
                            >
                                KES {{ Number(n.amount).toFixed(2) }}
                            </span>
                            <span class="text-slate-500">
                                {{ n.time }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #pagination>
            <ListPagination :paginator="notifications" />
        </template>
    </ListPage>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import ListPage from '@/components/ListPage.vue';
import ListFilters from '@/components/ListFilters.vue';
import ListPagination from '@/components/ListPagination.vue';
import EmptyState from '@/components/EmptyState.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    unread_count: { type: Number, default: 0 },
});

const isProcessing = ref(false);

// ─── Filters ─────────────────────────────────────────────────────────
const statusOptions = [
    { value: 'all',    label: 'All' },
    { value: 'unread', label: 'Unread' },
];

const typeOptions = [
    { value: null,       label: 'All' },
    { value: 'betslip',  label: 'Betslips' },
    { value: 'contest',  label: 'Contests' },
    { value: 'watch',    label: 'Watching' },
    { value: 'referral', label: 'Referrals' },
    { value: 'wallet',   label: 'Wallet' },
];

const statusFilter = ref(props.filters.status ?? 'all');
const typeFilter = ref(props.filters.type ?? null);

const applyFilters = () => {
    const params = {};
    if (statusFilter.value && statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }
    if (typeFilter.value) {
        params.type = typeFilter.value;
    }

    router.get('/notifications', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(() => props.filters.status, (v) => { statusFilter.value = v ?? 'all'; });
watch(() => props.filters.type,   (v) => { typeFilter.value = v ?? null; });

// ─── Empty state (filter-aware) ─────────────────────────────────────
const filterActive = computed(
    () =>
        (statusFilter.value && statusFilter.value !== 'all') ||
        typeFilter.value !== null,
);

const emptyTitle = computed(() =>
    filterActive.value ? 'No notifications match' : 'No notifications yet',
);

const emptyBody = computed(() =>
    filterActive.value
        ? 'Try a different filter, or clear them to see everything.'
        : "You'll see notifications here when your betslips settle, contests progress, and rewards land.",
);

const emptyCtaLabel = computed(() =>
    filterActive.value ? 'Clear filters' : 'Back to dashboard',
);

const emptyCtaHref = computed(() =>
    filterActive.value ? '/notifications' : '/dashboard',
);

// ─── Row actions ────────────────────────────────────────────────────
const handleClick = async (n) => {
    if (!n.read) {
        // Fire-and-forget — the navigation is the primary intent.
        axios.post(`/notifications/${n.id}/read`).catch(() => {});
    }

    const href = hrefFor(n);
    if (href) {
        router.visit(href);
    } else {
        // No destination — reload to reflect the read state.
        router.reload({ only: ['notifications', 'unread_count'] });
    }
};

const dismiss = async (n) => {
    try {
        await axios.delete(`/notifications/${n.id}`);
        router.reload({ only: ['notifications', 'unread_count'] });
    } catch {
        // silent
    }
};

const markAllAsRead = async () => {
    try {
        isProcessing.value = true;
        await axios.post('/notifications/read-all');
        router.reload({ only: ['notifications', 'unread_count'] });
    } catch {
        // silent
    } finally {
        isProcessing.value = false;
    }
};

// ─── Deep-link resolution ───────────────────────────────────────────
const hrefFor = (n) => {
    switch (n.type) {
        case 'betslip_won':
        case 'betslip_lost':
        case 'betslip_voided':
        case 'watch_settled':
            return n.betslip_code ? `/betslip/view/g/${n.betslip_code}` : null;

        case 'contest_settled':
            return n.contest_uuid ? `/contests/${n.contest_uuid}/results` : null;

        case 'contest_join_requested':
        case 'contest_entry_status':
            return n.contest_uuid
                ? `/contests/${n.contest_uuid}/manage`
                : null;

        case 'referral_signup':
        case 'referral_attributed':
        case 'referral_reward':
            return '/refer';

        case 'deposit':
        case 'withdrawal':
        case 'pending_release':
        case 'payout':
        case 'fee':
            return '/wallet/transactions';

        default:
            return null;
    }
};

// ─── Icon + colour mapping (mirrors NotificationBell) ───────────────
const iconEmoji = (type, outcome) => {
    if (type === 'watch_settled') {
        return outcome === 'won' ? '🏆' : outcome === 'voided' ? '↩️' : '💸';
    }
    switch (type) {
        case 'betslip_won':             return '🏆';
        case 'betslip_lost':            return '💸';
        case 'betslip_voided':          return '↩️';
        case 'deposit':                 return '💰';
        case 'withdrawal':              return '🏦';
        case 'referral_reward':         return '💰';
        case 'referral_attributed':     return '🎁';
        case 'referral_signup':         return '🎉';
        case 'contest_join_requested':  return '🙋';
        case 'contest_entry_status':    return '✅';
        case 'contest_settled':         return '🎯';
        default:                        return '🔔';
    }
};

const iconBg = (type, outcome) => {
    if (type === 'watch_settled') {
        return outcome === 'won'
            ? 'bg-emerald-500/15 text-emerald-400'
            : outcome === 'voided'
              ? 'bg-slate-500/15 text-slate-400'
              : 'bg-rose-500/15 text-rose-400';
    }
    switch (type) {
        case 'betslip_won':             return 'bg-emerald-500/15 text-emerald-400';
        case 'betslip_lost':            return 'bg-amber-500/15 text-amber-400';
        case 'betslip_voided':          return 'bg-slate-500/15 text-slate-400';
        case 'deposit':                 return 'bg-sky-500/15 text-sky-400';
        case 'withdrawal':              return 'bg-purple-500/15 text-purple-400';
        case 'referral_reward':         return 'bg-amber-500/15 text-amber-400';
        case 'referral_attributed':     return 'bg-amber-500/15 text-amber-400';
        case 'referral_signup':         return 'bg-emerald-500/15 text-emerald-400';
        case 'contest_settled':         return 'bg-amber-500/15 text-amber-400';
        case 'contest_join_requested':  return 'bg-purple-500/15 text-purple-400';
        case 'contest_entry_status':    return 'bg-sky-500/15 text-sky-400';
        default:                        return 'bg-slate-500/15 text-slate-400';
    }
};
</script>