<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    health: { type: Object, required: true },
});

const refresh = () => {
    router.reload({ only: ['health'] });
};

const overallLabel = computed(() => ({
    ok: 'All systems healthy',
    warn: 'Something needs attention',
    fail: 'Critical issues detected',
}[props.health.overall] ?? 'Unknown'));

const overallClasses = computed(() => ({
    ok: 'border-emerald-500/30 bg-emerald-500/5 text-emerald-400',
    warn: 'border-amber-500/30 bg-amber-500/5 text-amber-400',
    fail: 'border-rose-500/40 bg-rose-500/10 text-rose-400',
}[props.health.overall] ?? 'border-[#232d42] bg-[#161c2a] text-slate-400'));

const cardBorder = (status: string) => ({
    ok: 'border-[#232d42]',
    warn: 'border-amber-500/30',
    fail: 'border-rose-500/40',
}[status] ?? 'border-[#232d42]');

const statusBadge = (status: string) => ({
    ok: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400',
    warn: 'border-amber-500/20 bg-amber-500/10 text-amber-400',
    fail: 'border-rose-500/20 bg-rose-500/10 text-rose-400',
}[status] ?? 'border-slate-500/20 bg-slate-500/10 text-slate-400');

const valueClass = (status: string) => ({
    ok: 'text-white',
    warn: 'text-amber-400',
    fail: 'text-rose-400',
}[status] ?? 'text-white');

const formatValue = (check: any) => {
    if (check.value === null || check.value === undefined) return '—';
    if (check.key === 'withdrawal_queue_age') {
        return `${check.value}h`;
    }
    if (check.key === 'escrow_integrity') {
        return check.value === 0 ? '0' : (check.value > 0 ? `+${check.value}` : `${check.value}`);
    }
    return Number(check.value).toLocaleString();
};
</script>

<template>
    <Head title="System Health — Admin" />

    <div>
        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-[#232d42] pb-6">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <Link
                        href="/admin"
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase hover:text-slate-300"
                    >
                        ← Operations
                    </Link>
                </div>
                <h1 class="text-2xl font-black tracking-wider text-white uppercase">
                    System Health
                </h1>
                <p class="mt-2 text-xs text-slate-500">
                    Integrity checks · run automatically every 5 minutes
                    <span class="text-slate-600"> · </span>
                    checked {{ new Date(health.checked_at).toLocaleTimeString() }}
                </p>
            </div>

            <button
                type="button"
                @click="refresh"
                class="rounded-lg border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/40 hover:text-sky-400"
            >
                Re-run checks
            </button>
        </div>

        <!-- Overall banner -->
        <div
            :class="[
                'mb-6 rounded-lg border p-4',
                overallClasses,
            ]"
        >
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-2xl leading-none">
                        {{ health.overall === 'ok' ? '✓' : health.overall === 'warn' ? '!' : '✗' }}
                    </span>
                    <div>
                        <p class="text-sm font-black tracking-widest uppercase">
                            {{ overallLabel }}
                        </p>
                        <p class="mt-0.5 text-[11px] opacity-80">
                            {{ health.checks.length }} checks run
                            <span class="opacity-60"> · </span>
                            {{ health.checks.filter((c: any) => c.status !== 'ok').length }} with issues
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Check cards -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="check in health.checks"
                :key="check.key"
                :class="[
                    'rounded-lg border bg-[#161c2a] p-5',
                    cardBorder(check.status),
                ]"
            >
                <div class="flex items-start justify-between gap-3">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        {{ check.label }}
                    </span>
                    <span
                        :class="[
                            'rounded border px-2 py-0.5 text-[9px] font-black tracking-widest uppercase',
                            statusBadge(check.status),
                        ]"
                    >
                        {{ check.status }}
                    </span>
                </div>

                <p
                    :class="[
                        'mt-3 font-mono text-2xl font-black',
                        valueClass(check.status),
                    ]"
                >
                    {{ formatValue(check) }}
                </p>

                <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                    {{ check.context }}
                </p>

                <!-- Details, if any -->
                <ul
                    v-if="check.details && check.details.length"
                    class="mt-3 space-y-1 border-t border-[#232d42]/60 pt-3"
                >
                    <li
                        v-for="(detail, i) in check.details.slice(0, 5)"
                        :key="i"
                        class="flex items-center justify-between text-[10px]"
                    >
                        <span class="text-slate-500">
                            {{ detail.label ?? `User #${detail.user_id}` ?? detail.id ?? '' }}
                        </span>
                        <span class="font-mono text-slate-400">
                            {{ detail.count ?? detail.wallet_total ?? detail.failed_at ?? '' }}
                        </span>
                    </li>
                    <li
                        v-if="check.details.length > 5"
                        class="pt-1 text-[10px] text-slate-600"
                    >
                        + {{ check.details.length - 5 }} more
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer note -->
        <div class="mt-8 rounded-lg border border-[#232d42]/60 bg-[#0f1422] p-4">
            <p class="text-[11px] leading-relaxed text-slate-500">
                Checks are computed live every time this page loads, and by a scheduled command every five minutes.
                <span class="font-black tracking-widest text-emerald-400 uppercase">Ok</span>
                means the invariant holds.
                <span class="font-black tracking-widest text-amber-400 uppercase">Warn</span>
                is a soft threshold worth watching.
                <span class="font-black tracking-widest text-rose-400 uppercase">Fail</span>
                means money or data is inconsistent — investigate immediately.
            </p>
        </div>
    </div>
</template>