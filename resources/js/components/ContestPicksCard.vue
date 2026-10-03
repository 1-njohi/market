<script setup>
import { computed } from 'vue';

const props = defineProps({
    legs: { type: Array, required: true },
    canSubmit: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false },
    minLegs: { type: Number, default: 5 },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['review-picks', 'remove-leg']);

const totalOdds = computed(() =>
    props.legs.reduce((carry, leg) => carry * (selectedOdd(leg) ?? 1), 1),
);

const selectedOdd = (leg) => {
    const opt = leg.options?.find((o) => o.value === leg.selection);
    return opt?.odd ?? null;
};

const shortfall = computed(() =>
    Math.max(0, props.minLegs - props.legs.length),
);

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
    <div class="flex max-h-[75vh] flex-col">
        <!-- Header -->
        <header class="flex items-center justify-between border-b border-[#232d42] px-5 py-3">
            <span class="text-[10px] font-black tracking-widest text-emerald-400 uppercase">
                Your picks
            </span>
            <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                {{ legs.length }} {{ legs.length === 1 ? 'leg' : 'legs' }}
            </span>
        </header>

        <!-- Empty state -->
        <div
            v-if="legs.length === 0"
            class="px-5 py-8 text-center text-[11px] text-slate-500"
        >
            No picks yet. Tap an odd on the fixture list to add a leg.
        </div>

        <!-- List -->
        <ul
            v-else
            class="flex-1 divide-y divide-[#232d42]/60 overflow-y-auto"
        >
            <li
                v-for="(leg, i) in legs"
                :key="`${leg.fixture_id}-${leg.market_id}`"
                class="flex items-start justify-between gap-3 px-5 py-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                        Leg {{ i + 1 }} · {{ leg.market_label }} ·
                        {{ formatKickoff(leg.kickoff) }}
                    </p>
                    <p class="mt-0.5 truncate text-sm font-bold text-slate-200">
                        {{ leg.home_team }}
                        <span class="text-slate-500">vs</span>
                        {{ leg.away_team }}
                    </p>
                    <p class="mt-0.5 truncate text-[11px] font-semibold text-sky-400">
                        {{ leg.selection }}
                    </p>
                </div>

                <div class="flex flex-shrink-0 flex-col items-end gap-1">
                    <span class="font-mono text-sm font-black text-emerald-400">
                        {{ selectedOdd(leg) != null ? Number(selectedOdd(leg)).toFixed(2) : '—' }}
                    </span>
                    <button
                        type="button"
                        @click="emit('remove-leg', i)"
                        class="cursor-pointer text-[10px] font-black tracking-widest text-rose-400 uppercase hover:text-rose-300"
                    >
                        Remove
                    </button>
                </div>
            </li>
        </ul>

        <!-- Footer -->
        <footer class="border-t border-[#232d42] bg-[#0f1422] px-5 py-3">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    Total odds
                </span>
                <span class="font-mono text-lg font-black text-emerald-400">
                    {{ totalOdds.toFixed(2) }}×
                </span>
            </div>
        </footer>

        <!-- Actions -->
        <div class="border-t border-[#232d42] bg-[#0d1527] px-5 py-4">
            <p
                v-if="shortfall > 0"
                class="mb-3 text-center text-[10px] font-bold tracking-widest text-amber-400 uppercase"
            >
                Add {{ shortfall }} more {{ shortfall === 1 ? 'leg' : 'legs' }} to continue
            </p>

            <p
                v-if="errors.legs"
                class="mb-3 text-center text-[11px] text-rose-400"
            >
                {{ errors.legs }}
            </p>

            <button
                type="button"
                @click="emit('review-picks')"
                :disabled="!canSubmit"
                class="w-full cursor-pointer rounded-lg bg-[#ff8c00] px-6 py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100"
            >
                {{ submitting ? 'Reviewing…' : 'Next: name & publish' }}
            </button>
        </div>
    </div>
</template>