<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: { type: Object, required: true },
    counts: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');
const status = ref(props.filters.status || '');
const sort = ref(props.filters.sort || 'newest');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role: role.value || undefined,
            status: status.value || undefined,
            sort: sort.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});

watch([role, sort], applyFilters);

const tabs = [
    { value: '',            label: 'All' },
    { value: 'active',      label: 'Active' },
    { value: 'unverified',  label: 'Unverified' },
    { value: 'suspended',   label: 'Suspended' },
    { value: 'admin',       label: 'Admins' },
];

const setStatus = (value: string) => {
    if (status.value === value) return;
    status.value = value;
    applyFilters();
};

const clearFilters = () => {
    search.value = '';
    role.value = '';
    status.value = '';
    sort.value = 'newest';
    router.get('/admin/users', {}, { preserveState: false, replace: true });
};

const formatKES = (n: number) =>
    'KES ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 0 });

const statusBadge = (user: any) => {
    if (user.suspended_at) return { label: 'Suspended', color: 'rose' };
    if (!user.is_verified) return { label: 'Unverified', color: 'amber' };
    if (user.is_admin) return { label: 'Admin', color: 'sky' };
    return { label: 'Active', color: 'emerald' };
};

const badgeClasses: Record<string, string> = {
    rose: 'border-rose-500/20 bg-rose-500/10 text-rose-400',
    amber: 'border-amber-500/20 bg-amber-500/10 text-amber-400',
    sky: 'border-sky-500/20 bg-sky-500/10 text-sky-400',
    emerald: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400',
};
</script>

<template>
    <Head title="Users — Admin" />

    <div>
        <!-- Header -->
        <div class="mb-6 border-b border-[#232d42] pb-6">
            <h1 class="text-2xl font-black tracking-wider text-white uppercase">Users</h1>
            <p class="mt-1 text-xs text-slate-500">Manage accounts, roles, and access</p>
        </div>

        <!-- Stat cards -->
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Total users</span>
                <p class="mt-1 text-2xl font-black text-white">{{ counts.total.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Sellers</span>
                <p class="mt-1 text-2xl font-black text-emerald-400">{{ counts.sellers.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Buyers</span>
                <p class="mt-1 text-2xl font-black text-sky-400">{{ counts.buyers.toLocaleString() }}</p>
            </div>
            <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">Suspended</span>
                <p class="mt-1 text-2xl font-black text-rose-400">{{ counts.suspended.toLocaleString() }}</p>
            </div>
        </div>

        <!-- Status tabs -->
        <div class="mb-5 flex flex-wrap items-center gap-1 border-b border-[#232d42]">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                @click="setStatus(tab.value)"
                :class="[
                    'relative px-4 py-2 text-[11px] font-black tracking-widest uppercase transition-colors',
                    status === tab.value
                        ? 'text-sky-400'
                        : 'text-slate-500 hover:text-slate-300',
                ]"
            >
                {{ tab.label }}
                <span
                    v-if="status === tab.value"
                    class="absolute inset-x-0 -bottom-px h-0.5 bg-sky-400"
                ></span>
            </button>
        </div>

        <!-- Secondary filters -->
        <div class="mb-5 flex flex-wrap items-center gap-2 rounded-lg border border-[#232d42] bg-[#0f1422] p-3">
            <input
                v-model="search"
                type="text"
                placeholder="Search name, email, phone, or code…"
                class="min-w-[240px] flex-1 rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-xs text-white placeholder-slate-600 focus:border-sky-500 focus:outline-none"
            />

            <select
                v-model="role"
                class="rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-xs text-slate-300 focus:border-sky-500 focus:outline-none"
            >
                <option value="">All roles</option>
                <option value="seller">Sellers</option>
                <option value="buyer">Buyers</option>
                <option value="both">Both</option>
            </select>

            <select
                v-model="sort"
                class="rounded border border-[#232d42] bg-[#070b14] px-3 py-2 text-xs text-slate-300 focus:border-sky-500 focus:outline-none"
            >
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="name">Name A–Z</option>
                <option value="balance">Highest balance</option>
            </select>

            <button
                type="button"
                @click="clearFilters"
                class="rounded border border-[#232d42] px-3 py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase hover:border-sky-500/40 hover:text-slate-200"
            >
                Reset
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#0f1422] text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                            <th class="px-4 py-3">Joined</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#232d42]/60">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="transition-colors hover:bg-[#111a30]"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}`"
                                        :alt="user.name"
                                        class="h-8 w-8 rounded-full border border-[#232d42]"
                                    />
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-200">{{ user.name }}</p>
                                        <p class="truncate text-[10px] text-slate-500">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-sky-400">
                                {{ user.code }}
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-400">
                                {{ user.country_code }}{{ user.phone }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-black text-emerald-400">
                                {{ formatKES(user.wallet?.balance || 0) }}
                            </td>
                            <td class="px-4 py-3 text-[10px] text-slate-500">
                                {{ user.joined_ago }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'rounded border px-2 py-0.5 text-[9px] font-black tracking-wider uppercase',
                                        badgeClasses[statusBadge(user).color],
                                    ]"
                                >
                                    {{ statusBadge(user).label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="`/admin/users/${user.id}`"
                                    class="inline-block rounded border border-sky-500/20 bg-sky-500/5 px-3 py-1 text-[10px] font-black tracking-widest text-sky-400 uppercase hover:border-sky-500 hover:bg-sky-500/10"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-xs text-slate-500">
                                No users match these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="mt-5 flex items-center justify-between">
            <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                Page {{ users.current_page }} of {{ users.last_page }}
            </p>
            <div class="flex gap-2">
                <Link
                    v-if="users.prev_page_url"
                    :href="users.prev_page_url"
                    preserve-scroll
                    class="rounded border border-[#232d42] px-3 py-1.5 text-[10px] font-black tracking-widest text-slate-400 uppercase hover:border-sky-500/40 hover:text-sky-400"
                >
                    ← Prev
                </Link>
                <Link
                    v-if="users.next_page_url"
                    :href="users.next_page_url"
                    preserve-scroll
                    class="rounded border border-[#232d42] px-3 py-1.5 text-[10px] font-black tracking-widest text-slate-400 uppercase hover:border-sky-500/40 hover:text-sky-400"
                >
                    Next →
                </Link>
            </div>
        </div>
    </div>
</template>