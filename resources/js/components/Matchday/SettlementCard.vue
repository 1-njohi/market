<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    settlement: { type: Object, required: true },
});

const isDismissing = ref(false);

const rankLabel = (rank: number, total: number) => {
    if (rank === 1) return `🏆 1st of ${total}`;
    if (rank === 2) return `🥈 2nd of ${total}`;
    if (rank === 3) return `🥉 3rd of ${total}`;
    return `#${rank} of ${total}`;
};

const dismiss = async () => {
    if (isDismissing.value) return;
    isDismissing.value = true;
    try {
        await axios.post(`/matchday/settlements/${props.settlement.entry_id}/dismiss`);
        router.reload({ only: ['unacknowledged_settlements'] });
    } catch {
        isDismissing.value = false;
    }
};
</script>

<template>
    <div
        class="rounded-lg border border-amber-500/40 bg-gradient-to-r from-amber-500/15 via-amber-500/5 to-transparent p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 text-[10px] font-black tracking-widest text-amber-400 uppercase">
                    <span>🏆</span>
                    <span>MD {{ settlement.matchday_number }} settled · {{ settlement.league_code }}</span>
                </div>
                <h3 class="mt-1 font-mono text-lg font-black text-white">
                    {{ settlement.name }}
                </h3>
                <p class="mt-1 font-mono text-[11px] text-slate-300">
                    <span class="font-black text-amber-400">{{ rankLabel(settlement.rank, settlement.total_participants)
                        }}</span>
                    · <span class="font-black text-slate-200">{{ settlement.correct }}</span> correct
                </p>
            </div>
            <div class="flex flex-shrink-0 items-center gap-2">
                <Link :href="`/contests/${settlement.uuid}/results`"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400">
                    View results →
                </Link>
                <button type="button" @click="dismiss" :disabled="isDismissing"
                    class="cursor-pointer rounded-lg border border-[#232d42] bg-[#111622] px-3 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-slate-500/60 hover:text-slate-200 disabled:cursor-not-allowed disabled:opacity-50">
                    {{ isDismissing ? '…' : 'Dismiss' }}
                </button>
            </div>
        </div>
    </div>
</template>