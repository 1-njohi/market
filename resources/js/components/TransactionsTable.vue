<template>
    <div
        class="mt-4 w-full overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40 font-sans text-slate-200"
    >
        <div
            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
        >
            <div class="flex items-center space-x-2 text-emerald-400">
                <span class="text-[10px] font-black tracking-widest uppercase"
                    >RECENT TRANSACTIONS</span
                >
            </div>
            <DepositPopover class="max-w-[10rem]" />
            <!-- <span class="text-[9px] font-mono font-bold text-slate-500 uppercase select-none">[ BALANCE_HISTOGRAM ]</span> -->
        </div>

        <div
            v-if="!transactions || transactions.length === 0"
            class="p-8 text-center font-mono text-xs tracking-wider text-slate-500 uppercase"
        >
            No transaction records found in this operational tracking cycle.
        </div>

        <div v-else class="w-full">
            <div class="block divide-y divide-gray-800/40 md:hidden">
                <div
                    v-for="tx in transactions"
                    :key="tx.id"
                    class="space-y-3 bg-[#111622]/20 p-4 transition-colors hover:bg-[#111622]/40"
                >
                    <div class="flex items-center justify-between">
                        <span
                            :class="[
                                'rounded border px-2 py-0.5 font-mono text-[10px] font-black tracking-wider uppercase',
                                tx.type === 'deposit'
                                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                    : 'border-rose-500/20 bg-rose-500/10 text-rose-400',
                            ]"
                        >
                            {{ tx.type }}
                        </span>

                        <span
                            :class="[
                                'rounded border px-1.5 py-0.5 font-mono text-[8px] font-black tracking-widest uppercase',
                                tx.status === 'completed'
                                    ? 'border-emerald-500/20 bg-emerald-500/5 text-emerald-400/80'
                                    : tx.status === 'pending'
                                      ? 'border-amber-500/20 bg-amber-500/5 text-amber-400/80'
                                      : 'border-rose-500/20 bg-rose-500/5 text-rose-400/80',
                            ]"
                        >
                            {{ tx.status }}
                        </span>
                    </div>

                    <p
                        class="font-sans text-xs font-bold tracking-wide text-slate-300 normal-case"
                    >
                        {{ tx.description }}
                    </p>

                    <div
                        class="grid grid-cols-2 gap-x-4 gap-y-2 border-t border-gray-800/40 pt-2 font-mono text-[10px] font-semibold uppercase"
                    >
                        <div>
                            <span
                                class="block text-[8px] font-bold tracking-wider text-slate-500"
                                >DIFFERENCE
                            </span>
                            <span
                                :class="
                                    (tx.type === 'deposit') |
                                    (tx.type === 'pending_payout') |
                                    (tx.type === 'purchase')
                                        ? tx.type === 'deposit'
                                            ? 'font-black text-emerald-400'
                                            : 'font-black text-amber-400'
                                        : 'font-black text-rose-400'
                                "
                            >
                                {{
                                    (tx.type === 'deposit') |
                                    (tx.type === 'pending_payout')
                                        ? '+'
                                        : '-'
                                }}
                                KES
                                {{
                                    tx.amount.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                    })
                                }}
                            </span>
                        </div>
                        <div>
                            <span
                                class="block text-[8px] font-bold tracking-wider text-slate-500"
                                >POST RECONCILIATION</span
                            >
                            <span class="font-mono font-bold text-white"
                                >KES
                                {{
                                    tx.balance_after.toLocaleString(undefined, {
                                        minimumFractionDigits: 2,
                                    })
                                }}</span
                            >
                        </div>
                    </div>

                    <div
                        class="pt-1 text-right font-mono text-[9px] text-slate-500"
                    >
                        TIME: {{ formatDate(tx.created_at) }}
                    </div>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr
                            class="border-b border-gray-800/60 bg-[#0f1422] font-mono text-[10px] font-black tracking-widest text-slate-400 uppercase"
                        >
                            <th class="px-4 py-3">TYPE</th>
                            <th class="px-4 py-3">DESCRIPTION</th>
                            <th class="px-4 py-3 text-right">DIFFERENCE</th>
                            <th class="px-4 py-3 text-right">BALANCE</th>
                            <th class="px-4 py-3 text-center">STATUS</th>
                            <th class="px-4 py-3 text-right">TIME</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/30">
                        <tr
                            v-for="tx in transactions"
                            :key="tx.id"
                            class="group border-b border-gray-800/20 bg-[#111622]/10 text-xs transition-colors hover:bg-[#111622]/40"
                        >
                            <td
                                class="px-4 py-3.5 font-mono font-black tracking-wider"
                            >
                                <span
                                    :class="
                                        tx.type === 'deposit'
                                            ? 'text-emerald-400'
                                            : 'text-rose-400'
                                    "
                                >
                                    {{ tx.type.toUpperCase() }}
                                </span>
                            </td>

                            <td
                                class="max-w-xs truncate px-4 py-3.5 font-bold text-slate-300 normal-case"
                                :title="tx.description"
                            >
                                {{ tx.description }}
                            </td>

                            <td
                                :class="[
                                    'px-4 py-3.5 text-right font-mono font-black',
                                    (tx.type === 'deposit') |
                                    (tx.type === 'pending_payout') |
                                    (tx.type === 'purchase')
                                        ? 'text-emerald-400'
                                        : 'text-rose-400',
                                ]"
                            >
                                {{ tx.type === 'deposit' ? '+' : '-'
                                }}{{ tx.amount.toFixed(2) }}
                            </td>

                            <td
                                class="px-4 py-3.5 text-right font-mono text-slate-400"
                            >
                                <div class="font-bold text-white">
                                    KES
                                    {{
                                        tx.balance_after.toLocaleString(
                                            undefined,
                                            { minimumFractionDigits: 2 },
                                        )
                                    }}
                                </div>
                                <div
                                    class="text-[9px] font-medium text-slate-600"
                                >
                                    PREV: {{ tx.balance_before.toFixed(2) }}
                                </div>
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                <span
                                    :class="[
                                        'rounded border px-2 py-0.5 font-mono text-[9px] font-black uppercase',
                                        tx.status === 'completed'
                                            ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                                            : tx.status === 'pending'
                                              ? 'border-amber-500/20 bg-amber-500/10 text-amber-400'
                                              : 'border-rose-500/20 bg-rose-500/10 text-rose-400',
                                    ]"
                                >
                                    {{ tx.status }}
                                </span>
                            </td>

                            <td
                                class="px-4 py-3.5 text-right font-mono text-[10px] whitespace-nowrap text-slate-500"
                            >
                                {{ formatDate(tx.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import DepositPopover from './DepositPopover.vue';

// Props interface expectation mapping directly to array records
defineProps({
    transactions: {
        type: Array,
        required: true,
        default: () => [],
    },
});

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
</script>
