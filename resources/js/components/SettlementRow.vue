<template>
    <div class="p-3 transition-colors hover:bg-[#111a30]/40">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span
                    :class="[
                        'flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-sm font-mono text-[9px] font-black select-none',
                        settlement.outcome === 'won'
                            ? 'bg-emerald-500 text-[#070b14]'
                            : settlement.outcome === 'voided'
                              ? 'bg-slate-500 text-[#070b14]'
                              : 'bg-rose-500 text-white',
                    ]"
                >
                    {{
                        settlement.outcome === 'won'
                            ? 'W'
                            : settlement.outcome === 'voided'
                              ? 'V'
                              : 'L'
                    }}
                </span>
                <div class="min-w-0">
                    <span
                        class="truncate font-mono text-[11px] font-bold text-sky-400"
                    >
                        {{ settlement.betslip_code }}
                    </span>
                    <p class="mt-0.5 truncate text-[10px] text-slate-500">
                        from {{ settlement.buyer_name }} ·
                        {{ settlement.settled_ago }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <p
                    class="font-mono text-xs font-black"
                    :class="
                        settlement.outcome === 'won'
                            ? 'text-emerald-400'
                            : 'text-slate-500'
                    "
                >
                    <Money
                        :value="settlement.net"
                        :currency="'KES'"
                        :signed="settlement.outcome === 'won'"
                    />
                </p>
                <p class="text-[9px] text-slate-500 uppercase">
                    {{
                        settlement.outcome === 'won'
                            ? 'Net earned'
                            : settlement.outcome === 'voided'
                              ? 'Voided'
                              : 'Refunded'
                    }}
                </p>
            </div>
        </div>

        <div
            v-if="settlement.outcome === 'won'"
            class="mt-2 flex flex-wrap items-center gap-3 border-t border-gray-800/40 pt-2 font-mono text-[9px] tracking-wide text-slate-500 uppercase"
        >
            <span>
                Gross
                <span class="text-slate-300">
                    <Money :value="settlement.gross" :currency="'KES'" />
                </span>
            </span>
            <span class="text-slate-700">·</span>
            <span>
                Fee
                <span class="text-rose-400">
                    <Money :value="settlement.fee" :currency="'KES'" />
                </span>
            </span>
            <span class="text-slate-700">·</span>
            <span>
                Net
                <span class="font-black text-emerald-400">
                    <Money :value="settlement.net" :currency="'KES'" />
                </span>
            </span>
        </div>
    </div>
</template>

<script setup>
import Money from '@/components/Money.vue';

defineProps({
    settlement: { type: Object, required: true },
});
</script>