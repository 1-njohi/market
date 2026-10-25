<template>
    <div class="w-full overflow-hidden font-sans text-slate-200">
        <!-- Header with the deposit action -->
        <div class="flex items-center justify-between gap-3 border-b border-white/[0.06] px-5 py-4">
            <h2 class="text-base font-bold text-emerald-300">Transactions</h2>
            <div class="w-36 shrink-0 sm:w-44">
                <DepositPopover :currency="`KES`" :initial_amount="100" :initial_phone="buyer_data?.user?.phone ?? ''" />
            </div>
        </div>

        <div v-if="!transactions || transactions.length === 0" class="p-10 text-center text-sm text-slate-500">
            No transactions in this period.
        </div>

        <div v-else class="w-full">
            <!-- ─────── MOBILE ─────── -->
            <ul class="block divide-y divide-white/[0.05] md:hidden">
                <li v-for="tx in transactions" :key="tx.id" class="space-y-3 p-4 transition-colors hover:bg-white/[0.03]">
                    <div class="flex items-center justify-between gap-3">
                        <span :class="['rounded-full px-2.5 py-1 text-xs font-bold', isCredit(tx) ? 'bg-emerald-400/10 text-emerald-300' : 'bg-rose-400/10 text-rose-300']">
                            {{ typeLabel(tx.type) }}
                        </span>
                        <span
                            :class="[
                                'rounded-full px-2.5 py-1 text-xs font-semibold capitalize',
                                tx.status === 'completed'
                                    ? 'bg-emerald-400/10 text-emerald-300/90'
                                    : tx.status === 'pending'
                                      ? 'bg-amber-400/10 text-amber-300/90'
                                      : 'bg-rose-400/10 text-rose-300/90',
                            ]"
                        >
                            {{ tx.status }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span v-if="tx.outcome" :class="outcomeBadgeClass(tx.outcome)">{{ outcomeBadgeLabel(tx.outcome) }}</span>
                        <p class="text-sm font-semibold text-slate-200">{{ tx.description }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 rounded-2xl bg-black/25 p-3.5">
                        <div>
                            <p class="text-xs text-slate-500">Amount</p>
                            <p class="mt-0.5 text-lg font-black tabular-nums" :class="isCredit(tx) ? 'text-emerald-300' : 'text-rose-300'">
                                {{
                                    isCredit(tx)
                                        ? '+' + tx.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })
                                        : tx.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Balance after</p>
                            <p class="mt-0.5 text-lg font-bold text-white tabular-nums">
                                {{ tx.balance_after.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                            </p>
                        </div>
                    </div>

                    <p class="text-right text-xs text-slate-500 tabular-nums">{{ formatDate(tx.created_at) }}</p>
                </li>
            </ul>

            <!-- ─────── DESKTOP ─────── -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-white/[0.07] bg-black/20 text-xs font-semibold text-slate-500">
                            <th class="px-5 py-3.5">Type</th>
                            <th class="px-5 py-3.5">Description</th>
                            <th class="px-5 py-3.5 text-right">Amount</th>
                            <th class="px-5 py-3.5 text-right">Balance</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.05]">
                        <tr v-for="tx in transactions" :key="tx.id" class="group text-sm transition-colors hover:bg-white/[0.03]">
                            <td class="px-5 py-4 font-bold">
                                <span :class="isCredit(tx) ? 'text-emerald-300' : 'text-rose-300'">{{ typeLabel(tx.type) }}</span>
                            </td>

                            <td class="max-w-xs px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span v-if="tx.outcome" :class="outcomeBadgeClass(tx.outcome)">{{ outcomeBadgeLabel(tx.outcome) }}</span>
                                    <span class="truncate font-semibold text-slate-300" :title="tx.description">{{ tx.description }}</span>
                                </div>
                            </td>

                            <td :class="['px-5 py-4 text-right font-black tabular-nums', isCredit(tx) ? 'text-emerald-300' : 'text-rose-300']">
                                {{ isCredit(tx) ? '+' + tx.amount.toFixed(2) : tx.amount.toFixed(2) }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                <div class="font-bold text-white tabular-nums">
                                    {{ tx.balance_after.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                </div>
                                <div class="text-xs text-slate-600 tabular-nums">Before: {{ tx.balance_before.toFixed(2) }}</div>
                            </td>

                            <td class="px-5 py-4 text-center">
                                <span
                                    :class="[
                                        'rounded-full px-2.5 py-1 text-xs font-bold capitalize',
                                        tx.status === 'completed'
                                            ? 'bg-emerald-400/10 text-emerald-300'
                                            : tx.status === 'pending'
                                              ? 'bg-amber-400/10 text-amber-300'
                                              : 'bg-rose-400/10 text-rose-300',
                                    ]"
                                >
                                    {{ tx.status }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-right text-xs whitespace-nowrap text-slate-500 tabular-nums">{{ formatDate(tx.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import DepositPopover from './DepositPopover.vue';

// Props interface expectation mapping directly to array records
defineProps({
    transactions: {
        type: Array,
        required: true,
        default: () => [],
    },
});

// The template pre-fills the deposit phone number from the buyer's profile.
const buyer_data = usePage().props.buyer_data;

// Money coming in to the wallet; everything else is money going out.
const CREDIT_TYPES = ['deposit', 'refund', 'pending_release', 'payout'];
const isCredit = (tx) => CREDIT_TYPES.includes(tx.type);

const typeLabel = (type) => {
    const t = String(type).replace(/_/g, ' ');
    return t.charAt(0).toUpperCase() + t.slice(1);
};

// ISO Ledger timestamp normalizer string utility
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date
        .toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        })
        .replace(',', '');
};

const outcomeBadgeClass = (outcome) => {
    const base = 'shrink-0 rounded-full px-2.5 py-1 text-xs font-bold';
    const palette = {
        pending: 'bg-amber-400/10 text-amber-300',
        won: 'bg-emerald-400/10 text-emerald-300',
        refunded: 'bg-rose-400/10 text-rose-300',
        voided: 'bg-slate-400/10 text-slate-300',
    };
    return [base, palette[outcome] ?? ''];
};

const outcomeBadgeLabel = (outcome) => {
    return (
        {
            pending: 'Pending',
            won: 'Won',
            refunded: 'Refunded',
            voided: 'Voided',
        }[outcome] ?? outcome
    );
};
</script>