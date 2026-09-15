<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    withdrawals: Object,
    counts: Object,
    filters: Object,
});

const status = ref(props.filters.status || 'pending');
const search = ref(props.filters.search || '');
const rejectModal = ref<number | null>(null);
const rejectReason = ref('');

const apply = () => {
    router.get('/admin/withdrawals', {
        status: status.value,
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
};

const approve = (id: number) => {
    if (!confirm('Approve this withdrawal? It will proceed to M-Pesa payout.')) return;
    router.post(`/admin/withdrawals/${id}/approve`);
};

const openReject = (id: number) => {
    rejectModal.value = id;
    rejectReason.value = '';
};

const reject = () => {
    if (!rejectModal.value) return;
    router.post(`/admin/withdrawals/${rejectModal.value}/reject`, { reason: rejectReason.value }, {
        onSuccess: () => { rejectModal.value = null; rejectReason.value = ''; },
    });
};

const fmt = (n: number) => 'KES ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Withdrawals — Admin" />

    <div>
        <div class="mb-6 border-b border-[#232d42] pb-6">
            <h1 class="text-2xl font-black tracking-wider text-white uppercase">Withdrawals</h1>
            <p class="mt-1 text-xs text-slate-500">M-Pesa payout queue</p>
        </div>

        <!-- Stat cards -->
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Pending</span>
                <p class="mt-1 text-2xl font-black text-amber-400">{{ counts.pending }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Processing</span>
                <p class="mt-1 text-2xl font-black text-sky-400">{{ counts.processing }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Completed today</span>
                <p class="mt-1 text-2xl font-black text-emerald-400">{{ counts.completed_today }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Failed today</span>
                <p class="mt-1 text-2xl font-black text-rose-400">{{ counts.failed_today }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-3">
            <select v-model="status" @change="apply"
                class="rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white focus:outline-none">
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
                <option value="all">All</option>
            </select>
            <input v-model="search" @keyup.enter="apply" type="text" placeholder="Search reference or user…"
                class="flex-1 min-w-[240px] rounded-lg border border-[#232d42] bg-[#161c2a] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none" />
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-[#232d42]">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Destination</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Requested</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#232d42]/60 bg-[#161c2a]">
                    <tr v-for="w in withdrawals.data" :key="w.id" class="hover:bg-[#1a2233]">
                        <td class="px-4 py-3 font-mono text-[10px] text-sky-400">{{ w.reference }}</td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-200">{{ w.user?.name }}</div>
                            <div class="text-[10px] text-slate-500">{{ w.user?.code }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono text-[10px] text-slate-400">
                            {{ w.destination?.phone || '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-black text-white">{{ fmt(w.amount) }}</td>
                        <td class="px-4 py-3">
                            <span :class="[
                                'rounded border px-2 py-0.5 text-[9px] font-black tracking-widest uppercase',
                                w.status === 'pending' ? 'border-amber-500/30 bg-amber-500/10 text-amber-400' :
                                w.status === 'processing' ? 'border-sky-500/30 bg-sky-500/10 text-sky-400' :
                                w.status === 'completed' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' :
                                'border-rose-500/30 bg-rose-500/10 text-rose-400'
                            ]">{{ w.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ new Date(w.created_at).toLocaleString() }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button v-if="w.status === 'pending'" @click="approve(w.id)"
                                class="rounded border border-emerald-500/40 bg-emerald-500/10 px-3 py-1 text-[10px] font-black tracking-widest text-emerald-400 uppercase hover:bg-emerald-500/20">
                                Approve
                            </button>
                            <button v-if="w.status === 'pending' || w.status === 'processing'" @click="openReject(w.id)"
                                class="rounded border border-rose-500/40 bg-rose-500/10 px-3 py-1 text-[10px] font-black tracking-widest text-rose-400 uppercase hover:bg-rose-500/20">
                                Reject
                            </button>
                        </td>
                    </tr>
                    <tr v-if="withdrawals.data.length === 0">
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500">No withdrawals in this view.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Reject modal -->
        <Teleport to="body">
            <div v-if="rejectModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="rejectModal = null">
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-md rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                    <h3 class="mb-3 text-sm font-black tracking-widest text-rose-400 uppercase">Reject withdrawal</h3>
                    <p class="mb-4 text-xs text-slate-500">
                        The user's wallet will be refunded automatically.
                    </p>
                    <textarea v-model="rejectReason" rows="3" placeholder="Reason (visible to the user)"
                        class="w-full resize-none rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-rose-500"></textarea>
                    <div class="mt-4 flex justify-end gap-2">
                        <button @click="rejectModal = null" class="rounded border border-[#232d42] px-4 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase">Cancel</button>
                        <button @click="reject" :disabled="rejectReason.length < 5" class="rounded bg-rose-500 px-4 py-2 text-[10px] font-black tracking-widest text-white uppercase disabled:opacity-50">Confirm</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>