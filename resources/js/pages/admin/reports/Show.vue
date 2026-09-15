<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    report: Object,
    betslip: Object,
});

const notes = ref(props.report.resolution_notes || '');

const updateStatus = (status: string) => {
    router.post(`/admin/reports/${props.report.id}/status`, {
        status,
        resolution_notes: notes.value,
    });
};

const forceRefund = () => {
    if (
        !confirm(
            'Refund all buyers of this betslip and mark it as lost? This cannot be undone.',
        )
    )
        return;
    router.post(`/admin/reports/${props.report.id}/force-refund`);
};
</script>

<template>
    <Head :title="`Report #${report.id} — Admin`" />

    <div>
        <div class="mb-6 border-b border-[#232d42] pb-6">
            <Link
                href="/admin/reports"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← All reports
            </Link>
            <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1
                        class="text-2xl font-black tracking-wider text-white uppercase"
                    >
                        {{ report.subject }}
                    </h1>
                    <p class="mt-1 text-xs text-slate-500">
                        Report #{{ report.id }} · {{ report.type }} ·
                        {{ report.severity }} ·
                        {{ new Date(report.created_at).toLocaleString() }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="updateStatus('investigating')"
                        class="rounded-lg border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Investigating
                    </button>
                    <button
                        @click="updateStatus('resolved')"
                        class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                    >
                        Resolve
                    </button>
                    <button
                        @click="updateStatus('closed')"
                        class="rounded-lg border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Description -->
                <section
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                >
                    <h2
                        class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Description
                    </h2>
                    <p
                        class="text-sm leading-relaxed whitespace-pre-wrap text-slate-300"
                    >
                        {{ report.description }}
                    </p>
                </section>

                <!-- Betslip context -->
                <section
                    v-if="betslip"
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                >
                    <h2
                        class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Attached Betslip
                    </h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Code</span>
                            <span class="font-mono text-slate-200">{{
                                betslip.code
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Seller</span>
                            <span class="text-slate-200"
                                >{{ betslip.seller?.name }} ({{
                                    betslip.seller?.code
                                }})</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status</span>
                            <span class="text-slate-200 uppercase">{{
                                betslip.status
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Price</span>
                            <span class="font-mono text-emerald-400"
                                >KES
                                {{ Number(betslip.price).toFixed(2) }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Buyers</span>
                            <span class="text-slate-200">{{
                                betslip.purchases?.length || 0
                            }}</span>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-[#232d42] pt-4">
                        <button
                            @click="forceRefund"
                            class="rounded border border-rose-500/40 bg-rose-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-rose-400 uppercase hover:bg-rose-500/20"
                        >
                            Force refund + mark as lost
                        </button>
                    </div>
                </section>

                <!-- Resolution notes -->
                <section
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                >
                    <h2
                        class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Resolution Notes
                    </h2>
                    <textarea
                        v-model="notes"
                        rows="4"
                        placeholder="Internal notes about how this was handled…"
                        class="w-full resize-none rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
                    ></textarea>
                    <button
                        @click="updateStatus(report.status)"
                        class="mt-3 rounded-lg border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Save notes
                    </button>
                </section>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <section
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                >
                    <h2
                        class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Reporter
                    </h2>
                    <div class="space-y-2 text-xs">
                        <div>
                            <span class="text-slate-500">Name: </span>
                            <span class="text-slate-200">{{
                                report.user?.name || 'Guest'
                            }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Email: </span>
                            <span class="text-slate-200">{{
                                report.contact_email
                            }}</span>
                        </div>
                        <div v-if="report.user">
                            <span class="text-slate-500">Code: </span>
                            <span class="font-mono text-slate-200">{{
                                report.user.code
                            }}</span>
                        </div>
                    </div>
                    <Link
                        v-if="report.user"
                        :href="`/admin/users/${report.user.id}`"
                        class="mt-4 inline-block text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
                    >
                        View user profile →
                    </Link>
                </section>

                <section
                    v-if="report.screenshot"
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                >
                    <h2
                        class="mb-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Screenshot
                    </h2>
                    <a
                        :href="`/storage/${report.screenshot}`"
                        target="_blank"
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
                    >
                        Open attachment →
                    </a>
                </section>

                <section
                    v-if="report.resolver"
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                >
                    <h2
                        class="mb-3 text-[10px] font-black tracking-widest text-slate-400 uppercase"
                    >
                        Resolved By
                    </h2>
                    <p class="text-xs text-slate-300">
                        {{ report.resolver.name }}
                    </p>
                    <p class="text-[10px] text-slate-500">
                        {{ new Date(report.resolved_at).toLocaleString() }}
                    </p>
                </section>
            </div>
        </div>
    </div>
</template>
