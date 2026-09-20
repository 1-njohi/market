<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: { type: Object, required: true },
    wallet: { type: Object, required: true },
    stats: { type: Object, required: true },
    seller_metric: { type: Object, default: null },
    recent_betslips: { type: Array, required: true },
    recent_purchases: { type: Array, required: true },
    recent_transactions: { type: Array, required: true },
});

const showSuspendModal = ref(false);
const showAdjustModal = ref(false);

const suspendForm = useForm({ reason: '' });
const adjustForm = useForm({ amount: '', reason: '' });

const formatKES = (n: number) =>
    'KES ' +
    Number(n || 0).toLocaleString('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const formatDate = (iso: string) => {
    const d = new Date(iso);
    return d.toLocaleString('en-KE', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
};

const submitSuspend = () => {
    suspendForm.post(`/admin/users/${props.user.id}/suspend`, {
        preserveScroll: true,
        onSuccess: () => {
            showSuspendModal.value = false;
            suspendForm.reset();
        },
    });
};

const submitUnsuspend = () => {
    if (!confirm(`Reactivate ${props.user.name}?`)) return;
    router.post(
        `/admin/users/${props.user.id}/unsuspend`,
        {},
        { preserveScroll: true },
    );
};

const submitAdjust = () => {
    adjustForm.post(`/admin/users/${props.user.id}/adjust-balance`, {
        preserveScroll: true,
        onSuccess: () => {
            showAdjustModal.value = false;
            adjustForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="`${user.name} — Admin`" />

    <div>
        <!-- Back -->
        <Link
            href="/admin/users"
            class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
        >
            ← Back to users
        </Link>

        <!-- ═══ HERO ═══ -->
        <div
            class="mt-5 mb-6 flex flex-col items-start gap-5 rounded-lg border border-[#232d42] bg-[#161c2a] p-6 md:flex-row md:items-center md:justify-between"
        >
            <div class="flex items-center gap-4">
                <img
                    :src="user.avatar"
                    :alt="user.name"
                    class="h-14 w-14 rounded-full border border-[#232d42]"
                />
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1
                            class="text-xl font-black tracking-wider text-white uppercase"
                        >
                            {{ user.name }}
                        </h1>
                        <span
                            v-if="user.is_admin"
                            class="rounded border border-sky-500/30 bg-sky-500/10 px-2 py-0.5 text-[9px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            Admin
                        </span>
                        <span
                            v-if="user.suspended_at"
                            class="rounded border border-rose-500/30 bg-rose-500/10 px-2 py-0.5 text-[9px] font-black tracking-widest text-rose-400 uppercase"
                        >
                            Suspended
                        </span>
                        <span
                            v-if="!user.is_verified"
                            class="rounded border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[9px] font-black tracking-widest text-amber-400 uppercase"
                        >
                            Unverified
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ user.email }} · {{ user.country_code
                        }}{{ user.phone }}
                    </p>
                    <p class="mt-0.5 font-mono text-[11px] text-sky-400">
                        {{ user.code }} · joined {{ user.joined_ago }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-if="!user.suspended_at"
                    type="button"
                    @click="showSuspendModal = true"
                    class="rounded border border-rose-500/30 bg-rose-500/5 px-4 py-2 text-[10px] font-black tracking-widest text-rose-400 uppercase transition-colors hover:border-rose-500 hover:bg-rose-500/10"
                >
                    Suspend user
                </button>
                <button
                    v-else
                    type="button"
                    @click="submitUnsuspend"
                    class="rounded border border-emerald-500/30 bg-emerald-500/5 px-4 py-2 text-[10px] font-black tracking-widest text-emerald-400 uppercase transition-colors hover:border-emerald-500 hover:bg-emerald-500/10"
                >
                    Reactivate user
                </button>
                <button
                    type="button"
                    @click="showAdjustModal = true"
                    class="rounded border border-sky-500/30 bg-sky-500/5 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:border-sky-500 hover:bg-sky-500/10"
                >
                    Adjust balance
                </button>
            </div>
        </div>

        <!-- Suspension banner -->
        <div
            v-if="user.suspended_at"
            class="mb-6 rounded-lg border border-rose-500/30 bg-rose-500/5 p-4"
        >
            <p
                class="text-[10px] font-black tracking-widest text-rose-400 uppercase"
            >
                Suspended on {{ formatDate(user.suspended_at) }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
                Reason: {{ user.suspension_reason }}
            </p>
        </div>

        <!-- ═══ WALLET ═══ -->
        <section class="mb-6">
            <h2
                class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Wallet
            </h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Available</span
                    >
                    <p class="mt-2 text-lg font-black text-emerald-400">
                        {{ formatKES(wallet.balance) }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Pending</span
                    >
                    <p class="mt-2 text-lg font-black text-amber-400">
                        {{ formatKES(wallet.escrow_balance) }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Total Deposited</span
                    >
                    <p class="mt-2 text-lg font-black text-sky-400">
                        {{ formatKES(wallet.total_deposited) }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Total Withdrawn</span
                    >
                    <p class="mt-2 text-lg font-black text-rose-400">
                        {{ formatKES(wallet.total_withdrawn) }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ═══ STATS ═══ -->
        <section class="mb-6">
            <h2
                class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Activity
            </h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Betslips Created</span
                    >
                    <p class="mt-2 text-lg font-black text-white">
                        {{ stats.betslips_created }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Betslips Purchased</span
                    >
                    <p class="mt-2 text-lg font-black text-white">
                        {{ stats.betslips_purchased }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Followers</span
                    >
                    <p class="mt-2 text-lg font-black text-white">
                        {{ stats.followers }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Following</span
                    >
                    <p class="mt-2 text-lg font-black text-white">
                        {{ stats.following }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ═══ SELLER METRIC ═══ -->
        <section v-if="seller_metric" class="mb-6">
            <h2
                class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Seller Performance
            </h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Win Rate</span
                    >
                    <p class="mt-2 text-lg font-black text-emerald-400">
                        {{ seller_metric.win_rate }}%
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >ROI</span
                    >
                    <p class="mt-2 text-lg font-black text-sky-400">
                        {{ seller_metric.roi }}%
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Total Sold</span
                    >
                    <p class="mt-2 text-lg font-black text-white">
                        {{ seller_metric.total_sold }}
                    </p>
                </div>
                <div
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >Revenue</span
                    >
                    <p class="mt-2 text-lg font-black text-amber-400">
                        {{ formatKES(seller_metric.total_revenue) }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ═══ RECENT ACTIVITY (2-col) ═══ -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Betslips -->
            <section>
                <h2
                    class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                >
                    Recent Betslips
                </h2>
                <div
                    class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
                >
                    <div
                        v-if="recent_betslips.length === 0"
                        class="p-6 text-center text-xs text-slate-500"
                    >
                        No betslips yet.
                    </div>
                    <div v-else class="divide-y divide-[#232d42]/60">
                        <Link
                            v-for="b in recent_betslips"
                            :key="b.id"
                            :href="`/betslip/view/g/${b.code}`"
                            class="flex items-center justify-between p-3 transition-colors hover:bg-[#111a30]"
                        >
                            <div>
                                <p
                                    class="font-mono text-[11px] font-bold text-sky-400"
                                >
                                    {{ b.code }}
                                </p>
                                <p class="mt-0.5 text-[10px] text-slate-500">
                                    {{ b.created_ago }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    class="font-mono text-xs font-black text-white"
                                >
                                    {{ b.total_odds }}x
                                </p>
                                <p class="text-[10px] text-slate-500">
                                    {{ formatKES(b.price) }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Purchases -->
            <section>
                <h2
                    class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                >
                    Recent Purchases
                </h2>
                <div
                    class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
                >
                    <div
                        v-if="recent_purchases.length === 0"
                        class="p-6 text-center text-xs text-slate-500"
                    >
                        No purchases yet.
                    </div>
                    <div v-else class="divide-y divide-[#232d42]/60">
                        <div
                            v-for="p in recent_purchases"
                            :key="p.id"
                            class="flex items-center justify-between p-3"
                        >
                            <div>
                                <p
                                    class="font-mono text-[11px] font-bold text-sky-400"
                                >
                                    {{ p.betslip_code }}
                                </p>
                                <p class="mt-0.5 text-[10px] text-slate-500">
                                    {{ p.created_ago }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    class="font-mono text-xs font-black text-white"
                                >
                                    {{ formatKES(p.purchase_price) }}
                                </p>
                                <p class="text-[10px] text-slate-500 uppercase">
                                    {{ p.status }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ═══ TRANSACTIONS ═══ -->
        <section class="mt-6">
            <h2
                class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
            >
                Recent Transactions
            </h2>
            <div
                class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead
                            class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3 text-right">
                                    Balance After
                                </th>
                                <th class="px-4 py-3 text-right">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#232d42]/60">
                            <tr
                                v-for="t in recent_transactions"
                                :key="t.id"
                                class="transition-colors hover:bg-[#111a30]"
                            >
                                <td
                                    class="px-4 py-3 font-mono text-[10px] font-bold text-slate-400 uppercase"
                                >
                                    {{ t.type }}
                                </td>
                                <td
                                    class="max-w-xs truncate px-4 py-3 text-slate-400"
                                    :title="t.description"
                                >
                                    {{ t.description }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono font-black"
                                    :class="
                                        t.amount >= 0
                                            ? 'text-emerald-400'
                                            : 'text-rose-400'
                                    "
                                >
                                    {{ t.amount >= 0 ? '+' : ''
                                    }}{{ t.amount.toFixed(2) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono text-slate-400"
                                >
                                    {{ formatKES(t.balance_after) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-[10px] text-slate-500"
                                >
                                    {{ t.created_ago }}
                                </td>
                            </tr>
                            <tr v-if="recent_transactions.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-8 text-center text-xs text-slate-500"
                                >
                                    No transactions.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ═══ SUSPEND MODAL ═══ -->
        <Teleport to="body">
            <div
                v-if="showSuspendModal"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="showSuspendModal = false"
            >
                <div
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg border border-[#232d42] bg-[#161c2a] p-6"
                >
                    <h3
                        class="text-base font-black tracking-widest text-white uppercase"
                    >
                        Suspend {{ user.name }}
                    </h3>
                    <p class="mt-2 text-xs text-slate-400">
                        The user will be blocked from the platform. This action
                        is logged and can be reversed.
                    </p>
                    <form
                        @submit.prevent="submitSuspend"
                        class="mt-4 space-y-3"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                            >
                                Reason (visible to the user)
                            </label>
                            <textarea
                                v-model="suspendForm.reason"
                                rows="4"
                                required
                                minlength="5"
                                class="w-full resize-none rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-sm text-white focus:border-rose-500 focus:outline-none"
                                placeholder="Explain why this account is being suspended…"
                            ></textarea>
                            <p
                                v-if="suspendForm.errors.reason"
                                class="mt-1 text-[10px] text-rose-400"
                            >
                                {{ suspendForm.errors.reason }}
                            </p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                @click="showSuspendModal = false"
                                class="rounded border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase hover:text-slate-200"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="suspendForm.processing"
                                class="rounded border border-rose-500 bg-rose-500 px-4 py-2 text-[10px] font-black tracking-widest text-white uppercase hover:bg-rose-600 disabled:opacity-50"
                            >
                                {{
                                    suspendForm.processing
                                        ? 'Suspending…'
                                        : 'Confirm suspend'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ═══ ADJUST BALANCE MODAL ═══ -->
        <Teleport to="body">
            <div
                v-if="showAdjustModal"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="showAdjustModal = false"
            >
                <div
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg border border-[#232d42] bg-[#161c2a] p-6"
                >
                    <h3
                        class="text-base font-black tracking-widest text-white uppercase"
                    >
                        Adjust {{ user.name }}'s Balance
                    </h3>
                    <p class="mt-2 text-xs text-slate-400">
                        Current available balance:
                        <strong class="text-emerald-400">{{
                            formatKES(wallet.balance)
                        }}</strong>
                    </p>
                    <form @submit.prevent="submitAdjust" class="mt-4 space-y-3">
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                            >
                                Amount (KES)
                            </label>
                            <input
                                v-model="adjustForm.amount"
                                type="number"
                                step="0.01"
                                required
                                class="w-full rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-sm text-white focus:border-sky-500 focus:outline-none"
                                placeholder="e.g. 500 or -250"
                            />
                            <p class="mt-1 text-[10px] text-slate-500">
                                Use a positive number to credit, negative to
                                debit.
                            </p>
                            <p
                                v-if="adjustForm.errors.amount"
                                class="mt-1 text-[10px] text-rose-400"
                            >
                                {{ adjustForm.errors.amount }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                            >
                                Reason (audit log)
                            </label>
                            <textarea
                                v-model="adjustForm.reason"
                                rows="3"
                                required
                                minlength="5"
                                class="w-full resize-none rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-sm text-white focus:border-sky-500 focus:outline-none"
                                placeholder="e.g. Refund for reported bug, or manual correction…"
                            ></textarea>
                            <p
                                v-if="adjustForm.errors.reason"
                                class="mt-1 text-[10px] text-rose-400"
                            >
                                {{ adjustForm.errors.reason }}
                            </p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                @click="showAdjustModal = false"
                                class="rounded border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase hover:text-slate-200"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="adjustForm.processing"
                                class="rounded border border-sky-500 bg-sky-500 px-4 py-2 text-[10px] font-black tracking-widest text-[#070b14] uppercase hover:bg-sky-400 disabled:opacity-50"
                            >
                                {{
                                    adjustForm.processing
                                        ? 'Applying…'
                                        : 'Confirm adjustment'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
