<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reports: Object,
    counts: Object,
    filters: Object,
});

const status = ref(props.filters.status || 'open');
const type = ref(props.filters.type || '');

const apply = () => {
    router.get(
        '/admin/reports',
        {
            status: status.value,
            type: type.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const sevClass = (s: string) =>
    ({
        critical: 'border-rose-500/40 bg-rose-500/15 text-rose-400',
        high: 'border-amber-500/40 bg-amber-500/15 text-amber-400',
        medium: 'border-sky-500/40 bg-sky-500/15 text-sky-400',
        low: 'border-slate-500/40 bg-slate-500/15 text-slate-400',
    })[s] || 'border-slate-500/40 bg-slate-500/15 text-slate-400';
</script>

<template>
    <Head title="Reports — Admin" />

    <div>
        <div class="mb-6 border-b border-[#232d42] pb-6">
            <h1 class="text-2xl font-black tracking-wider text-white uppercase">
                Reports
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                User-submitted reports and disputes
            </p>
        </div>

        <!-- Counts -->
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >Open</span
                >
                <p class="mt-1 text-2xl font-black text-amber-400">
                    {{ counts.open }}
                </p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >Investigating</span
                >
                <p class="mt-1 text-2xl font-black text-sky-400">
                    {{ counts.investigating }}
                </p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >Critical</span
                >
                <p class="mt-1 text-2xl font-black text-rose-400">
                    {{ counts.critical }}
                </p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span
                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >Resolved today</span
                >
                <p class="mt-1 text-2xl font-black text-emerald-400">
                    {{ counts.resolved_today }}
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex gap-3">
            <select
                v-model="status"
                @change="apply"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white focus:outline-none"
            >
                <option value="open">Open</option>
                <option value="investigating">Investigating</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
                <option value="all">All</option>
            </select>
            <select
                v-model="type"
                @change="apply"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white focus:outline-none"
            >
                <option value="">All types</option>
                <option value="bug">Bug</option>
                <option value="seller">Seller</option>
                <option value="buyer">Buyer</option>
                <option value="payment">Payment</option>
                <option value="security">Security</option>
                <option value="other">Other</option>
            </select>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-[#232d42]">
            <table class="w-full text-left text-xs">
                <thead
                    class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3">Reporter</th>
                        <th class="px-4 py-3">Age</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#232d42]/60 bg-[#161c2a]">
                    <tr
                        v-for="r in reports.data"
                        :key="r.id"
                        class="hover:bg-[#1a2233]"
                    >
                        <td class="px-4 py-3">
                            <span
                                :class="[
                                    'rounded border px-2 py-0.5 text-[9px] font-black tracking-widest uppercase',
                                    sevClass(r.severity),
                                ]"
                            >
                                {{ r.severity }}
                            </span>
                        </td>
                        <td
                            class="px-4 py-3 text-[10px] text-slate-400 uppercase"
                        >
                            {{ r.type }}
                        </td>
                        <td class="px-4 py-3 font-bold text-slate-200">
                            {{ r.subject }}
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ r.user?.name || r.contact_email }}
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ new Date(r.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="`/admin/reports/${r.id}`"
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
                                >View →</Link
                            >
                        </td>
                    </tr>
                    <tr v-if="reports.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-12 text-center text-slate-500"
                        >
                            No reports match these filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
